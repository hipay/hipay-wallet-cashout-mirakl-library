<?php
namespace Hipay\MiraklConnector\Exception\Event;

use Exception;
use Symfony\Component\EventDispatcher\Event;

/**
 * File ThrowException.php
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class ThrowException extends Event
{
    protected $exception;

    /**
     * ThrowException constructor.
     * @param $exception
     */
    public function __construct(Exception $exception)
    {
        $this->exception = $exception;
    }

    /**
     * @return mixed
     */
    public function getException()
    {
        return $this->exception;
    }
}