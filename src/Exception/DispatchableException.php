<?php

namespace HiPay\Wallet\Mirakl\Exception;

use Exception;

/**
 * Base class for exception meant to be dispatched as a specific event
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
abstract class DispatchableException extends Exception
{
    /**
     * @return string
     */
    abstract public function getEventName();
}
