<?php
namespace Hipay\MiraklConnector\Api\Hipay\Model;
use Hipay\MiraklConnector\Vendor\VendorInterface;

/**
 * File ModelInterface.php
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
abstract class SoapModelAbstract
{
    /**
     * ModelAbstract constructor.
     *
     * @param VendorInterface $vendor
     * @param array $shopData
     */
    public abstract function __construct(VendorInterface $vendor, array $shopData);

    /**
     * @return string
     */
    public function getSoapParameterKey()
    {
        return lcfirst(get_class($this));
    }

    /**
     * Get SOAP parameter data
     */
    public function getSoapParameterData()
    {
        return get_object_vars($this);
    }
}