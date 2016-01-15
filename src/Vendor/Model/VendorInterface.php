<?php
/**
 * File Payable.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Vendor;

/**
 * Interface VendorInterface
 * Represent an entity that is able to receive money from Hipay
 *
 * @package Mirakl\Hipay\Vendor
 */
interface VendorInterface
{
    /**
     * Return the Mirakl shop id
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     * @Assert\GreaterThan(value = 0)
     *
     * @return int
     */
    public function getMiraklId();

    /**
     * Returns the email recored by hipay
     *
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     * @Assert\Email
     *
     * @return string
     */
    public function getEmail();

    /**
     * Return the hipay account id
     *
     * @Assert\NotBlank()
     * @Assert\Type(type="integer")
     * @Assert\GreaterThan(value = 0)
     *
     * @return int
     */
    public function getHipayId();
}