<?php
/**
 * File VendorManager.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Vendor;


/**
 * Interface VendorManager
 * @package Hipay\MiraklConnector\Vendor
 */
interface VendorManager
{
    /**
     * @param $email
     * @param $miraklId
     * @param $hipayId
     * @param array $miraklData
     *
     * @return VendorInterface
     */
    public function create(
        $email,
        $miraklId,
        $hipayId,
        array $miraklData = array()
    );

    /**
     * @param $vendor
     * @param array $miraklData
     *
     * @return void
     */
    public function update(
        $vendor,
        array $miraklData
    );
    /**
     * @param array $vendors
     * @return mixed
     */
    public function saveAll(array $vendors);

    /**
     * @param $shopId
     * @return VendorInterface|null if not found
     */
    public function findByMiraklId($shopId);

    /**
     * @param $shopId
     * @return VendorInterface|null if not found
     */
    public function findByHipayId($shopId);

    public function handleException();
}