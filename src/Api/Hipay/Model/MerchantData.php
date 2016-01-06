<?php
namespace Hipay\MiraklConnector\Api\Hipay\Model;
use Hipay\MiraklConnector\Vendor\VendorInterface;

/**
 * File MerchantData.php
 * Value object for merchant data
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class MerchantData extends SoapModelAbstract
{
    /**
     * @param VendorInterface $vendor
     * @param array $miraklShopData
     *
     * @return SoapModelAbstract
     */
    public function setData(VendorInterface $vendor, array $miraklShopData)
    {
        return new MerchantData();
    }

    /**
     * Add the class data to the parameters
     * based on the class name
     *
     * @param array $parameters
     *
     * @return array
     */
    public function mergeIntoParameters(array $parameters = array())
    {
        return $parameters + array(
            $this->getSoapParameterKey() => $this->getSoapParameterData()
        );
    }
}