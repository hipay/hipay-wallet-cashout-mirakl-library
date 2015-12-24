<?php
namespace Hipay\MiraklConnector\Api\Hipay\Model;
use Hipay\MiraklConnector\Vendor\VendorInterface;

/**
 * File Data.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class MerchantData extends SoapModelAbstract
{
    /**
     * MerchantData constructor.
     *
     * @param VendorInterface $vendor
     * @param array $shopData
     */
    public function __construct(VendorInterface $vendor, array $shopData)
    {
        parent::__construct($vendor, $shopData);
    }
}