<?php
namespace Hipay\MiraklConnector\Cashout\Model\Operation;

use Hipay\MiraklConnector\Common\AbstractEnumeration;

/**
 * Status class
 * Represent the status of an operation
 *
 * @package Hipay\MiraklConnector\Cashout\Model\Operation
 */
class Status extends AbstractEnumeration
{
    //Initial status of the operation
    const CREATED = 1;

    //Transfert status
    const TRANSFER_SUCCESS = 3;
    const TRANSFER_FAILED = -9;

    //Withdraw statuses
    const WITHDRAW_REQUESTED = 5;
    const WITHDRAW_SUCCESS = 6;
    const WITHDRAW_FAILED = -7;
    const WITHDRAW_CANCELED = -8;
}