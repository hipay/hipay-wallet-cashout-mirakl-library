<?php

namespace HiPay\Wallet\Mirakl\Cashout\Model\Operation;

use HiPay\Wallet\Mirakl\Common\AbstractEnumeration;

/**
 * Status class
 * Represent the status of an operation.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 *
 * @method int getValue()
 */
class Status extends AbstractEnumeration
{
    //Initial status of the operation
    const CREATED = 1;

    //Transfer status
    const TRANSFER_SUCCESS = 3;
    const TRANSFER_FAILED = -9;
    const TRANSFER_NEGATIVE = -10;

    //Withdraw statuses
    const WITHDRAW_REQUESTED = 5;
    const WITHDRAW_SUCCESS = 6;
    const WITHDRAW_FAILED = -7;
    const WITHDRAW_CANCELED = -8;
    const WITHDRAW_NEGATIVE = -11;
}
