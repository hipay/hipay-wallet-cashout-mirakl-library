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
     * Populate the fields with data
     *
     * @param VendorInterface $vendor
     * @param array $miraklShopData
     *
     * @return self
     */
    public abstract function setData(
        VendorInterface $vendor,
        array $miraklShopData
    );

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

    /**
     * Add the object data in the parameters array
     * @param array $parameters
     *
     * @return array
     */
    public function mergeIntoParameters(array $parameters = array())
    {
        return $parameters + $this->getSoapParameterData();
    }
}