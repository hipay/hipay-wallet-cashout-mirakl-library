<?php
namespace Hipay\MiraklConnector\Service\Validation;

use Hipay\MiraklConnector\Exception\UnauthorizedModificationException;
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
     * Check an object data against old values
     *
     * @param $object
     * @param array $array
     *
     * @throws UnauthorizedModificationException
     */
    public static function checkImmutability($object, array $array)
    {
        $exception = new UnauthorizedModificationException($object);
        foreach ($array as $key => $previousValue) {
            $methodName = "get" . ucfirst($key);
            if ($previousValue != $object->$methodName()) {
                $exception->addModifiedProperty($key);
            }
        }

        if ($exception->hasModifiedProperty()) {
            throw $exception;
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