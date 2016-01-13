<?php
namespace Hipay\MiraklConnector\Api\Hipay\Model;

use Hipay\MiraklConnector\Service\ModelValidator;
use Symfony\Component\Validator\Validator;

/**
 * Class SoapModelAbstract
 * Base class for the models used as a request or response of a soap call
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
abstract class SoapModelAbstract
{
    /** @var Validator Validate the model */
    protected static $validator;

    /**
     * SoapModelAbstract constructor.
     *
     * Instanciate the validator
     *
     * @param array $miraklData
     */
    public function __construct(array $miraklData)
    {
    }

    /**
     * @return string
     */
    public function getSoapParameterKey()
    {
        return lcfirst(substr(strrchr(get_called_class(), '\\'), 1));
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
     * Validate data before merging
     *
     * @param array $parameters
     *
     * @return array
     */
    public function mergeIntoParameters(array $parameters = array())
    {
        $this->validate();
        return $parameters + $this->getSoapParameterData();
    }

    /**
     * Validate the model before sending it
     * Use ModelValidator
     *
     * @return void
     */
    public function validate()
    {
        ModelValidator::validate($this);
    }
}