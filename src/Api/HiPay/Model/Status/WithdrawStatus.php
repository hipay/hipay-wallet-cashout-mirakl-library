<?php

namespace HiPay\Wallet\Mirakl\Api\HiPay\Model\Status;

use HiPay\Wallet\Mirakl\Common\AbstractEnumeration;

/**
 * Constants for the status of the transfer
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class WithdrawStatus extends AbstractEnumeration
{
    // HiPay Wallet transfer statuses.
    const CAPTURED = 'CAPTURED';
    const UNAUTHED = 'UNAUTHED';
    const ABORTED = 'ABORTED';
    const REJECTED_END = 'REJECTED_END';
    const CANCELLED_END = 'CANCELLED_END';
    const STANDBY = 'STANDBY';
}
