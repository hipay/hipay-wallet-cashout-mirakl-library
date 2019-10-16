<?php

namespace HiPay\Wallet\Mirakl\Api\HiPay\Model\Status;

/**
 * Constants for the operations of the notification
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Notification
{
    const WITHDRAW_VALIDATION = 'withdraw_validation';
    const BANK_INFO_VALIDATION = 'bank_info_validation';
    const IDENTIFICATION = 'identification';
    const OTHER = 'other_transaction';
    const DOCUMENT_VALIDATION = 'document_validation';
    const AUTHORIZATION = 'authorization';
    const CAPTURE = 'capture';
}
