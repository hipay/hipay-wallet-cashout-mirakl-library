<?php

namespace HiPay\Wallet\Mirakl\Vendor\Model;

/**
 * Interface VendorManager
 * Contains utility methods to create, save and find vendors
 * To be implemented by the integrator of the library.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
interface ManagerInterface
{
    /**
     * Create a vendor
     * Do not save it.
     *
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
