<?php
/**
 * File NotificationEvent.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace HiPay\Wallet\Mirakl\Notification\Event;

use DateTime;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class NotificationEvent
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class NotificationEvent extends Event
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