<?php

namespace HiPay\Wallet\Mirakl\Cashout\Model\Operation;

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
 *
 * @package HiPay\Wallet\Mirakl\Cashout\Model\Operation
 */
interface OperationInterface
{
    /**
     * @return int|null if it is an operator operation
     */
    public function getMiraklId();

    /**
     * @param $miraklId
     *
     * @return void
     */
    public function setMiraklId($miraklId);

    /**
     * @return int|null if the vendor didn't have its data in the db at the creation of the operation
     *
     * @Assert\Type(type="integer")
     */
    public function getHiPayId();

    /**
     * @param $hipayId
     *
     * @return void
     */
    public function setHiPayId($hipayId);

    /**
     * @return string
     */
    public function getWithdrawId();

    /**
     * @param int $withdrawId
     */
    public function setWithdrawId($withdrawId);

    /**
     * @return float
     */
    public function getWithdrawnAmount();

    /**
     * @param $amount
     * @return void
     */
    public function setWithdrawnAmount($amount);

    /**
     * @return string
     *
     */
    public function getTransferId();

    /**
     * @param $transferId
     */
    public function setTransferId($transferId);

    /**
     * @return int
     *
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     */
    public function getStatus();

    /**
     * Set the status
     * For information, the method Status::getValue return the int to set
     * @param Status $status
     */
    public function setStatus(Status $status);

    /**
     * @return DateTime
     *
     * @Assert\NotBlank()
     * @Assert\DateTime()
     * @Assert\LessThanOrEqual("now")
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
     * @Assert\GreaterThanOrEqual(value = 0)
     * @Assert\Type(type="float")
     */
    public function getAmount();

    /**
     * @param float $amount
     */
    public function setAmount($amount);

    /**
     * @return DateTime
     *
     * @Assert\NotBlank()
     * @Assert\DateTime()
     * @Assert\LessThanOrEqual("now")
     */
    public function getUpdatedAt();

    /**
     * @param DateTime $dateTime
     *
     * @return void
     */
    public function setUpdatedAt(DateTime $dateTime);

    /**
     * @return string
     *
     * @Assert\NotBlank()
     * @Assert\Regex("/^[0-9]{6}$/")
     * @Assert\Type("string")
     */
    public function getPaymentVoucher();

    /**
     * @param $paymentVoucher
     *
     * @return void
     */
    public function setPaymentVoucher($paymentVoucher);
}
