<?php

namespace Hipay\MiraklConnector\Exception;

use Exception;

/**
 * Class DispatchableException.
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
