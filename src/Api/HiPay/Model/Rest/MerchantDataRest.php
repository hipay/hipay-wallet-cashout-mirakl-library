<?php

namespace HiPay\Wallet\Mirakl\Api\HiPay\Model\Rest;

/**
 * Value object for merchant data
 * Set the properties directly into the object.
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
class MerchantDataRest extends ModelAbstract
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
            $this->getRestParameterKey() => $this->getData(),
        );
    }
}
