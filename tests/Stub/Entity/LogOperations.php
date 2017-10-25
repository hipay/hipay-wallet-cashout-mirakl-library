<?php
/**
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace HiPay\Wallet\Mirakl\Test\Stub\Entity;


use DateTime;
use HiPay\Wallet\Mirakl\Notification\Model\LogOperationsInterface;

/**
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class LogOperations implements LogOperationsInterface
{
    protected $miraklId;

    protected $hipayId;

    protected $paymentVoucher;

    protected $dateCreated;

    protected $amount;

    protected $statusTransferts;

    protected $statusWithDrawal;

    protected $message;

    protected $balance;

    protected $originAmount;

    /**
     * Operation constructor.
     * @param $miraklId
     * @param $paymentVoucher
     * @param $amount
     * @param $cycleDate
     */
    public function __construct($miraklId, $hipayId)
    {
        $this->miraklId = $miraklId;
        $this->hipayId = $hipayId;
    }

    function getMiraklId()
    {
        return $this->miraklId;
    }

    function getHipayId()
    {
        return $this->hipayId;
    }

    function getPaymentVoucher()
    {
        return $this->paymentVoucher;
    }

    function getDateCreated()
    {
        return $this->dateCreated;
    }

    function getAmount()
    {
        return $this->amount;
    }

    function getStatusTransferts()
    {
        return $this->statusTransferts;
    }

    function getStatusWithDrawal()
    {
        return $this->statusWithDrawal;
    }

    function getMessage()
    {
        return $this->message;
    }

    function getBalance()
    {
        return $this->balance;
    }

    function getOriginAmount()
    {
        return $this->originAmount;
    }


    function setMiraklId($miraklId)
    {
        $this->miraklId = $miraklId;
    }

    function setHipayId($hipayId)
    {
        $this->hipayId = $hipayId;
    }

    function setPaymentVoucher($paymentVoucher)
    {
        $this->paymentVoucher = $paymentVoucher;
    }

    function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;
    }

    function setAmount($amount)
    {
        $this->amount = $amount;
    }

    function setStatusTransferts($statusTransferts)
    {
        $this->statusTransferts = $statusTransferts;
    }

    function setStatusWithDrawal($statusWithDrawal)
    {
        $this->statusWithDrawal = $statusWithDrawal;
    }

    function setMessage($message)
    {
        $this->message = $message;
    }

    function setBalance($balance)
    {
        $this->balance = $balance;
    }

    function setOriginAmount($originAmount)
    {
        $this->originAmount = $originAmount;
    }


}