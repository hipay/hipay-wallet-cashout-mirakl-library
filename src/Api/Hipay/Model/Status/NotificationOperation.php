<?php
namespace Hipay\MiraklConnector\Api\Hipay\Model\Status;

/**
 * Class Notification
 * Contains the notification constants
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class NotificationOperation
{
    const WITHDRAW_VALIDATION = 'withdraw_validation';
    const BANK_INFO_VALIDATION = 'bank_info_validation';
    const IDENTIFICATION = 'identification';
    const OTHER = "other_transaction";

}