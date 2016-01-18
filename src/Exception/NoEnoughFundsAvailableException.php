<?php
namespace Hipay\MiraklConnector\Exception;
use Hipay\MiraklConnector\Vendor\VendorInterface;


/**
 * Class NoFundsAvailableException
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class NoEnoughFundsAvailableException extends DispatchableException
{
    /** @var VendorInterface  */
    protected $vendor;
    /**
     * NoFundsAvailable constructor.
     * @param VendorInterface $vendor
     */
    public function __construct($vendor)
    {
        $this->vendor = $vendor;
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'not.enough.funds';
    }
}