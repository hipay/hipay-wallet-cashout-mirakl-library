<?php

namespace HiPay\Wallet\Mirakl\Vendor\Event;

use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\BankInfo;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CheckBankInfos
 * Event made to customize the validation process of the bank info
 * 
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class CheckBankInfos extends Event
{
    /** @var bool  */
    protected $synchronicity;
    
    /** @var BankInfo  */
    protected $miraklBankInfo;
    
    /** @var BankInfo  */
    protected $hipayBankInfo;

    /**
     * CheckBankInfos constructor.
     * @param BankInfo $miraklBankInfo
     * @param BankInfo $hipayBankInfo
     */
    public function __construct(BankInfo $miraklBankInfo, BankInfo $hipayBankInfo)
    {
        $this->miraklBankInfo = $miraklBankInfo;
        $this->hipayBankInfo = $hipayBankInfo;
        $this->synchronicity = true;
    }

    /**
     * @return BankInfo
     */
    public function getMiraklBankInfo()
    {
        return $this->miraklBankInfo;
    }

    /**
     * @return BankInfo
     */
    public function getHipayBankInfo()
    {
        return $this->hipayBankInfo;
    }

    /**
     * @return boolean
     */
    public function isSynchrony()
    {
        return $this->synchronicity;
    }

    /**
     * @param boolean $synchronicity
     */
    public function setSynchronicity($synchronicity)
    {
        $this->synchronicity = $synchronicity;
    }
}
