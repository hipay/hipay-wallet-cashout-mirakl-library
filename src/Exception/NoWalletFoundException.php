<?php

namespace Hipay\MiraklConnector\Exception;


use Hipay\MiraklConnector\Vendor\VendorInterface;

/**
 * Class NoWalletFoundException
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class NoWalletFoundException extends DispatchableException
{
    /** @var  VendorInterface */
    protected $vendor;

    /**
     * NoWalletFoundException constructor.
     * @param VendorInterface $vendor
     * May be null if not found in database
     */
    public function __construct(VendorInterface $vendor = null)
    {
        $this->vendor = $vendor;
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'no.wallet.found';
    }
}