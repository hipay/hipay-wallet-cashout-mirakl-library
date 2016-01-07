<?php
/**
 * File AddBankAccountEvent.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Vendor\Event;

use Hipay\MiraklConnector\Api\Hipay\Model\BankInfo;
use Symfony\Component\EventDispatcher\Event;


/**
 * Class AddBankAccountEvent
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class AddBankAccountEvent extends AbstractEvent
{
    /** @var  BankInfo */
    protected $bankInfo;

    /**
     * AddBankAccountEvent constructor.
     *
     * @param $bankInfo
     */
    public function __construct($miraklData, BankInfo $bankInfo)
    {
        parent::__construct($miraklData);
        $this->bankInfo = $bankInfo;
    }

    /**
     * @return BankInfo
     */
    public function getBankInfo()
    {
        return $this->bankInfo;
    }

    /**
     * @param BankInfo $bankInfo
     */
    public function setBankInfo($bankInfo)
    {
        $this->bankInfo = $bankInfo;
    }
}