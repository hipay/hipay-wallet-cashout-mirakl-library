<?php

namespace HiPay\Wallet\Mirakl\Common;

use ReflectionClass;

/**
 * Represent an enumeration similar to the Enum class in Java.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
abstract class AbstractEnumeration
{
    /** @var mixed */
    protected $value;

    /**
     * Status constructor.
     *
     * @param $value
     */
    public function __construct($value)
    {
        if (!in_array($value, $this->getConstList(), true)) {
            throw new \InvalidArgumentException(
                $value.'is not a possible value for ' . __CLASS__
            );
        }
        $this->value = $value;
    }

    /**
     * Return the constant list.
     *
     * @return array
     */
    public function getConstList()
    {
        $reflect = new ReflectionClass(get_class($this));

        return $reflect->getConstants();
    }

    /**
     * Return the value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * String representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getValue();
    }
}
