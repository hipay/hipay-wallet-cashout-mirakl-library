<?php

namespace HiPay\Wallet\Mirakl\Cashout;

use DateTime;
use Exception;
use HiPay\Wallet\Mirakl\Api\Factory;
use HiPay\Wallet\Mirakl\Api\HiPay;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\Transfer;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\BankInfo as BankInfoStatus;
use HiPay\Wallet\Mirakl\Cashout\Event\OperationEvent;
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
class Processor extends AbstractApiProcessor
{
    const SCALE = 2;

    /** @var  OperationManager */
    protected $operationManager;

    /** @var  VendorManager */
    protected $vendorManager;

    /** @var VendorInterface */
    protected $operator;

    /**
     * @var FormatNotification class
     */
    protected $formatNotification;

    /** @var  LogOperationsManager */
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
    EventDispatcherInterface $dispatcher, LoggerInterface $logger, Factory $factory, OperationManager $operationManager,
    VendorManager $vendorManager, VendorInterface $operator, LogOperationsManager $logOperationsManager
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
        $this->logger->info('Control Mirakl Settings', array('miraklId' => null, "action" => "Operations process"));
        // control mirakl settings
        $boolControl = $this->getControlMiraklSettings($this->documentTypes);
        if ($boolControl === false) {
            // log critical
            $title   = $this->criticalMessageMiraklSettings;
            $message = $this->formatNotification->formatMessage($title);
            $this->logger->critical($message, array('miraklId' => null, "action" => "Operations process"));
        } else {
            $this->logger->info('Control Mirakl Settings OK',
                                array('miraklId' => null, "action" => "Operations process"));
        }

        $this->logger->info("Cashout Processor", array('miraklId' => null, "action" => "Operations process"));

        //Transfer
        $this->transferOperations();

