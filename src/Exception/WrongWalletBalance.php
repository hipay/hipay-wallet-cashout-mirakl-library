<?php

namespace HiPay\Wallet\Mirakl\Exception;

use Exception;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;

/**
 * Class NoFundsAvailableException.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class WrongWalletBalance extends DispatchableException
{
    /** @var VendorInterface  */
    protected $vendor;

    /**
     * NoFundsAvailable constructor.
     *
     * @param VendorInterface $vendor
     * @param string          $message
     * @param int             $code
     * @param Exception       $previous
     */
    public function __construct(
        $vendor,
        $message = '',
        $code = 0,
        Exception $previous = null
    ) {
        $this->vendor = $vendor;
        parent::__construct(
            $message ?:
            "This vendor ({$vendor->getMiraklId()}) balance is wrong",
            $code,
            $previous
        );
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'wrong.wallet.balance';
    }
}
