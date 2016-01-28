<?php

namespace HiPay\Wallet\Mirakl\Cashout\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ValidateTransactions
 * Event object used when the event 'before.availability.check'
 * is dispatched from the processor.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class ValidateTransactions extends Event
{
    /** @var  array */
    protected $transactions;

    /**
     * ValidateTransactions constructor.
     *
     * @param $transactions
     */
    public function __construct($transactions)
    {
        $this->transactions = $transactions;
    }

    /**
     * @return array
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @param array $transactions
     */
    public function setTransactions($transactions)
    {
        $this->transactions = $transactions;
    }
}
