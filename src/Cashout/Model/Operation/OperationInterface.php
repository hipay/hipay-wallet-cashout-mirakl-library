<?php
namespace Hipay\MiraklConnector\Cashout\Model\Operation;

use DateTime;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Operation interface
 * You must implement this class to use the library
 * Uses Symfony Validation assertion to ensure basic data integrity
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

/**
 * Interface OperationInterface
 * @package Hipay\MiraklConnector\Cashout\Model\Operation
 *
 */
interface OperationInterface
{
    /**
     * @return int|false if it is an operator operation
     */
    public function getMiraklId();

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
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
     * @return void
     */
    public function setWithdrawId($withdrawId);

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     *
     * @return int
     */
    public function getStatus();

    /**
     * @param Status $status
     * @return void
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
     * @return void
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
     * @return void
     */
    public function setAmount($amount);
}