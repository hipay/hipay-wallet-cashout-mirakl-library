<?php
/**
 * File Handler.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Cashout\Model\Operation;


use DateTime;

/**
 * Interface HandlerInterface
 * @package Hipay\MiraklConnector\Cashout\Model\Operation
 */
interface HandlerInterface
{

    /**
     * Save a operation
     *
     * @param OperationInterface $operation
     *
     * @return bool
     */
    public function save(OperationInterface $operation);

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
     * @param OperationInterface $operation
     * @return bool
     */
    public function isValid(OperationInterface $operation);
}