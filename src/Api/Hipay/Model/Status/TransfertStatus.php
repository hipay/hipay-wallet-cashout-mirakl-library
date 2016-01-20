<?php
namespace Hipay\MiraklConnector\Api\Hipay\Status;

use Hipay\MiraklConnector\Common\AbstractEnumeration;
/**
 * Class TransfertStatus
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class TransfertStatus extends AbstractEnumeration
{

    // Hipay Wallet transfer statuses.
    const CAPTURED = 'CAPTURED';
}