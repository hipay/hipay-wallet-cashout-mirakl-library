<?php

namespace HiPay\Wallet\Mirakl\Notification\Event;

use DateTime;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class BankInfoNotification
 * Event used on the bank information validation operation notification.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class BankInfoNotification extends Event
{
    /** @var VendorInterface */
    protected $vendor;

    /** @var DateTime */
    protected $date;

    /**
     * BankInfoNotification constructor.
     *
     * @param VendorInterface $vendor
     * @param DateTime        $date
     */
    public function __construct(VendorInterface $vendor, DateTime $date)
    {
        $this->vendor = $vendor;
        $this->date = $date;
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
