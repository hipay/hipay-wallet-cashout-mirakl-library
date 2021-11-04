<?php

namespace HiPay\Wallet\Mirakl\Cashout;

use DateTime;
use Exception;
use HiPay\Wallet\Mirakl\Api\Factory;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\ManagerInterface as OperationManager;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\Status;
use HiPay\Wallet\Mirakl\Cashout\AbstractOperationProcessor;
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

        foreach ($operations as $op){
            var_dump($op->getTransferId());
            $transactinInfo = $this->hipay->getTransaction($op->getTransferId());
            var_dump($transactinInfo["transaction_status"]);
        }

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
