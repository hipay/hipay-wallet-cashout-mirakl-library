<?php
namespace Hipay\MiraklConnector\Vendor\Model;

/**
 * Interface VendorManager
 * @package Hipay\MiraklConnector\Vendor
 */
interface ManagerInterface
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
     * Insert more data if you want
     *
     * @param VendorInterface $vendor
     * @param array $miraklData
     *
     * @return void
     */
    public function update(
        VendorInterface $vendor,
        array $miraklData
    );

    /**
     * @param VendorInterface[] $vendors
     * @return mixed
     */
    public function saveAll(array $vendors);

    /**
     * @param VendorInterface $vendor
     * @return mixed
     */
    public function save($vendor);

    /**
     * @param int $miraklShopId
     * @return VendorInterface|null if not found
     */
    public function findByMiraklId($miraklShopId);

    /**
     * @param string $email
     * @return VendorInterface|null if not found
     */
    public function findByEmail($email);

    /**
     * @param $hipayId
     * @return VendorInterface|null if not found
     */
    public function findByHipayId($hipayId);

}