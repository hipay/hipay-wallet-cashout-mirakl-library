<?php
/**
 * File DispatchableException.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Exception;
use Exception;


/**
 * Class DispatchableException
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
abstract class DispatchableException extends Exception
{
    /**
     * @return string
     */
    public abstract function getEventName();
}