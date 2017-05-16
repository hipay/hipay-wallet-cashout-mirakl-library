<?php

namespace HiPay\Wallet\Mirakl\Vendor\Model;

/**
 * Vendor processor handling the wallet creation
 * and the bank info registration and verification.
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
interface VendorManagerInterface
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
     * @param $callbackSalt
     * @param array $miraklData
     *
     * @return VendorInterface
     */
    public function create(
        $email,
        $miraklId,
        $hipayId,
        $hipayUserSpaceId,
        $identified,
        $vatNumber,
        $callbackSalt,
        array $miraklData
    );

    /**
     * Insert more data into the vendor.
     * Do not save it.
     *
     * @param VendorInterface $vendor
     * @param array           $miraklData
     */
    public function update(
        VendorInterface $vendor,
        array $miraklData
    );

    /**
     * Save an array of vendors.
     *
     * @param VendorInterface[] $vendors
     *
     * @return mixed
     */
    public function saveAll(array $vendors);

    /**
     * Save a vendor.
     *
     * @param VendorInterface $vendor
     *
     * @return mixed
     */
    public function save($vendor);

    /**
     * Find a vendor by is mirakl shop id.
     *
     * @param int $miraklShopId
     *
     * @return VendorInterface|null if not found
     */
    public function findByMiraklId($miraklShopId);

    /**
     * Find a vendor by its email.
     *
     * @param string $email
     *
     * @return VendorInterface|null if not found
     */
    public function findByEmail($email);

    /**
     * Find a vendor by its hipay wallet id.
     *
     * @param $hipayId
     *
     * @return VendorInterface|null if not found
     */
    public function findByHiPayId($hipayId);

    /**
     * Verify that a vendor is valid before save.
     *
     * @param $vendor
     *
     * @return bool
     */
    public function isValid(VendorInterface $vendor);
}
