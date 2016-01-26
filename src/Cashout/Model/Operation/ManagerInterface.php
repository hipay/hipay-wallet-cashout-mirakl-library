<?php

namespace Hipay\MiraklConnector\Cashout\Model\Operation;

use DateTime;
use Hipay\MiraklConnector\Vendor\Model\VendorInterface;

/**
 * Interface ManagerInterface.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
interface ManagerInterface
{
    /**
     * Save a batch of operation.
     *
     * @param OperationInterface[] $operation
     *
     * @return bool
     */
    public function saveAll(array $operation);

    /**
     * Save a single operation.
     *
     * @param OperationInterface $operation
     *
     * @return bool
     */
    public function save($operation);

    /**
     * Create an operation.
     *
     * @param float $amount
     * @param DateTime $cycleDate
     * @param int $miraklId
     * @param VendorInterface $vendor
     * @return OperationInterface
     *
     */
    public function create($amount, DateTime $cycleDate, $miraklId = null, VendorInterface $vendor = null);

    /**
     * Check if an operation is valid.
     *
     * @param OperationInterface $operation
     *
     * @return bool
     */
    public function isValid(OperationInterface $operation);

    /**
     * Finds operations.
     *
     * @param Status   $status      status to filter upon
     * @param DateTime $maximumDate maximum date to filter
     *
     * @return OperationInterface[]
     */
    public function findByStatusAndCycleDate(
        Status $status,
        DateTime $maximumDate
    );

    /**
     * Finds operations.
     *
     * @param Status $status status to filter upon
     *
     * @return OperationInterface[]
     */
    public function findByStatus(Status $status);

    /**
     * Finds an operation.
     *
     * @param int      $miraklId|false if operator
     * @param DateTime $date          optional date to filter upon
     *
     * @return OperationInterface|null
     */
    public function findByMiraklIdAndCycleDate(
        $miraklId,
        DateTime $date
    );

    /**
     * Find an operation by transactionId.
     *
     * @param $withdrawalId
     *
     * @return OperationInterface|null
     */
    public function findByWithdrawalId($withdrawalId);
}
