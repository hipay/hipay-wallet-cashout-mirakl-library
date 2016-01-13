<?php
namespace Hipay\MiraklConnector\Cashout\Event;
use Symfony\Component\EventDispatcher\Event;

/**
 * File ValidateTransaction.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class ValidateTransactions extends Event
{
    protected $paymentVoucher;

    protected $transactions;

    /**
     * ValidateTransactions constructor.
     * @param $paymentVoucher
     * @param $transactions
     */
    public function __construct($paymentVoucher, $transactions)
    {
        $this->paymentVoucher = $paymentVoucher;
        $this->transactions = $transactions;
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
    public function getPaymentVoucher()
    {
        return $this->paymentVoucher;
    }

    /**
     * @param mixed $paymentVoucher
     */
    public function setPaymentVoucher($paymentVoucher)
    {
        $this->paymentVoucher = $paymentVoucher;
    }
}