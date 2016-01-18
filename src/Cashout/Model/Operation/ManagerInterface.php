<?php
namespace Hipay\MiraklConnector\Cashout\Model\Operation;

use DateTime;

/**
 * Interface HandlerInterface
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
     * @param $vendorAmount
     * @param $shopId
     * @param DateTime $startDate
     * @param DateTime $endDate
     *
     * @return OperationInterface
     */
    public function create(
        $vendorAmount,
        $shopId,
        DateTime $startDate,
        DateTime $endDate
    );

    /**
     * Check if an operation is saveable
     *
     * @param OperationInterface $operation
     *
     * @return bool
     */
    public function isSaveable(OperationInterface $operation);

    /**
     * Finds operations
     *
     * @param Status $status status to filter upon
     * @param DateTime $date optional date to filter upon
     *
     * @return OperationInterface[]
     */
    public function find(Status $status, DateTime $date = null);
}