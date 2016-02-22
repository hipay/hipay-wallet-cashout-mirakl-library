<?php

namespace HiPay\Wallet\Mirakl\Exception;

use Exception;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;

/**
 * Thrown when a wallet is not found
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class WalletNotFoundException extends DispatchableException
{
    /** @var  VendorInterface */
    protected $vendor;

    /**
     * NoWalletFoundException constructor.
     *
     * @param VendorInterface $vendor
     * @param string          $message
     * @param int             $code
     * @param Exception       $previous
     */
    public function __construct(
        VendorInterface $vendor = null,
        $message = '',
        $code = 0,
        Exception $previous = null
    ) {
        $this->vendor = $vendor;
        $defaultMessage = $vendor ?
            "The wallet for {$vendor->getHiPayId()} is not found" : "The vendor is not found in the storage";
        parent::__construct(
            $message ?: $defaultMessage,
            $code,
            $previous
        );
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'wallet.not.found';
    }
}
