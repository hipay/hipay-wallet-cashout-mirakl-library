<?php

namespace Hipay\MiraklConnector\Cashout\Model\Operation;

use DateTime;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Operation interface
 * You must implement this class to use the library
 * Uses Symfony Validation assertion to ensure basic data integrity.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

/**
 * Interface OperationInterface
 *
 *
 * @Assert\Expression("is_int(this.getMiraklId()) || is_null(this.getMiraklId())")
 * @Assert\Expression("is_int(this.getHipayId()) || is_null(this.getMiraklId())")
 *
 * @package Hipay\MiraklConnector\Cashout\Model\Operation
 */
interface OperationInterface
{
    /**
     * @return int|null if it is an operator operation
     *
     */
    public function getMiraklId();

    /**
     * @return int|null if the vendor didn't have its data in the db at the creation of the operation
     *
     * @Assert\Expression("")
     */
    public function getHipayId();

    /**
     * @return int
     * @Assert\Type(type="integer")
     */
    public function getWithdrawId();

    /**
     * @param int $withdrawId
     */
    public function setWithdrawId($withdrawId);

    /**
     * @return int
     * @Assert\Type(type="integer")
     */
    public function getTransferId();

    /**
     * @param $transferId
     */
    public function setTransferId($transferId);

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     *
     * @return int
     */
    public function getStatus();

    /**
     * @param Status $status
     */
    public function setStatus(Status $status);

    /**
     * @return DateTime
     *
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    public function getCycleDate();

    /**
     * @param DateTime $date
     */
    public function setCycleDate(DateTime $date);

    /**
     * @return float
     *
     * @Assert\NotBlank()
     * @Assert\GreaterThan(value = 0)
     * @Assert\Type(type="float")
     */
    public function getAmount();

    /**
     * @param float $amount
     */
    public function setAmount($amount);

    /**
     * Set the hipay Id.
     *
     * @param $hipayId
     *
     * @return mixed
     */
    public function setHipayId($hipayId);

    /**
     * Set the mirakl id
     * @param $miraklId
     * @return mixed
     */
    public function setMiraklId($miraklId);
}
