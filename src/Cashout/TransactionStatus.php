<?php

namespace HiPay\Wallet\Mirakl\Cashout;

use Exception;
use HiPay\Wallet\Mirakl\Api\Factory;
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
        $this->logger->info("Sync Transaction status", array('miraklId' => null, "action" => "Withdraw"));

        $operations = $this->getSyncableOperations();

        foreach ($operations as $op) {
            $this->syncStatus($op);
        }

    }

    private function syncStatus(OperationInterface $operation)
    {
        var_dump($operation->getTransferId());
        if ($operation->getStatus() === Status::TRANSFER_REQUESTED) {
            $transactionInfo = $this->hipay->getTransaction($operation->getTransferId());
            if ($transactionInfo["transaction_status"]) {
                $this->setStatus($operation, Status::TRANSFER_SUCCESS);
            } else {
                $this->setStatus($operation, Status::TRANSFER_FAILED);
            }
        } else {
            $transactionInfo = $this->hipay->getTransaction($operation->getWithdrawId(), $operation->getHiPayId());
            if ($transactionInfo["transaction_status"]) {
                $this->setStatus($operation, Status::WITHDRAW_SUCCESS);
            } else {
                $this->setStatus($operation, Status::WITHDRAW_FAILED);
            }
        }

        var_dump($transactionInfo["transaction_status"]);
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
