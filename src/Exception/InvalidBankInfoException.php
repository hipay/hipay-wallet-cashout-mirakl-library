<?php

namespace HiPay\Wallet\Mirakl\Exception;

use Exception;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\BankInfo;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;

/**
 * Thrown when the bank information in not synchronized
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class InvalidBankInfoException extends DispatchableException
{
    /** @var  VendorInterface */
    protected $vendor;

    /** @var  BankInfo */
    protected $bankInfo;

    /**
     * InvalidBankInfoException constructor.
     *
     * @param VendorInterface $vendor
     * @param BankInfo        $bankInfo
     * @param string          $message
     * @param int             $code
     * @param Exception       $previous
     */
    public function __construct(
        VendorInterface $vendor,
        BankInfo $bankInfo,
        $message = '',
        $code = 0,
        Exception $previous = null
    ) {
        $this->vendor = $vendor;
        $this->bankInfo = $bankInfo;
        parent::__construct(
            $message ?:
            "The Bank info for shop {$vendor->getMiraklId()} are not synchronized with HiPay Wallet (which means that bank info have been updated in Mirakl though they were already registered into HiPay Wallet). Please contact HiPay in order to update the bank info for this shop.",
            $code,
            $previous
        );
    }

    /**
     * @return VendorInterface
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @return BankInfo
     */
    public function getBankInfo()
    {
        return $this->bankInfo;
    }

    /**
     * Return the event name.
     *
     * @return string
     */
    public function getEventName()
    {
        return 'invalid.bankInfo';
    }
}
