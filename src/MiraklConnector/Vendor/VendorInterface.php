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


use DateTime;

/**
 * Interface Payable
 * Represent an entity that is able to receive money from Hipay
 *
 * @package Mirakl\Hipay\Vendor
 */
interface VendorInterface
{
    /**
     * Return the Mirakl shop id
     *
     * @return string
     */
    public function getMiraklShopId();

    /**
     * Returns the date of the last modification
     *
     * @return DateTime
     */
    public function getLastModification();

    /**
     * Returns the email recored by hipay
     *
     * @return string
     */
    public function getEmail();

    /**
     * @return string
     */
    public function getHipayAccountId();

    /**
     * Returns the Hipay login
     *
     * @return string
     */
    public function getHipayLogin();
}