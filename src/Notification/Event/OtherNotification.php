<?php

namespace HiPay\Wallet\Mirakl\Notification\Event;

use DateTime;

/**
 * Class OtherNotification
 * Event used on the others operation notification.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class OtherNotification extends NotificationEvent
{
    /** @var  float */
    protected $amount;

    /** @var  string */
    protected $currency;

    /** @var  string */
    protected $label;


    /**
     * OtherNotification constructor.
     *
     * @param float           $amount
     * @param string          $currency
     * @param string          $label
     * @param int             $hipayId
     * @param DateTime        $date
     */
    public function __construct(
        $hipayId,
        DateTime $date,
        $amount,
        $currency,
        $label
    ) {
        parent::__construct($hipayId, $date);
        $this->amount = $amount;
        $this->currency = $currency;
        $this->label = $label;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }


    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }


    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

}
