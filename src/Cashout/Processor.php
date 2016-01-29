<?php

namespace HiPay\Wallet\Mirakl\Cashout;

use DateTime;
use Exception;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\Transfer;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\Status;
use HiPay\Wallet\Mirakl\Common\AbstractProcessor;
use HiPay\Wallet\Mirakl\Api\Mirakl\ConfigurationInterface
    as MiraklConfiguration;
use HiPay\Wallet\Mirakl\Api\HiPay;
use HiPay\Wallet\Mirakl\Api\HiPay\ConfigurationInterface
    as HiPayConfiguration;
use HiPay\Wallet\Mirakl\Exception\WrongWalletBalance;
use HiPay\Wallet\Mirakl\Exception\NoWalletFoundException;
use HiPay\Wallet\Mirakl\Exception\UnconfirmedBankAccountException;
use HiPay\Wallet\Mirakl\Exception\UnidentifiedWalletException;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\ManagerInterface
    as OperationManager;
use HiPay\Wallet\Mirakl\Vendor\Model\ManagerInterface as VendorManager;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\BankInfo as BankInfoStatus;

/**
 * Class Processor.
 * Process the operations created by the cashout/initializer
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Processor extends AbstractProcessor
{
    /** @var  OperationManager */
    protected $operationManager;

    /** @var  VendorManager */
    protected $vendorManager;

    /** @var VendorInterface */
    protected $operator;
    /**
     * @var VendorInterface
     */
    protected $technical;

    /**
     * Processor constructor.
     *
     * @param MiraklConfiguration $miraklConfig
     * @param HiPayConfiguration $hipayConfig
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param OperationManager $operationManager ,
     * @param VendorManager $vendorManager
     * @param VendorInterface $operator
     * @param VendorInterface $technical
     */
    public function __construct(
        MiraklConfiguration $miraklConfig,
        HiPayConfiguration $hipayConfig,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        OperationManager $operationManager,
        VendorManager $vendorManager,
        VendorInterface $operator,
        VendorInterface $technical
    ) {
        parent::__construct($miraklConfig, $hipayConfig, $dispatcher, $logger);
        $this->operationManager = $operationManager;
        $this->vendorManager = $vendorManager;
        $this->operator = $operator;
        $this->technical = $technical;
    }

    /**
     * Main processing function.
     *
     * @throws WrongWalletBalance
     * @throws NoWalletFoundException
     * @throws UnconfirmedBankAccountException
     * @throws UnidentifiedWalletException
     */
    public function process()
    {
        $previousDay = new DateTime('-1 day');

        $this->logger->info("Cashout Processor");

        //Transfer
        $this->transferOperations($previousDay);

        //Withdraw
        $this->withdrawOperations($previousDay);
    }

    /**
     * Execute the operation needing transfer.
     *
     * @param DateTime $previousDay
     */
    protected function transferOperations(DateTime $previousDay)
    {
        $this->logger->info("Transfer operations");
        //Transfer
        $toTransfer = $this->operationManager->findByStatus(
            new Status(Status::CREATED)
        );
        $toTransfer = array_merge(
            $toTransfer,
            $this->operationManager
                ->findByStatusAndAfterUpdatedAt(
                    new Status(Status::TRANSFER_FAILED),
                    $previousDay
                )
        );

        $this->logger->info("Operation transfered : " . count($toTransfer));

        $transferSuccess = new Status(Status::TRANSFER_SUCCESS);
        $transferFailed = new Status(Status::TRANSFER_FAILED);
        /** @var OperationInterface $operation */
        foreach ($toTransfer as $operation) {
            try {
                $transferId = $this->transferOperation($operation);
                $operation->setStatus($transferSuccess);
                $operation->setTransferId($transferId);
            } catch (Exception $e) {
                $operation->setStatus($transferFailed);
                $this->handleException($e, 'critical');
            }
            $this->operationManager->save($operation);
            $this->logger->info("[OK] Transfer operation executed");
        }
    }
    /**
     * Execute the operation needing withdrawal.
     *
     * @param DateTime $previousDay
     */
    protected function withdrawOperations(DateTime $previousDay)
    {
        $toWithdraw = $this->operationManager->findByStatus(
            new Status(Status::TRANSFER_SUCCESS)
        );
        $toWithdraw = array_merge(
            $toWithdraw,
            $this->operationManager
                ->findByStatusAndAfterUpdatedAt(
                    new Status(Status::WITHDRAW_FAILED),
                    $previousDay
                )
        );

        $withdrawRequested = new Status(Status::WITHDRAW_REQUESTED);
        $withdrawFailed = new Status(Status::WITHDRAW_FAILED);

        /** @var OperationInterface $operation */
        foreach ($toWithdraw as $operation) {
            try {
                $withdrawId = $this->withdrawOperation($operation);
                $operation->setWithdrawId($withdrawId);
                $operation->setStatus($withdrawRequested);
            } catch (Exception $e) {
                $operation->setStatus($withdrawFailed);
                $this->handleException($e, 'critical');
            }
            $this->operationManager->save($operation);
            $this->logger->info("[OK] Withdraw operation executed");
        }
    }

    /**
     * Transfer money between the technical
     * wallet and the operator|seller wallet.
     *
     * @param OperationInterface $operation
     *
     * @return int
     *
     * @throws NoWalletFoundException if the wallet is not found
     */
    public function transferOperation(OperationInterface $operation)
    {
        $vendor = $this->getVendor($operation);

        if (!$vendor || $this->hipay->isAvailable($vendor->getEmail())) {
            throw new NoWalletFoundException($vendor);
        }

        $operation->setHiPayId($vendor->getHiPayId());

        $transfer = new Transfer(
            round($operation->getAmount(), 2),
            $vendor,
            $this->operationManager->generatePublicLabel($operation),
            $this->operationManager->generatePrivateLabel($operation)
        );


        //Transfer
        return $this->hipay->transfer($transfer);
    }

    /**
     * Put the money into the real bank account of the operator|seller.
     *
     * @param OperationInterface $operation
     * @return int
     * @throws NoWalletFoundException
     * @throws UnconfirmedBankAccountException if the bank account
     *                                         information is not the the status validated at HiPay
     * @throws UnidentifiedWalletException if the account is not identified by HiPay
     * @throws WrongWalletBalance if the hipay wallet balance is
     *                                         lower than the transaction amount to be sent to the bank account
     */
    public function withdrawOperation(OperationInterface $operation)
    {
        $vendor = $this->getVendor($operation);

        if (!$vendor || $this->hipay->isAvailable($vendor->getEmail())) {
            throw new NoWalletFoundException($vendor);
        }

        if (!$this->hipay->isIdentified($vendor)) {
            throw new UnidentifiedWalletException($vendor);
        }

        $bankInfoStatus = $this->hipay->bankInfosStatus($vendor);

        if ($this->hipay->bankInfosStatus($vendor)
            != BankInfoStatus::VALIDATED
        ) {
            throw new UnconfirmedBankAccountException(
                $vendor,
                new BankInfoStatus($bankInfoStatus)
            );
        }

        //Check account balance
        $amount = round(($operation->getAmount()), 2);
        $balance = round($this->hipay->getBalance($vendor), 2);
        if ($balance < $amount) {
            //Operator operation
            if (!$operation->getMiraklId()) {
                $amount = $balance;
                //Vendor operation
            } else {
                throw new WrongWalletBalance(
                    $vendor,
                    $amount,
                    $balance
                );
            }
        }

        $operation->setHiPayId($vendor->getHiPayId());

        //Withdraw
        return $this->hipay->withdraw(
            $vendor,
            $amount,
            $this->operationManager->generateWithdrawLabel($operation)
        );
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
}
