<?php

namespace HiPay\Wallet\Mirakl\Api\HiPay\Model\Status;

use HiPay\Wallet\Mirakl\Common\AbstractEnumeration;

/**
 * Class TransferStatus.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class TransferStatus extends AbstractEnumeration
{
    // HiPay Wallet transfer statuses.
    const CAPTURED = 'CAPTURED';
}
