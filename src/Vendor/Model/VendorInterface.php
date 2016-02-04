<?php

namespace HiPay\Wallet\Mirakl\Vendor\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Interface VendorInterface
 * Represent an entity that is able to receive money from HiPay
 * Uses Symfony Validation assertion to ensure basic data integrity.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
interface VendorInterface
{
    /**
     * @Assert\NotBlank(group={"Default"})
     * @Assert\Type(type="integer", group={"Default"})
     * @Assert\GreaterThan(value = 0, group={"Default"})
     * @Assert\IsNull(group={"Operator"})
     * @return int
     */
    public function getMiraklId();

    /**
     * @param int $id
     * @return void
     */
    public function setMiraklId($id);

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     * @Assert\Email
     *
     * @return string
     */
    public function getEmail();

    /**
     * @param string $email
     *
     * @return void
     */
    public function setEmail($email);

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     * @Assert\GreaterThan(value = 0)
     *
     * @return int
     */
    public function getHiPayId();

    /**
     * @param int $id
     *
     * @return void
     */
    public function setHiPayId($id);
}
