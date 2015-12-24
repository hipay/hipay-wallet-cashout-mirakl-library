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
     * @param string $miraklShopId
     */
    public function setMiraklShopId($miraklShopId);


    /**
     * Returns the date of the last modification
     *
     * @return DateTime
     */
    public function getLastModifcation();
    /**
     * @param DateTime $lastModifcation
     */
    public function setLastModifcation($lastModifcation);

    /**
     * Returns the email recored by hipay
     *
     * @return string
     */
    public function getEmail();
    /**
     * @param string $email
     */
    public function setEmail($email);

    /**
     * @return string
     */
    public function getHipayAccountId();
    /**
     * Returns the hipay account Id
     *
     * @param string $hipayAccountId
     */
    public function setHipayAccountId($hipayAccountId);

    /**
     * Returns the Hipay login
     *
     * @return string
     */
    public function getHipayLogin();
    /**
     * @param string $hipayLogin
     */
    public function setHipayLogin($hipayLogin);

    /**
     * Save the vendor (in a database, file or anything you want)
     *
     * @return true if the vendor was saved, false otherwise
     */
    public function save();

    /**
     * Create the vendor from an array
     *
     * @param array $data
     *
     * @return self
     */
    public static function fromArray(array $data);
}