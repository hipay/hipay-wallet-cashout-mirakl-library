<?php
/**
 * File OperationInterface.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Operation;
use Hipay\MiraklConnector\Vendor\VendorInterface;


/**
 * Class OperationInterface
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
interface OperationInterface
{
    /**
     * @return VendorInterface
     */
    public function getRecipent();

    public function getAmount();

    public function getCurrency();
}