<?php
namespace Hipay\MiraklConnector\Service;

use Hipay\MiraklConnector\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator;

/**
 * Class ModelValidator
 * Validate models using the annotation in the interface
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
abstract class ModelValidator
{
    /** @var Validator */
    protected static $validator;

    /**
     * Validate an object (Basic check)
     *
     * @param mixed $object the object to validate
     *
     * @return void
     *
     * @throws ValidationFailedException
     */
    public static function validate($object)
    {
        self::initialize();
        $errors = static::$validator->validate($object);
        if ($errors->count() != 0) {
            //Throw new exception containing the errors
            throw new ValidationFailedException($errors);
        }
    }

    /**
     * Initialize the validator
     */
    public static function initialize()
    {
        if (!static::$validator) {
            static::$validator = Validation::createValidatorBuilder()
                ->enableAnnotationMapping()
                ->getValidator();
        }
    }
}