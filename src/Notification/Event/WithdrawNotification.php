<?php

namespace HiPay\Wallet\Mirakl\Notification\Event;

use DateTime;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class WithdrawNotification
 * Event used on the withdraw operation notification.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class WithdrawNotification extends Event
{
    /** @var  OperationInterface */
    protected $operation;

    /** @var VendorInterface */
    protected $vendor;

    /** @var DateTime */
    protected $date;

    /**
     * WithdrawNotification constructor.
     *
     * @param OperationInterface $operation
     * @param VendorInterface    $vendor
     * @param DateTime           $date
     */
    public function __construct(
        OperationInterface $operation,
        VendorInterface $vendor,
        DateTime $date
    ) {
        $this->operation = $operation;
        $this->vendor = $vendor;
        $this->date = $date;
    }

    /**
     * @return OperationInterface
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param OperationInterface $operation
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
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
