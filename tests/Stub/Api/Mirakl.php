<?php
/**
 * File Mirakl.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace HiPay\Wallet\Mirakl\Test\Stub\Api;

use DateTime;

/**
 * Class Mirakl
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Mirakl
{

    /**
     * Fetch from Mirakl all vendors (uses S20).
     *
     * @param DateTime $lastDate
     * @return array the response
     */
    public static function getVendors(DateTime $lastDate = null)
    {
        $vendors = json_decode(file_get_contents(__DIR__ . "/../../../data/test/vendors.json"), true);
        $vendors = $vendors['shops'];
        if ($lastDate) {
            $vendors = array_filter($vendors, function (array $vendor) use ($lastDate) {
                return (new DateTime($vendor['last_updated_date'])) >= $lastDate;
            });
        }
        return $vendors;
    }

    /**
     * @return array
     */
    public static function getVendor()
    {
        return array(reset(static::getVendors()));
    }

    /**
     * @param $shopId
     * @param $paymentVoucher
     * @return array
     */
    public static function getOrderTransactions($shopId, $paymentVoucher)
    {
        $transactions = json_decode(file_get_contents(__DIR__ . "/../../../data/test/orders.json"), true);

        $lines = $transactions['lines'];
        return array_filter($lines, function ($line) use ($shopId, $paymentVoucher) {
            return $line['shop_id'] == $shopId && $line['payment_voucher_number'] == $paymentVoucher;
        });
    }
    /**
     * @return array
     */
    public static function getPaymentTransactions()
    {
        $transactions = json_decode(file_get_contents(__DIR__ . "/../../../data/test/payments.json"), true);

        return $transactions['lines'];
    }
}