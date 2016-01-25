<?php

namespace Hipay\MiraklConnector\Exception;

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

    /**
     * TransactionException constructor.
     *
     * @param string $orderTransactions
     * @param string $message
     * @param int    $code
     * @param $previousException
     */
    public function __construct(
        $orderTransactions,
        $message = '',
        $code = 0,
        $previousException = null
    ) {
        parent::__construct($message, $code, $previousException);
        $this->orderTransactions = $orderTransactions;
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
}
