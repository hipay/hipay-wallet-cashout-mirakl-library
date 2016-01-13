<?php
namespace Hipay\MiraklConnector\Cashout\Model\Operation;

use Prophecy\Argument;
use ReflectionClass;

/**
 * Status class
 * Represent the status of an operation
 *
 * @package Hipay\MiraklConnector\Cashout\Model\Operation
 */
class Status
{
    const CREATED = 1;
    const TRANSFERED = 2;
    const WITHDRAWED = 3;

    /** @var  mixed */
    protected $value;

    /**
     * Status constructor.
     * @param $value
     */
    public function __construct($value)
    {
        if (!in_array($value, $this->getConstList(), true)) {
            throw new \InvalidArgumentException(
                $value . "is not a possible value for status"
            );
        }
        $this->value = $value;
    }


    /**
     * Return the constant list
     * @return array
     */
    public function getConstList()
    {
        $reflect = new ReflectionClass(get_class($this));
        return $reflect->getConstants();
    }

    /**
     * Return the value
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}