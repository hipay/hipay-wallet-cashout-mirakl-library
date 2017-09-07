<?php

namespace HiPay\Wallet\Mirakl\Api\HiPay\Model\Status;

use HiPay\Wallet\Mirakl\Common\AbstractEnumeration;

/**
 * Constants for the status of BankInfo
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class BankInfo extends AbstractEnumeration
{
    // HiPay Wallet bankInfosStatus response 'status' values (in en_GB)
    const BLANK = 'No bank account details.';
    const VALIDATED = 'Validated';
    const TO_VALIDATE = 'To validate';
    const VALIDATION_PROGRESS = 'Validation in progress';
}
