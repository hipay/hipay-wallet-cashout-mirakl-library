<?php

namespace HiPay\Wallet\Mirakl\Cashout;

use Exception;
use HiPay\Wallet\Mirakl\Api\Factory;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\TransferStatus;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\WithdrawStatus;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\ManagerInterface as OperationManager;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\Status;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorManagerInterface as VendorManager;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use HiPay\Wallet\Mirakl\Notification\FormatNotification;
use HiPay\Wallet\Mirakl\Notification\Model\LogOperationsManagerInterface as LogOperationsManager;

/**
 * Process withdraw
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
class TransactionStatus extends AbstractOperationProcessor
{
    protected $formatNotification;

    private $transferValidatedStatus = array(TransferStatus::CAPTURED);

    private $withdrawValidatedStatus = array(WithdrawStatus::AUTHED);

    /**
     * Withdraw constructor.
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param Factory $factory
     * @param OperationManager $operationManager
     * @param VendorManager $vendorManager
     * @param VendorInterface $operator
     * @param LogOperationsManager $logOperationsManager
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
     * Process withdraw
     */
    public function process()
    {
        $this->logger->info("Sync Transaction status", array('miraklId' => null, "action" => "transactionSync"));

        $operations = $this->getSyncableOperations();

        $this->logger->info("Operation to sync : " . sizeof($operations),
            array('miraklId' => null, "action" => "transactionSync"));

        foreach ($operations as $op) {
            $this->syncStatus($op);
        }

    }

    private function syncStatus(OperationInterface $operation)
    {
        if ($operation->getStatus() === Status::TRANSFER_REQUESTED) {
            $this->setNewStatus(
                $operation->getTransferId(),
                $operation,
                Status::TRANSFER_SUCCESS,
                Status::TRANSFER_FAILED,
                $this->transferValidatedStatus
            );
        } elseif ($operation->getStatus() === Status::WITHDRAW_REQUESTED) {
            $this->setNewStatus(
                $operation->getWithdrawId(),
                $operation,
                Status::WITHDRAW_SUCCESS,
                Status::WITHDRAW_FAILED,
                $this->withdrawValidatedStatus,
                $operation->getHiPayId()
            );
        }
    }

    private function setNewStatus(
        $operationId,
        $operation,
        $successStatus,
        $failStatus,
        $validatedStatus,
        $accountId = null
    ) {
        $this->logger->info(
            "Sync Transaction " . $operationId,
            array('miraklId' => null, "action" => "transactionSync")
        );

        try {
            $transactionInfo = $this->hipay->getTransaction($operationId, $accountId);

            $this->logger->info(
                "Transaction status : " . $transactionInfo["transaction_status"],
                array('miraklId' => null, "action" => "transactionSync")
            );

            if (in_array($transactionInfo["transaction_status"], $validatedStatus)) {
                $this->setStatus($operation, $successStatus);
                $this->logger->info(
                    "New status : " . $successStatus,
                    array('miraklId' => null, "action" => "transactionSync")
                );
            } else {
                $this->setStatus($operation, $failStatus);
                $this->logger->info(
                    "New status : " . $failStatus,
                    array('miraklId' => null, "action" => "transactionSync")
                );
            }
        } catch (Exception $e) {
            $this->logger->critical(
                "[KO] Status Sync operation failed",
                array('miraklId' => $operation->getMiraklId(), "action" => "transactionSync")
            );
            $this->handleException(
                $e,
                'critical',
                array('miraklId' => $operation->getMiraklId(), "action" => "transactionSync")
            );
        }
    }

    private function setStatus($operation, $status)
    {
        $operation->setStatus(new Status($status));
        $this->operationManager->save($operation);

        $this->logOperation(
            $operation->getMiraklId(),
            $operation->getPaymentVoucher(),
            $status,
            ""
        );
    }


    /**
     * Fetch the operation to sync from the storage
     *
     * @return OperationInterface[]
     */
    protected function getSyncableOperations()
    {

        $withdrawRequested = $this->operationManager->findByStatus(new Status(Status::WITHDRAW_REQUESTED));

        $transferRequested = $this->operationManager->findByStatus(new Status(Status::TRANSFER_REQUESTED));

        return array_merge($withdrawRequested, $transferRequested);
    }
}
