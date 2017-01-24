<?php
/**
 * Thrown when the Mirakl setting is not configured with HiPay Prerequisites
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */

namespace HiPay\Wallet\Mirakl\Exception;

use Exception;


class InvalidMiraklSettingException extends Exception
{
    /**
     * InvalidMiraklSettingException constructor.
     *
     * @param string          $message
     * @param int             $code
     * @param Exception       $previous
     */
    public function __construct(
        $message = '',
        $code = 0,
        Exception $previous = null
    ) {
        parent::__construct(
            $message ?:
                "Your Mirakl account is not configured with the HiPay prerequisites as indicated in the HiPay documentation. You must configure the Mirakl account with the additional fields.",
            $code,
            $previous
        );
    }

    /**
     * Return the event name.
     *
     * @return string
     */
    public function getEventName()
    {
        return 'invalid.mirakl.settings';
    }
}