<?php

namespace HiPay\Wallet\Mirakl\Vendor\Event;

use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\BankInfo;
use Symfony\Component\EventDispatcher\Event;

/**
 * * Event object used when the event 'before.bankAccount.add'
 * is dispatched from the processor.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class AddBankAccount extends Event
{
    /** @var  BankInfo */
    protected $bankInfo;

    /**
     * AddBankAccountEvent constructor.
     *
     * @param $bankInfo
     */
    public function __construct(BankInfo $bankInfo)
    {
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
     *
     * @return $this
     */
    public function setBankInfo($bankInfo)
    {
        $this->bankInfo = $bankInfo;

        return $this;
    }
}
