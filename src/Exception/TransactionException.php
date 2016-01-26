<?php

namespace Hipay\MiraklConnector\Exception;

use Exception;

/**
 * Class TransactionException.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class TransactionException extends DispatchableException
{
    /** @var  array */
    protected $orderTransactions;

    /** @var  Exception */
    protected $originalException;

    /**
     * TransactionException constructor.
     *
     * @param string $orderTransactions
     * @param Exception $originalException
     * @param string $message
     * @param int $code
     * @param $previousException
     */
    public function __construct(
        $orderTransactions,
        $originalException = null,
        $message = '',
        $code = 0,
        $previousException = null
    ) {
        parent::__construct($message, $code, $previousException);
        $this->orderTransactions = $orderTransactions;
        $this->originalException = $originalException;
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'transaction.exception';
    }

    /**
     * @return array
     */
    public function getOrderTransactions()
    {
        return $this->orderTransactions;
    }

    /**
     * @return Exception
     */
    public function getOriginalException()
    {
        return $this->originalException;
    }
}
