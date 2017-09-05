<?php

namespace HiPay\Wallet\Mirakl\Notification\Model;

/**
 * 2017 HiPay
 *
 * NOTICE OF LICENSE
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2016 HiPay
 * @license   https://github.com/hipay/hipay-wallet-cashout-mirakl-integration/blob/master/LICENSE.md
 */
interface LogGeneralManagerInterface
{
    /**
     * Create a vendor
     * Do not save it.
     *
     * @param $email
     * @param $miraklId
     * @param $hipayId
     * @param $hipayUserSpaceId
     * @param $identified
     * @param $vatNumber
     * @param array $miraklData
     *
     * @return VendorInterface
     */
    public function create(
        $miraklId,
        $type,
        $action,
        $message,
        $date
    );

    /**
     * Insert more data into the vendor.
     * Do not save it.
     *
     * @param VendorInterface $vendor
     * @param array           $miraklData
     */
    public function update(
        LogGeneralInterface $logGeneral
    );

    /**
     * Save an array of vendors.
     *
     * @param VendorInterface[] $vendors
     *
     * @return mixed
     */
    public function saveAll(array $logGeneral);

    /**
     * Save a vendor.
     *
     * @param VendorInterface $vendor
     *
     * @return mixed
     */
    public function save($logGeneral);

    /**
     * Find a vendor by is mirakl shop id.
     *
     * @param int $miraklShopId
     *
     * @return VendorInterface|null if not found
     */
    public function findByMiraklId($miraklShopId);
    

    /**
     * Verify that a vendor is valid before save.
     *
     * @param $vendor
     *
     * @return bool
     */
    public function isValid(LogGeneralInterface $logGeneral);

}
