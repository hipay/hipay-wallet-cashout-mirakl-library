<?php

namespace HiPay\Wallet\Mirakl\Notification\Model;
use HiPay\Wallet\Mirakl\Integration\Entity\LogOperations;

/**
 * Contains utility methods to create, save and find vendors
 * To be implemented by the integrator of the library.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
interface LogOperationsManagerInterface
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
    public function create($miraklId, $hipayId, $paymentVoucher, $amount, $balance);

    /**
     * Insert more data into the vendor.
     * Do not save it.
     *
     * @param VendorInterface $vendor
     * @param array           $miraklData
     */
    public function update(
        LogOperationsInterface $logOperations
    );

    /**
     * Save an array of vendors.
     *
     * @param VendorInterface[] $vendors
     *
     * @return mixed
     */
    public function saveAll(array $logOperations);

    /**
     * Save a vendor.
     *
     * @param VendorInterface $vendor
     *
     * @return mixed
     */
    public function save($logOperations);

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
    public function isValid(LogOperationsInterface $logGeneral);

    /**
     * Finds an operation.
     *
     * @param int $miraklId|null if operator
     * @param int $paymentVoucherNumber optional date to filter upon
     *
     * @return OperationInterface|null
     */
    public function findByMiraklIdAndPaymentVoucherNumber(
        $miraklId,
        $paymentVoucherNumber
    );
}
