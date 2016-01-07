<?php
namespace Hipay\MiraklConnector\Api\Hipay\Model;
use Hipay\MiraklConnector\Vendor\VendorInterface;
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
    protected $validator;

    /**
     * SoapModelAbstract constructor.
     *
     * Instanciate the validator
     *
     * @param VendorInterface $vendor
     * @param array $miraklData
     */
    public function __construct(
        VendorInterface $vendor,
        array $miraklData
    )
    {
        $this->validator = Validation::createValidatorBuilder()
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
        $violations = $this->validator->validate($this);
        if ($violations->count() != 0) {
            $message = "";
            foreach ($violations as $violation) {
                /** @var ConstraintViolation $violation*/
                $message .= ucfirst($violation->getPropertyPath()) . ":\t". $violation->getMessage() . "\n";
            }
            throw new InvalidArgumentException($message);
        }
    }
}