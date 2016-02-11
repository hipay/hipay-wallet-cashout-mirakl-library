<?php

namespace HiPay\Wallet\Mirakl\Cashout\Model\Operation;

use DateTime;

/**
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
     * @param string $paymentVoucher
     * @param int $miraklId
     *
     * @return OperationInterface
     */
    public function create(
        $amount,
        DateTime $cycleDate,
        $paymentVoucher,
        $miraklId = null
    );

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
     * @param DateTime $maximumDate date for filtering
     *
     * @return OperationInterface[]
     */
    public function findByStatusAndBeforeUpdatedAt(
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
     * @param int $miraklId|null if operator
     * @param int $paymentVoucherNumber optional date to filter upon
     *
     * @return OperationInterface|null
     */
    public function findByMiraklIdAndPaymentVoucherNumber(
        $miraklId,
        $paymentVoucherNumber
    );

    /**
     * Find an operation by transactionId.
     *
     * @param $withdrawalId
     *
     * @return OperationInterface|null
     */
    public function findByWithdrawalId($withdrawalId);

    /**
     * Generate the public label of a transfer operation
     *
     * @param OperationInterface $operation
     *
     * @return string
     */
    public function generatePublicLabel(OperationInterface $operation);

    /**
     * Generate the private label of a transfer operation
     *
     * @param OperationInterface $operation
     *
     * @return string
     */
    public function generatePrivateLabel(OperationInterface $operation);

    /**
     * Generate the label of a withdraw operation
     *
     * @param OperationInterface $operation
     *
     * @return string
     */
    public function generateWithdrawLabel(OperationInterface $operation);
}
