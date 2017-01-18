<?php

namespace HiPay\Wallet\Mirakl\Api\HiPay\Model\Rest;

use HiPay\Wallet\Mirakl\Service\Validation\ModelValidator;

/**
 * Base class for the models used as a request or response of a soap call.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
abstract class ModelAbstract
{
    /**
     * @return string
     */
    public function getRestParameterKey()
    {
        return lcfirst(substr(strrchr(get_called_class(), '\\'), 1));
    }

    /**
     * Get SOAP parameter data.
     */
    public function getData()
    {
        return get_object_vars($this);
    }

    /**
     * Add the object data in the parameters array
     * Validate data before merging.
     *
     * @param array $parameters
     *
     * @return array
     */
    public function mergeIntoParameters(array $parameters = array())
    {
        $this->validate();

        return $parameters + $this->getData();
    }

    /**
     * Validate the model before sending it
     * Use ModelValidator.
     */
    public function validate()
    {
        ModelValidator::validate($this);
    }
}
