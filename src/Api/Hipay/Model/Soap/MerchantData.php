<?php
namespace Hipay\MiraklConnector\Api\Hipay\Model\Soap;

/**
 * File MerchantData.php
 * Value object for merchant data
 * Set the properties directly
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class MerchantData extends ModelAbstract
{
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