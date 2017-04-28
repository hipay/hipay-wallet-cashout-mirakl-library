<?php

namespace HiPay\Wallet\Mirakl\Notification\Event;

use DateTime;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;

/**
 * Event used on the withdraw operation notification.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Withdraw extends AbstractEvent
{
    /** @var  OperationInterface */
    protected $operation;

    /**
     * WithdrawNotification constructor.
     *
     * @param OperationInterface $operation
     * @param int                $hipayId
     * @param DateTime           $date
     */
    public function __construct(
        $hipayId,
        DateTime $date,
        OperationInterface $operation
    ) {
        parent::__construct($hipayId, $date);
        $this->operation = $operation;
    }

    /**
     * @return OperationInterface
     */
    public function getOperation()
    {
        return $this->operation;
    }


}
