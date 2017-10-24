<?php

namespace HiPay\Wallet\Mirakl\Cashout;

use DateTime;
use Exception;
use HiPay\Wallet\Mirakl\Api\Factory;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\BankInfo as BankInfoStatus;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\ManagerInterface as OperationManager;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\Status;
use HiPay\Wallet\Mirakl\Cashout\AbstractOperationProcessor;
use HiPay\Wallet\Mirakl\Exception\UnconfirmedBankAccountException;
use HiPay\Wallet\Mirakl\Exception\UnidentifiedWalletException;
use HiPay\Wallet\Mirakl\Exception\WalletNotFoundException;
use HiPay\Wallet\Mirakl\Exception\WrongWalletBalance;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorManagerInterface as VendorManager;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use HiPay\Wallet\Mirakl\Notification\FormatNotification;
use HiPay\Wallet\Mirakl\Notification\Model\LogOperationsManagerInterface as LogOperationsManager;

/**
 * Process the operations created by the cashout/initializer
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
class Withdraw extends AbstractOperationProcessor
{
    protected $formatNotification;

    /**
     * Processor constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param Factory $factory
     * @param OperationManager $operationManager ,
     * @param VendorManager $vendorManager
     * @param VendorInterface $operator
     *
     * @throws \HiPay\Wallet\Mirakl\Exception\ValidationFailedException
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        Factory $factory,
        OperationManager $operationManager,
        VendorManager $vendorManager,
        VendorInterface $operator,
        LogOperationsManager $logOperationsManager
    ) {
        parent::__construct(
            $dispatcher,
            $logger,
            $factory,
            $operationManager,
            $vendorManager,
            $logOperationsManager,
            $operator
        );

        $this->formatNotification = new FormatNotification();

        $this->logOperationsManager = $logOperationsManager;
    }

    /**
     * Main processing function.
     *
     * @throws WrongWalletBalance
     * @throws WalletNotFoundException
     * @throws UnconfirmedBankAccountException
     * @throws UnidentifiedWalletException
     *
     * @codeCoverageIgnore
     */
    public function process()
    {
        $this->logger->info("Withdraw operations", array('miraklId' => null, "action" => "Withdraw"));

        $this->withdrawOperations();
    }

    /**
     * Execute the operation needing withdrawal.
     *
     */
    protected function withdrawOperations()
    {

        $toWithdraw = $this->getWithdrawableOperations();

        $this->logger->info(
            "Operation to withdraw : " . count($toWithdraw),
            array('miraklId' => null, "action" => "Withdraw")
        );

        /** @var OperationInterface $operation */
        foreach ($toWithdraw as $operation) {
            try {

                //Execute the withdrawal
                $withdrawId = $this->withdraw($operation);

                //Set operation new data
                $this->logger->info(
                    "[OK] Withdraw operation " . $operation->getWithdrawId() . " executed",
                    array('miraklId' => $operation->getMiraklId(), "action" => "Withdraw")
                );
            } catch (Exception $e) {
                $this->logger->info(
                    "[OK] Withdraw operation failed",
                    array('miraklId' => $operation->getMiraklId(), "action" => "Withdraw")
                );
                $this->handleException(
                    $e,
                    'critical',
                    array('miraklId' => $operation->getMiraklId(), "action" => "Withdraw")
                );
            }
        }
    }

    /**
     * Put the money into the real bank account of the operator|seller.
     *
     * @param OperationInterface $operation
     * @return int
     * @throws Exception
     */
    public function withdraw(OperationInterface $operation)
    {
        try {
            $vendor = $this->getVendor($operation);

            if (!$vendor || $this->hipay->isAvailable($vendor->getEmail())) {
                throw new WalletNotFoundException($vendor);
            }

            if (!$this->hipay->isIdentified($vendor)) {
                throw new UnidentifiedWalletException($vendor);
            }

            $bankInfoStatus = trim($this->hipay->bankInfosStatus($vendor));

            if ($bankInfoStatus != BankInfoStatus::VALIDATED) {
                throw new UnconfirmedBankAccountException(
                    new BankInfoStatus(BankInfoStatus::getLabel($bankInfoStatus)), $operation->getMiraklId()
                );
            }

            try {
                $this->hasSufficientFunds($operation->getAmount(), $vendor);
                $amount = round(($operation->getAmount()), self::SCALE);
            } catch (WrongWalletBalance $ex) {
                if ($operation->getMiraklId() === null || !$operation->getMiraklId()) {
                    $amount = $ex->getBalance();
                    //Vendor operation
                } else {
                    throw $ex;
                }
            }

            $operation->setHiPayId($vendor->getHiPayId());

            //Withdraw
            $withdrawId = $this->hipay->withdraw(
                $vendor,
                $amount,
                $this->operationManager->generateWithdrawLabel($operation)
            );

            $operation->setWithdrawId($withdrawId);
            $operation->setStatus(new Status(Status::WITHDRAW_REQUESTED));
            $operation->setUpdatedAt(new DateTime());
            $operation->setWithdrawnAmount($amount);
            $this->operationManager->save($operation);

            $this->logOperation(
                $operation->getMiraklId(),
                $operation->getPaymentVoucher(),
                Status::WITHDRAW_REQUESTED,
                ""
            );

            return $withdrawId;

        } catch (WrongWalletBalance $e) {
            $operation->setStatus(new Status(Status::WITHDRAW_NEGATIVE));
            $operation->setUpdatedAt(new DateTime());
            $this->operationManager->save($operation);

            $this->logOperation(
                $operation->getMiraklId(),
                $operation->getPaymentVoucher(),
                Status::WITHDRAW_NEGATIVE,
                $e->getMessage()
            );

            throw $e;
        } catch (Exception $e) {
            $operation->setStatus(new Status(Status::WITHDRAW_FAILED));
            $operation->setUpdatedAt(new DateTime());
            $this->operationManager->save($operation);

            $this->logOperation(
                $operation->getMiraklId(),
                $operation->getPaymentVoucher(),
                Status::WITHDRAW_FAILED,
                $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * Fetch the operation to withdraw from the storage
     *
     * @return OperationInterface[]
     */
    protected function getWithdrawableOperations()
    {

        $toWithdrawSuccess = $this->operationManager->findByStatus(new Status(Status::TRANSFER_SUCCESS));

        $toWithdrawFailed = $this->operationManager->findByStatus(new Status(Status::WITHDRAW_FAILED));

        $toWithdrawNegative = $this->operationManager->findByStatus(new Status(Status::WITHDRAW_NEGATIVE));

        $toWithdraw = array_merge($toWithdrawNegative, $toWithdrawFailed, $toWithdrawSuccess);

        return $toWithdraw;
    }

}