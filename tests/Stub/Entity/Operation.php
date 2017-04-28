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
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\Status;

/**
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Operation implements OperationInterface
{
    protected $paymentVoucher;
    protected $amount;
    protected $miraklId;
    protected $hipayId;
    protected $status;
    protected $withdrawId;
    protected $transferId;
    protected $updatedAt;
    /** @var  float */
    protected $withdrawnAmount;

    /**
     * Operation constructor.
     * @param $miraklId
     * @param $paymentVoucher
     * @param $amount
     * @param $cycleDate
     */
    public function __construct($amount, $cycleDate, $paymentVoucher, $miraklId)
    {
        $this->miraklId = $miraklId;
        $this->paymentVoucher = $paymentVoucher;
        $this->amount = (float) $amount;
        $this->cycleDate = $cycleDate;
        $this->setUpdatedAt(new DateTime());
        $this->setStatus(new Status(Status::CREATED));

    }


    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus(Status $status)
    {
        $this->status = $status->getValue();
    }

    /**
     * @return mixed
     */
    public function getWithdrawId()
    {
        return $this->withdrawId;
    }

    /**
     * @param mixed $withdrawId
     */
    public function setWithdrawId($withdrawId)
    {
        $this->withdrawId = $withdrawId;
    }

    /**
     * @return mixed
     */
    public function getTransferId()
    {
        return $this->transferId;
    }

    /**
     * @param mixed $transferId
     */
    public function setTransferId($transferId)
    {
        $this->transferId = $transferId;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param mixed $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
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

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return mixed
     */
    public function getMiraklId()
    {
        return $this->miraklId;
    }

    /**
     * @param mixed $miraklId
     */
    public function setMiraklId($miraklId)
    {
        $this->miraklId = $miraklId;
    }

    /**
     * @return mixed
     */
    public function getCycleDate()
    {
        return $this->cycleDate;
    }

    /**
     * @param mixed $cycleDate
     */
    public function setCycleDate(\DateTime $cycleDate)
    {
        $this->cycleDate = $cycleDate;
    }

    /**
     * @return mixed
     */
    public function getHipayId()
    {
        return $this->hipayId;
    }

    /**
     * @param mixed $hipayId
     */
    public function setHipayId($hipayId)
    {
        $this->hipayId = $hipayId;
    }
    protected $cycleDate;

    /**
     * @return float
     */
    public function getWithdrawnAmount()
    {
        return $this->withdrawnAmount;
    }

    /**
     * @param float $withdrawnAmount
     */
    public function setWithdrawnAmount($withdrawnAmount)
    {
        $this->withdrawnAmount = $withdrawnAmount;
    }
}