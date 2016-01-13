<?php
/**
 * File ModelValidator.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Service;

use InvalidArgumentException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
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
     * @return ConstraintViolationListInterface
     *
     * @throws InvalidArgumentException
     */
    public static function validate($object)
    {
        if (!static::$validator) {
            static::$validator = Validation::createValidatorBuilder()
                ->enableAnnotationMapping()
                ->getValidator();
        }
        $errors = static::$validator->validate($object);
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