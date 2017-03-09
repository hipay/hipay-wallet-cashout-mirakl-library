<?php
/**
 * Make the SOAP & REST call to the HiPay API.
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
namespace HiPay\Wallet\Mirakl\Notification;

use HiPay\Wallet\Mirakl\Api\HiPay;

class FormatNotification {

    public function formatMessage (
        $title,
        $infos = false,
        $message = false
    )
    {
        $markdown = HiPay::LINEMKD .HiPay::SEPARMKD . '### ' . $title;
        if ($infos) {
            $markdown .=
                    HiPay::LINEMKD . HiPay::SEPARMKD . '***' .
                    HiPay::LINEMKD . HiPay::SEPARMKD .
                    HiPay::LINEMKD . HiPay::SEPARMKD .'* Shop ID Mirakl: ' . $infos['shopId'] .
                    HiPay::LINEMKD . HiPay::SEPARMKD .'* ID of the Wallet: ' . $infos['HipayId'] .
                    HiPay::LINEMKD . HiPay::SEPARMKD .'* Email Shop: ' . $infos['Email'] .
                    HiPay::LINEMKD . HiPay::SEPARMKD .'* Type Message: ' . $infos['Type'] .
                    HiPay::LINEMKD . HiPay::SEPARMKD . '***';
        }
        if ($message) {
            $markdown .= HiPay::LINEMKD.HiPay::SEPARMKD.'```
                '.HiPay::LINEMKD.HiPay::SEPARMKD.'Message:'.$message.'   
                '.HiPay::LINEMKD.HiPay::SEPARMKD.'```
                '.HiPay::LINEMKD.HiPay::SEPARMKD.'***'.HiPay::SEPARMKD;
        }
        return $markdown;
    }
}

