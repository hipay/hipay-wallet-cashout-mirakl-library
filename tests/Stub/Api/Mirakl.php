<?php
/**
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace HiPay\Wallet\Mirakl\Test\Stub\Api;

use DateTime;

/**
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
     * List shop documents (uses S30).
     *
     * @param $shops
     * @return array
     */
    public static function getShopDocuments($shops)
    {
        $documentsResponse = json_decode(file_get_contents(__DIR__ . "/../../../data/test/documents.json"), true);

        $allDocuments = $documentsResponse['shop_documents'];

        $documents = array_filter($allDocuments, function($element) use ($shops) {
            return in_array($element['shop_id'], $shops);
        });

        return $documents;
    }

    /**
     * @return array
     */
    public static function getVendor()
    {
        $vendors = static::getVendors();
        $vendor = reset($vendors);
        return array($vendor);
    }

    /**
     * @param $shopId
     * @param $paymentVoucher
     * @param $file
     * @return array
     */
    public static function getOrderTransactions($shopId, $paymentVoucher, $file)
    {
        $transactions = json_decode(file_get_contents(__DIR__ . "/../../../data/test/orders/$file"), true);

        $lines = $transactions['lines'];

        return array_filter($lines, function ($line) use ($shopId, $paymentVoucher) {
            return  $line['shop_id'] == $shopId &&
                    $line['payment_voucher_number'] == $paymentVoucher &&
                    $line['transaction_type'] != "PAYMENT";
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