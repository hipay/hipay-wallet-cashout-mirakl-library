<?php

namespace HiPay\Wallet\Mirakl\Notification\Event;

use DateTime;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class OtherNotification
 * Event used on the others operation notification.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class OtherNotification extends Event
{
    /** @var  float */
    protected $amount;

    /** @var  string */
    protected $currency;

    /** @var  string */
    protected $label;

    /** @var VendorInterface */
    protected $vendor;

    /** @var DateTime */
    protected $date;

    /**
     * OtherNotification constructor.
     *
     * @param float           $amount
     * @param string          $currency
     * @param string          $label
     * @param VendorInterface $vendor
     * @param DateTime        $date
     */
    public function __construct(
        $amount,
        $currency,
        $label,
        VendorInterface $vendor,
        DateTime $date
    ) {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->label = $label;
        $this->vendor = $vendor;
        $this->date = $date;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return VendorInterface
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @param VendorInterface $vendor
     */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;
    }

    /**
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }
}
