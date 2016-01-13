<?php
namespace Hipay\MiraklConnector\Cashout\Event;

use Symfony\Component\EventDispatcher\Event;
/**
 * File ComputeTransaction.php
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class ComputeTransactions extends Event
{
    protected $transactions;

    protected $errors;

    /**
     * ComputeTransactions constructor.
     * @param $transactions
     * @param $errors
     */
    public function __construct($transactions, $errors)
    {
        $this->transactions = $transactions;
        $this->errors = $errors;
    }

    /**
     * @return mixed
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @param mixed $transactions
     */
    public function setTransactions($transactions)
    {
        $this->transactions = $transactions;
    }

    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param mixed $errors
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;
    }

}