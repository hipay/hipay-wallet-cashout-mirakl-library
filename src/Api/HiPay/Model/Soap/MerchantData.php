<?php

namespace HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap;

/**
 * Value object for merchant data
 * Set the properties directly into the object.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class MerchantData extends ModelAbstract
{
    /**
     * Add the class data to the parameters
     * based on the class name.
     *
     * @param array $parameters
     *
     * @return array
     */
    public function mergeIntoParameters(array $parameters = array())
    {
        return $parameters + array(
            $this->getSoapParameterKey() => $this->getData(),
        );
    }
}
