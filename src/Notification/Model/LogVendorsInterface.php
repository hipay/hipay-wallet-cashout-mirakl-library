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
interface LogVendorsInterface
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
     * @return int
     */
    public function getStatus();

    /**
     * @param int $id
     *
     * @return void
     */
    public function setStatus($id);

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @param string $string
     *
     * @return void
     */
    public function setMessage($string);

    /**
     * @return string
     */
    public function getNbDoc();

    /**
     * @param string $string
     *
     * @return void
     */
    public function setNbDoc($string);

    /**
     * @return DateTime
     */
    public function getDate();

    /**
     * @param DateTime $datetime
     *
     * @return DateTime
     */
    public function setDate(DateTime $datetime);

}
