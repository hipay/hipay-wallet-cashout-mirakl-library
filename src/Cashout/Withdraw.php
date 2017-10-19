<?php

namespace HiPay\Wallet\Mirakl\Cashout;

use DateTime;
use Exception;
use HiPay\Wallet\Mirakl\Api\Factory;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\BankInfo as BankInfoStatus;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\ManagerInterface as OperationManager;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\Status;
use HiPay\Wallet\Mirakl\Common\AbstractApiProcessor;
use HiPay\Wallet\Mirakl\Exception\UnconfirmedBankAccountException;
use HiPay\Wallet\Mirakl\Exception\UnidentifiedWalletException;
use HiPay\Wallet\Mirakl\Exception\WalletNotFoundException;
use HiPay\Wallet\Mirakl\Exception\WrongWalletBalance;
use HiPay\Wallet\Mirakl\Service\Validation\ModelValidator;
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
class Withdraw extends AbstractApiProcessor
{
    const SCALE = 2;

    protected $operationManager;

    protected $vendorManager;

    protected $operator;

    protected $formatNotification;

    protected $logOperationsManager;


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
    )
    {
        parent::__construct($dispatcher, $logger, $factory);

        $this->operationManager   = $operationManager;
        $this->vendorManager      = $vendorManager;
        $this->formatNotification = new FormatNotification();

        ModelValidator::validate($operator, 'Operator');
        $this->operator = $operator;

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
            "Operation to withdraw : ".count($toWithdraw),
            array('miraklId' => null, "action" => "Withdraw")
            );

        /** @var OperationInterface $operation */
        foreach ($toWithdraw as $operation) {
            try {

                //Execute the withdrawal
                $withdrawId = $this->withdraw($operation);

                //Set operation new data
                $this->logger->info(
                    "[OK] Withdraw operation ".$operation->getWithdrawId()." executed",
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

            //Check account balance
            $amount  = round(($operation->getAmount()), self::SCALE);
            $balance = round($this->hipay->getBalance($vendor), self::SCALE);

            if ($balance < $amount) {
                //Operator operation
                if ($operation->getMiraklId() === null || !$operation->getMiraklId() ) {
                    $amount = $balance;
                    //Vendor operation
                } else {
                    throw new WrongWalletBalance(
                    $vendor->getMiraklId(), 'withdraw', $amount, $balance
                    );
                }
            }

            $operation->setHiPayId($vendor->getHiPayId());

            //Withdraw
            $withdrawId = $this->hipay->withdraw(
                $vendor, $amount, $this->operationManager->generateWithdrawLabel($operation)
            );

            $operation->setWithdrawId($withdrawId);
            $operation->setStatus(new Status(Status::WITHDRAW_REQUESTED));
            $operation->setUpdatedAt(new DateTime());
            $operation->setWithdrawnAmount($amount);
            $this->operationManager->save($operation);

            $this->logOperation(
                $operation->getMiraklId(), $operation->getPaymentVoucher(), Status::WITHDRAW_REQUESTED, ""
            );

            return $withdrawId;

        } catch (WrongWalletBalance $e) {
            $operation->setStatus(new Status(Status::WITHDRAW_NEGATIVE));
            $operation->setUpdatedAt(new DateTime());
            $this->operationManager->save($operation);

            $this->logOperation(
                $operation->getMiraklId(), $operation->getPaymentVoucher(), Status::WITHDRAW_NEGATIVE, $e->getMessage()
            );

            throw $e;
        } catch (Exception $e) {
            $operation->setStatus(new Status(Status::WITHDRAW_FAILED));
            $operation->setUpdatedAt(new DateTime());
            $this->operationManager->save($operation);

            $this->logOperation(
                $operation->getMiraklId(), $operation->getPaymentVoucher(), Status::WITHDRAW_FAILED, $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * Return the right vendor for an operation
     *
     * @param OperationInterface $operation
     *
     * @return VendorInterface|null
     */
    protected function getVendor(OperationInterface $operation)
    {
        if ($operation->getMiraklId()) {
            return $this->vendorManager->findByMiraklId($operation->getMiraklId());
        }
        return $this->operator;
    }

    /**
     * Fetch the operation to withdraw from the storage
     *
     * @return OperationInterface[]
     */
    protected function getWithdrawableOperations()
    {

        $toWithdrawSuccess = $this->operationManager->findByStatus(
            new Status(Status::TRANSFER_SUCCESS)
        );

        $toWithdrawFailed = $this->operationManager->findByStatus(
            new Status(Status::WITHDRAW_FAILED)
        );

        $toWithdrawNegative = $this->operationManager->findByStatus(
            new Status(Status::WITHDRAW_NEGATIVE)
        );

        $toWithdraw = array_merge($toWithdrawNegative, $toWithdrawFailed, $toWithdrawSuccess);

        return $toWithdraw;
    }

    /**
     * Log Operations
     * @param type $miraklId
     * @param type $paymentVoucherNumber
     * @param type $status
     * @param type $message
     */
    private function logOperation($miraklId, $paymentVoucherNumber, $status, $message)
    {
        $logOperation = $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber($miraklId,
                                                                                           $paymentVoucherNumber);

        if ($logOperation == null) {
            $this->logger->warning(
                "Could not fnd existing log for this operations : paymentVoucherNumber = ".$paymentVoucherNumber,
                array("action" => "Operation process", "miraklId" => $miraklId)
            );
        } else {
            $logOperation->setStatusWithDrawal($status);

            $logOperation->setMessage($message);

            $this->logOperationsManager->save($logOperation);
        }
    }
}