<?php

namespace HiPay\Wallet\Mirakl\Exception;

use Exception;
use Guzzle\Http\Exception\ClientErrorResponseException;

/**
 * Base class for exception meant to be dispatched as a specific event
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class HipayRestResponseException extends Exception
{

    private $guzzleException;

    public function __construct(
        ClientErrorResponseException $guzzleException,
        $command,
        $parameters,
        $code = 0,
        Exception $previous = null
    ) {

        $this->guzzleException = $guzzleException;

        try {
            $result = $guzzleException->getResponse()->json();
        } catch (Exception $e) {
            $result = null;
        }

        $message = "There was an error with the Rest call " . $command->getName();

        if ($message !== null) {
            $message .= PHP_EOL . $result['code'] . ' : ' . $result['message'] .
                PHP_EOL . print_r($result['errors'], true) .
                PHP_EOL . 'Parameters : ' . print_r($parameters, true) . PHP_EOL;
        }

        parent::__construct(
            $message,
            $code,
            $previous
        );
    }

    public function getResponse()
    {
        if ($this->guzzleException !== null) {
            return $this->guzzleException->getResponse();
        }

        return null;
    }

    public function getRequest()
    {
        if ($this->guzzleException !== null) {
            return $this->guzzleException->getRequest();
        }

        return null;
    }
}
