<?php

namespace Hipay\MiraklConnector\Api\Hipay\Model\Status;

use Hipay\MiraklConnector\Common\AbstractEnumeration;

/**
 * Class TransferType.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class TransferType extends AbstractEnumeration
{
    // Hipay Wallet transfer transaction types.
    const TRANSFER = 'Envoi';
    const OTHER = ' Autre';
}
