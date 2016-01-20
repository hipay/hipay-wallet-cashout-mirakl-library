<?php
namespace Hipay\MiraklConnector\Api\Hipay\Model\Status;

use Hipay\MiraklConnector\Common\AbstractEnumeration;
/**
 * Class TransfertStatus
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class TransferStatus extends AbstractEnumeration
{

    // Hipay Wallet transfer statuses.
    const CAPTURED = 'CAPTURED';
}