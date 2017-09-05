<?php

namespace HiPay\Wallet\Mirakl\Notification\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represent an entity that is able to receive money from HiPay
 * Uses Symfony Validation assertion to ensure basic data integrity.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
interface LogOperationsInterface
{
    /**
     * @return int|null if operator
     *
     * @Assert\NotBlank(groups={"Default"})
     * @Assert\Type(type="integer", groups={"Default"})
     * @Assert\GreaterThan(value = 0, groups={"Default"})
     * @Assert\IsNull(groups={"Operator"})
     */
    public function getMiraklId();

    /**
     * @param int $id|null if operator
     *
     * @return void
     */
    public function setMiraklId($id);

    /**
     * @return int
     */
    public function getHiPayId();

    /**
     * @param int $id
     *
     * @return void
     */
    public function setHiPayId($id);

    /**
     * @return string
     */
    public function getAmount();

    /**
     * @param string $amount
     *
     * @return void
     */
    public function setAmount($amount);

    /**
     * @return string
     */
    public function getStatusTransferts();

    /**
     * @param string $statusTransferts
     *
     * @return void
     */
    public function setStatusTransferts($statusTransferts);

    /**
     * @return string
     */
    public function getStatusWithDrawal();

    /**
     * @param string $statusWithDrawal
     *
     * @return void
     */
    public function setStatusWithDrawal($statusWithDrawal);

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @param string $message
     *
     * @return void
     */
    public function setMessage($message);

    /**
     * @return string
     */
    public function getBalance();

    /**
     * @param string $balance
     *
     * @return void
     */
    public function setBalance($balance);
}
