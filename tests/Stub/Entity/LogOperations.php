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

    public function getMiraklId()
    {
        return $this->miraklId;
    }

    public function getHipayId()
    {
        return $this->hipayId;
    }

    public function getPaymentVoucher()
    {
        return $this->paymentVoucher;
    }

    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getStatusTransferts()
    {
        return $this->statusTransferts;
    }

    public function getStatusWithDrawal()
    {
        return $this->statusWithDrawal;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getBalance()
    {
        return $this->balance;
    }

    public function getOriginAmount()
    {
        return $this->originAmount;
    }


    public function setMiraklId($miraklId)
    {
        $this->miraklId = $miraklId;
    }

    public function setHipayId($hipayId)
    {
        $this->hipayId = $hipayId;
    }

    public function setPaymentVoucher($paymentVoucher)
    {
        $this->paymentVoucher = $paymentVoucher;
    }

    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    public function setStatusTransferts($statusTransferts)
    {
        $this->statusTransferts = $statusTransferts;
    }

    public function setStatusWithDrawal($statusWithDrawal)
    {
        $this->statusWithDrawal = $statusWithDrawal;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function setBalance($balance)
    {
        $this->balance = $balance;
    }

    public function setOriginAmount($originAmount)
    {
        $this->originAmount = $originAmount;
    }


}