<?php

namespace Hipay\MiraklConnector\Exception;

use Exception;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class ValidationFailedException
 * Exception thrown when a model validation failed.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class ValidationFailedException extends DispatchableException
{
    /** @var ConstraintViolationListInterface  */
    protected $constraintViolationList;

    /**
     * ValidationFailedException constructor.
     *
     * @param string    $constraintViolationList
     * @param string    $message
     * @param int       $code
     * @param Exception $previous
     */
    public function __construct(
        $constraintViolationList,
        $message = '',
        $code = 0,
        Exception $previous = null
    ) {
        $this->constraintViolationList = $constraintViolationList;

        parent::__construct(
            $message ?: $this->getDefaultMessage(),
            $code,
            $previous
        );
    }

    /**
     * @return ConstraintViolationListInterface
     */
    public function getConstraintViolationList()
    {
        return $this->constraintViolationList;
    }

    /**
     * Return the event name.
     *
     * @return string
     */
    public function getEventName()
    {
        return 'validation.failed';
    }

    /**
     * @return string
     */
    public function getDefaultMessage()
    {
        $defaultMessage = '';
        foreach ($this->constraintViolationList as $error) {
            /* @var ConstraintViolation $error*/
            $defaultMessage .=
                PHP_EOL.$error->getPropertyPath().' : '.
                $error->getMessage();
        }

        return $defaultMessage;
    }
}
