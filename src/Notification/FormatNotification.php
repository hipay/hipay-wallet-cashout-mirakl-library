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
        $formattedMessage = $title.HiPay::LINEMKD;
        if ($infos) {
            $formattedMessage .=
                    '- ID of the Wallet: ' . $infos['HipayId'] .HiPay::LINEMKD.
                    '- Email Shop: ' . $infos['Email'].HiPay::LINEMKD;
        }
        if ($message) {
            $formattedMessage .= $message.HiPay::LINEMKD ;
        }
        return $formattedMessage;
    }
}

