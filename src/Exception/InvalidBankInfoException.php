<?php
namespace Hipay\MiraklConnector\Exception;

use Exception;
use Hipay\MiraklConnector\Api\Hipay\Model\Soap\BankInfo;
use Hipay\MiraklConnector\Vendor\Model\VendorInterface;

/**
 * Class InvalidBankInfoException
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
     * @param VendorInterface $vendor
     * @param BankInfo $bankInfo
     * @param string $message
     * @param int $code
     * @param Exception $previous
     */
    public function __construct(
        VendorInterface $vendor,
        BankInfo $bankInfo,
        $message = "",
        $code = 0,
        Exception $previous = null)
    {
        $this->vendor = $vendor;
        $this->bankInfo = $bankInfo;
        parent::__construct($message ?:
            "The Bank info for shop {$vendor->getMiraklId()} is incorrect",
            $code, $previous);
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
     * Return the event name
     *
     * @return string
     */
    public function getEventName()
    {
        return 'invalid.bankInfo';
    }
}