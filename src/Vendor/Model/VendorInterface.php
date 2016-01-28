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
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     * @Assert\GreaterThan(value = 0)
     *
     * @return int
     */
    public function getMiraklId();

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     * @Assert\Email
     *
     * @return string
     */
    public function getEmail();

    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     * @Assert\GreaterThan(value = 0)
     *
     * @return int
     */
    public function getHiPayId();
}
