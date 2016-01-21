<?php
/**
 * File IllegalOperationException.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Exception;
use Exception;

/**
 * Class IllegalOperationException
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile.
 */
class IllegalNotificationOperationException extends Exception
{
    /**
     * @var string
     */
    protected $operation;

    /**
     * IllegalNotificationOperationException constructor.
     * @param string $operation
     * @param string $message
     */
    public function __construct($operation, $message = "")
    {
        parent::__construct($message);
        $this->operation = $operation;
    }

    /**
     * @return string
     */
    public function getOperation()
    {
        return $this->operation;
    }
}