        //Withdraw
        $this->withdrawOperations();
    }

    /**
     * Execute the operation needing transfer.
     */
    protected function transferOperations()
    {
        $this->logger->info("Transfer operations", array('miraklId' => null, "action" => "Transfer"));

        $toTransfer = $this->getTransferableOperations();

        $this->logger->info("Operation to transfer : ".count($toTransfer),
                                                             array('miraklId' => null, "action" => "Transfer"));

        /** @var OperationInterface $operation */
        foreach ($toTransfer as $operation) {
            try {
                $eventObject = new OperationEvent($operation);

                $this->dispatcher->dispatch('before.transfer', $eventObject);

                $transferId = $this->transfer($operation);

                $eventObject->setTransferId($transferId);
                $this->dispatcher->dispatch('after.transfer', $eventObject);

                $this->logger->info("[OK] Transfer operation ".$operation->getTransferId()." executed",
                                    array('miraklId' => $operation->getMiraklId(), "action" => "Transfer"));
            } catch (Exception $e) {
                $this->logger->info("[OK] Transfer operation failed",
                                    array('miraklId' => $operation->getMiraklId(), "action" => "Transfer"));
                $this->handleException($e, 'critical');
            }
        }
    }

    /**
     * Execute the operation needing withdrawal.
     *
     */
    protected function withdrawOperations()
    {
        $this->logger->info("Withdraw operations", array('miraklId' => null, "action" => "Withdraw"));

        $toWithdraw = $this->getWithdrawableOperations();

        $this->logger->info("Operation to withdraw : ".count($toWithdraw),
                                                             array('miraklId' => null, "action" => "Withdraw"));

        /** @var OperationInterface $operation */
        foreach ($toWithdraw as $operation) {
            try {
                //Create the operation event object
                $eventObject = new OperationEvent($operation);

                //Dispatch the before.withdraw event
                $this->dispatcher->dispatch('before.withdraw', $eventObject);

                //Execute the withdrawal
                $withdrawId = $this->withdraw($operation);

                //Dispatch the after.withdraw
                $eventObject->setWithdrawId($withdrawId);
                $this->dispatcher->dispatch('after.withdraw', $eventObject);

                //Set operation new data
                $this->logger->info("[OK] Withdraw operation ".$operation->getWithdrawId()." executed",
                                    array('miraklId' => $operation->getMiraklId(), "action" => "Withdraw"));
            } catch (Exception $e) {
                $this->logger->info("[OK] Withdraw operation failed",
                                    array('miraklId' => $operation->getMiraklId(), "action" => "Withdraw"));
                $this->handleException($e, 'critical',
                                       array('miraklId' => $operation->getMiraklId(), "action" => "Withdraw"));
            }
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
     * @throws Exception
     */
    public function transfer(OperationInterface $operation)
    {
        try {
            $vendor = $this->getVendor($operation);

            if (!$vendor || $this->hipay->isAvailable($vendor->getEmail())) {
                throw new WalletNotFoundException($vendor);
            }

            $operation->setHiPayId($vendor->getHiPayId());

            $transfer = new Transfer(
                round($operation->getAmount(), self::SCALE), $vendor,
                      $this->operationManager->generatePrivateLabel($operation),
                                                                    $this->operationManager->generatePublicLabel($operation)
            );


            //Transfer
            $transferId = $this->hipay->transfer($transfer);

            $operation->setStatus(new Status(Status::TRANSFER_SUCCESS));
            $operation->setTransferId($transferId);
            $operation->setUpdatedAt(new DateTime());
            $this->operationManager->save($operation);

            $this->logOperation(
                $operation->getMiraklId(), $operation->getPaymentVoucher(), Status::TRANSFER_SUCCESS, ""
            );

            return $transferId;
        } catch (Exception $e) {
            $operation->setStatus(new Status(Status::TRANSFER_FAILED));
            $operation->setUpdatedAt(new DateTime());
            $this->operationManager->save($operation);

            $this->logOperation(
                $operation->getMiraklId(), $operation->getPaymentVoucher(), Status::TRANSFER_FAILED, $e->getMessage()
            );

            throw $e;
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
                new BankInfoStatus($bankInfoStatus), $operation->getMiraklId()
                );
            }

            //Check account balance
            $amount  = round(($operation->getAmount()), self::SCALE);
            $balance = round($this->hipay->getBalance($vendor), self::SCALE);
            if ($balance < $amount) {
                //Operator operation
                if (!$operation->getMiraklId()) {
                    $amount = $balance;
                    //Vendor operation
                } else {
                    throw new WrongWalletBalance(
                    $vendor->getMiraklId(), $amount, $balance
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
     * Control if Mirakl Setting is ok with HiPay prerequisites
     */
    public function getControlMiraklSettings($docTypes)
    {
        $this->mirakl->controlMiraklSettings($docTypes);
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
        $previousDay = new DateTime('-1 day');

        $toWithdraw = $this->operationManager->findByStatus(
            new Status(Status::TRANSFER_SUCCESS)
        );
        $toWithdraw = array_merge(
            $toWithdraw,
            $this->operationManager
                ->findByStatusAndBeforeUpdatedAt(
                    new Status(Status::WITHDRAW_FAILED), $previousDay
                )
        );
        return $toWithdraw;
    }

    /**
     * Fetch the operation to transfer from the storage
     * @return OperationInterface[]
     */
    protected function getTransferableOperations()
    {
        $previousDay = new DateTime('-1 day');
        //Transfer
        $toTransfer  = $this->operationManager->findByStatus(
            new Status(Status::CREATED)
        );
        $toTransfer  = array_merge(
            $toTransfer,
            $this->operationManager
                ->findByStatusAndBeforeUpdatedAt(
                    new Status(Status::TRANSFER_FAILED), $previousDay
                )
        );
        return $toTransfer;
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
        }

        switch ($status) {
            case Status::WITHDRAW_FAILED :
            case Status::WITHDRAW_REQUESTED :
                $logOperation->setStatusWithDrawal($status);
                break;
            case Status::TRANSFER_FAILED :
            case Status::TRANSFER_SUCCESS :
                $logOperation->setStatusTransferts($status);
                break;
        }

        $logOperation->setMessage($message);

        $this->logOperationsManager->save($logOperation);
    }
}