<?php
namespace Hipay\MiraklConnector\Api\Hipay\Status;

use Hipay\MiraklConnector\Common\AbstractEnumeration;

/**
 * Class TransfertType
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class TransfertType extends AbstractEnumeration
{
    // Hipay Wallet transfer transaction types.
    const TRANSFER = 'Envoi';
    const OTHER = ' Autre';

}