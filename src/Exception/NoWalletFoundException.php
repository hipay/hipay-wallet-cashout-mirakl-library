<?php

namespace Hipay\MiraklConnector\Exception;

use Exception;
use Hipay\MiraklConnector\Vendor\Model\VendorInterface;

/**
 * Class NoWalletFoundException.
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
        parent::__construct(
            $message ?: "The wallet for {$vendor->getHipayId()} is not found",
            $code,
            $previous
        );
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'no.wallet.found';
    }
}
