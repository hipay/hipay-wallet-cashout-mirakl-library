<?php
namespace Hipay\MiraklConnector\Api\Hipay\Model;
use InvalidArgumentException;
use stdClass;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator;

/**
 * File SoapModelAbstract.php
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
abstract class SoapModelAbstract extends stdClass
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
        self::$validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();
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
     *
     * @return true if the validation passes
     */
    public function validate()
    {
        $errors = self::$validator->validate($this);
        if ($errors->count() != 0) {
            $message = "";
            foreach ($errors as $error) {
                /** @var ConstraintViolation $violation*/
                $message .= $error . "\n";
            }
            throw new InvalidArgumentException($message);
        }
    }
}