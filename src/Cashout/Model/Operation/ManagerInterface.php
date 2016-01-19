<?php
namespace Hipay\MiraklConnector\Cashout\Model\Operation;

use DateTime;

/**
 * Interface ManagerInterface
 * @package Hipay\MiraklConnector\Cashout\Model\Operation
 */
interface ManagerInterface
{

    /**
     * Save a batch of operation
     *
     * @param OperationInterface[] $operation
     *
     * @return bool
     */
    public function saveAll(array $operation);

    /**
     * Save a single operation
     *
     * @param OperationInterface $operation
     *
     * @return bool
     */
    public function save($operation);

    /**
     * Create an operation
     *
     * @param int $shopId|false if it is an operator operation
     *
     * @return OperationInterface
     */
    public function create($shopId);

    /**
     * Check if an operation is savable
     *
     * @param OperationInterface $operation
     *
     * @return bool
     */
    public function isSavable(OperationInterface $operation);

    /**
     * Finds operations
     *
     * @param Status $status status to filter upon
     * @param DateTime $date optional date to filter upon
     *
     * @return OperationInterface[]
     */
    public function findByStatusAndCycleDate(
        Status $status,
        DateTime $date = null
    );

    /**
     * Find an operation by transactionId
     *
     * @param $transactionId
     * @return OperationInterface
     */
    public function findByTransactionId($transactionId);
}