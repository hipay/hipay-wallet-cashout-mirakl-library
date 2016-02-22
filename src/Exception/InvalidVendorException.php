<?php

namespace HiPay\Wallet\Mirakl\Exception;

use Exception;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;

/**
 * Thrown when the vendor is invalid (The VendorManager says so)
 * @see HiPay\Wallet\Mirakl\Vendor\Model\ManagerInterface::isValid()
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class InvalidVendorException extends DispatchableException
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
        VendorInterface $vendor,
        $message = '',
        $code = 0,
        Exception $previous = null
    ) {
        $this->vendor = $vendor;
        parent::__construct($message ?: "The vendor {$vendor->getMiraklId()} can't be saved", $code, $previous);
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'invalid.vendor';
    }
}
