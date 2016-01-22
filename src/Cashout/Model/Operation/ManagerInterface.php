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
     * Check if an operation is valid
     *
     * @param OperationInterface $operation
     *
     * @return bool
     */
    public function isValid(OperationInterface $operation);

    /**
     * Finds operations
     *
     * @param Status $status status to filter upon
     * @param DateTime $maximumDate maximum date to filter
     *
     *
     * @return OperationInterface[]
     */
    public function findByStatusAndCycleDate(
        Status $status,
        DateTime $maximumDate
    );

    /**
     * Finds operations
     *
     * @param Status $status status to filter upon
     *
     * @return OperationInterface[]
     */
    public function findByStatus(Status $status);

    /**
     * Finds an operation
     *
     * @param int $hipayId|false if operator
     * @param DateTime $date optional date to filter upon
     *
     * @return OperationInterface|null
     */
    public function findByHipayIdAndCycleDate(
        $hipayId,
        DateTime $date
    );

    /**
     * Find an operation by transactionId
     *
     * @param $withdrawalId
     *
     * @return OperationInterface|null
     */
    public function findByWithdrawalId($withdrawalId);
}