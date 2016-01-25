<?php

namespace Hipay\MiraklConnector\Notification\Event;

use DateTime;
use Hipay\MiraklConnector\Vendor\Model\VendorInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class IdentificationNotification
 * Event used on the identification operation notification.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class IdentificationNotification extends Event
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
