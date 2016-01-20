<?php
namespace Hipay\MiraklConnector\Api\Hipay\Model\Status;

use Hipay\MiraklConnector\Common\AbstractEnumeration;

/**
 * Class holding different constants for hipay
 * 
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Indentified extends AbstractEnumeration
{
    // Hipay Wallet accountInfosIdentified response 'status' values.
    const NO = 'no';
    const YES = 'yes';
}