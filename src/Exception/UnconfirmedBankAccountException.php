<?php
namespace Hipay\MiraklConnector\Exception;

use Exception;
use Hipay\MiraklConnector\Vendor\Model\VendorInterface;
use Hipay\MiraklConnector\Api\Hipay\Model\Status\BankInfo as BankInfoStatus;
/**
 * Class UnconfirmedBankAccountException
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class UnconfirmedBankAccountException extends DispatchableException
{
    /** @var  VendorInterface */
    protected $vendor;

    /** @var  BankInfoStatus */
    protected $status;

    /**
     * UnconfirmedBankAccountException constructor.
     * @param VendorInterface $vendor
     * @param BankInfoStatus $status
     * @param string $message
     * @param int $code
     * @param Exception $previous
     */
    public function __construct(
        VendorInterface $vendor,
        BankInfoStatus $status,
        $message = "",
        $code = 0,
        Exception $previous = null
    )
    {
        $this->vendor = $vendor;
        $this->status = $status;
        parent::__construct($message ?:
            "This vendor ({$vendor->getMiraklId()}) bank account is not validated.\n
             Please contact Hipay",
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
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'bank.account.unconfirmed';
    }
}