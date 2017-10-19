<?php

namespace HiPay\Wallet\Mirakl\Cashout;

use HiPay\Wallet\Mirakl\Common\AbstractApiProcessor;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\ManagerInterface as OperationManager;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Service\Validation\ModelValidator;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorManagerInterface as VendorManager;
use HiPay\Wallet\Mirakl\Notification\Model\LogOperationsManagerInterface as LogOperationsManager;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;

/**
 * Generate and save the operation to be executed by the processor.
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
class Transfer extends AbstractApiProcessor
{
    const SCALE = 2;

    /** @var VendorInterface */
    protected $technicalAccount;

    /** @var OperationManager */
    protected $operationManager;

    /** @var  VendorManager */
    protected $vendorManager;

    /** @var  LogOperationsManager */
    protected $logOperationsManager;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        Factory $factory,
        VendorInterface $technicalAccount,
        OperationManager $operationHandler,
        LogOperationsManager $logOperationsManager,
        VendorManager $vendorManager)
    {

        parent::__construct($dispatcher, $logger, $factory);
        
        ModelValidator::validate($technicalAccount, 'Operator');
        
        $this->technicalAccount = $technicalAccount;

        $this->operationManager = $operationHandler;

        $this->vendorManager = $vendorManager;

        $this->logOperationsManager = $logOperationsManager;
    }

    public function process()
    {

        $this->logger->info("Transfer operations", array('miraklId' => null, "action" => "Transfer"));

        $toTransfer = $this->getTransferableOperations();

        $this->transferOperations($toTransfer);

        $this->logger->info("Operation to transfer : ".count($toTransfer),
                                                             array('miraklId' => null, "action" => "Transfer"));
    }

    public function transferOperations(array $operations)
    {
        foreach ($operations as $operation) {
            try {

                if($this->hasSufficientFunds($operation->getAmount())){
                    $this->transfer($operation);
                    $this->logger->info(
                        "[OK] Transfer operation ".$operation->getTransferId()." executed",
                        array('miraklId' => $operation->getMiraklId(), "action" => "Transfer")
                        );
                }else{
                    $this->logger->warning(
                        "[KO] Insufficient Funds for operation ".$operation->getTransferId(),
                        array('miraklId' => $operation->getMiraklId(), "action" => "Transfer")
                        );

                    $operation->setStatus(new Status(Status::TRANSFER_NEGATIVE));
                    $operation->setUpdatedAt(new DateTime());
                    $this->operationManager->save($operation);
                }

            } catch (Exception $e) {
                $this->logger->info(
                    "[OK] Transfer operation failed",
                    array('miraklId' => $operation->getMiraklId(), "action" => "Transfer")
                    );
                $this->handleException($e, 'critical');
            }
        }
    }

    public function transfer(OperationInterface $operation){
        try {
            $vendor = $this->getVendor($operation);

            if (!$vendor || $this->hipay->isAvailable($vendor->getEmail())) {
                throw new WalletNotFoundException($vendor);
            }

            $operation->setHiPayId($vendor->getHiPayId());

            $transfer = new Transfer(
                round($operation->getAmount(), self::SCALE),
                $vendor,
                $this->operationManager->generatePrivateLabel($operation),
                $this->operationManager->generatePublicLabel($operation)
            );

            //Transfer
            $transferId = $this->hipay->transfer($transfer, $vendor);

            $operation->setStatus(new Status(Status::TRANSFER_SUCCESS));
            $operation->setTransferId($transferId);
            $operation->setUpdatedAt(new DateTime());
            $this->operationManager->save($operation);

            $this->logOperation(
                $operation->getMiraklId(),
                $operation->getPaymentVoucher(),
                Status::TRANSFER_SUCCESS,
                ""
            );

            return $transferId;
        } catch (Exception $e) {
            $operation->setStatus(new Status(Status::TRANSFER_FAILED));
            $operation->setUpdatedAt(new DateTime());
            $this->operationManager->save($operation);

            $this->logOperation(
                $operation->getMiraklId(),
                $operation->getPaymentVoucher(),
                Status::TRANSFER_FAILED,
                $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * Fetch the operation to transfer from the storage
     * @return OperationInterface[]
     */
    protected function getTransferableOperations()
    {
        $previousDay       = new DateTime('-1 day');
        //Transfer
        $toTransferCreated = $this->operationManager->findByStatus(
            new Status(Status::CREATED)
        );

        $toTransferFailed = $this->operationManager->findByStatusAndBeforeUpdatedAt(
            new Status(Status::TRANSFER_FAILED), $previousDay
        );

        $toTransferNegative = $this->operationManager->findByStatus(
            new Status(Status::TRANSFER_NEGATIVE)
        );

        $toTransfer = array_merge($toTransferNegative, $toTransferFailed, $toTransferCreated);

        return $toTransfer;
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
     * Log Operations
     * @param type $miraklId
     * @param type $paymentVoucherNumber
     * @param type $status
     * @param type $message
     */
    private function logOperation($miraklId, $paymentVoucherNumber, $status, $message)
    {
        $logOperation = $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber($miraklId, $paymentVoucherNumber);

        if ($logOperation == null) {
            $this->logger->warning(
                "Could not find existing log for this operations : paymentVoucherNumber = ".$paymentVoucherNumber,
                array("action" => "Operation process", "miraklId" => $miraklId)
            );
        } else {

            $logOperation->setStatusTransferts($status);

            $logOperation->setMessage($message);

            $this->logOperationsManager->save($logOperation);
        }
    }

    /**
     * Check if technical account has sufficient funds.
     *
     * @param $amount
     *
     * @returns boolean
     */
    public function hasSufficientFunds($amount)
    {
        return round($this->hipay->getBalance($this->technicalAccount), static::SCALE) >= round($amount, static::SCALE);
    }
}