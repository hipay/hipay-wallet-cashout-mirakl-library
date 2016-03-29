<?php

namespace HiPay\Wallet\Mirakl\Exception;

use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;

/**
 * Thrown when attempting to withdraw money with an unidentified wallet
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class UnidentifiedWalletException extends DispatchableException
{
    /**
     * @var VendorInterface
     */
    protected $vendor;

    /**
     * TransactionException constructor.
     *
     * @param VendorInterface $vendor
     * @param string          $message
     * @param int             $code
     * @param $previousException
     */
    public function __construct(
        $vendor,
        $message = '',
        $code = 0,
        $previousException = null
    ) {
        $this->vendor = $vendor;
        parent::__construct(
            $message ?: "The HiPay Wallet account {$vendor->getHiPayId()} is unidentified. Please upload identification documents for Mirakl shop " . $vendor->getMiraklId() ." if you didn't already do so.",
            $code,
            $previousException
        );
    }
    /**
     * @return string
     */
    public function getEventName()
    {
        return 'wallet.unidentified';
    }

    /**
     * @return VendorInterface
     */
    public function getVendor()
    {
        return $this->vendor;
    }
}
