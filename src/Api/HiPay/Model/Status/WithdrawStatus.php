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
    const AUTHED = 'AUTHED';
}
