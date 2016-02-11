<?php

namespace HiPay\Wallet\Mirakl\Api\HiPay\Model\Status;

use HiPay\Wallet\Mirakl\Common\AbstractEnumeration;

/**
 * Constants for the status of identification
 * 
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Identified extends AbstractEnumeration
{
    // HiPay Wallet accountInfosIdentified response 'status' values.
    const NO = 'no';
    const YES = 'yes';
}
