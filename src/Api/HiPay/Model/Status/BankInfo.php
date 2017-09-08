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
    const BLANK               = 1;
    const VALIDATION_PROGRESS = 2;
    const VALIDATED           = 3;
    const BLANK_LABEL               = 'No bank account details.';
    const VALIDATION_PROGRESS_LABEL = 'Validation in progress';
    const VALIDATED_LABEL           = 'Validated';

    public static function getLabel($status)
    {
        switch ($status) {
            case self::BLANK:
                return self::BLANK_LABEL;
            case self::VALIDATION_PROGRESS:
                return self::VALIDATION_PROGRESS_LABEL;
            case self::VALIDATED:
                return self::VALIDATED_LABEL;
        }
    }
}