<?php
/**
 * File Identified.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Api\Hipay;
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