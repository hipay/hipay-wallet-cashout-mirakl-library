<?php

namespace HiPay\Wallet\Mirakl\Notification\Event;

use DateTime;
use Symfony\Component\EventDispatcher\Event;

/**
 * Abstract base class for all notification event
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
abstract class AbstractEvent extends Event
{
    /** @var int */
    protected $hipayId;

    /** @var DateTime */
    protected $date;

    /**
     * BankInfoNotification constructor.
     *
     * @param int $hipayId
     * @param DateTime        $date
     */
    public function __construct($hipayId, DateTime $date)
    {
        $this->hipayId = $hipayId;
        $this->date = $date;
    }

    /**
     * @return int
     */
    public function getHipayId()
    {
        return $this->hipayId;
    }

    /**
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }
}
