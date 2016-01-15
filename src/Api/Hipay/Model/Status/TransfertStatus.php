<?php
/**
 * File TransfertStatus.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Api\Hipay;


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