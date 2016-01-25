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
interface OperationInterface
{
    /**
     * @return int|false if it is an operator operation
     * @Assert\Type(type="integer")
     */
    public function getMiraklId();

    /**
     * @Assert\Type(type="integer")
     * @Assert\NotBlank()
     *
     * @return int
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
}
