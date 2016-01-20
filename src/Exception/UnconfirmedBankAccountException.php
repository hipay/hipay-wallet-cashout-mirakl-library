<?php
namespace Hipay\MiraklConnector\Exception;

use Hipay\MiraklConnector\Vendor\Model\VendorInterface;
use Hipay\MiraklConnector\Api\Hipay\Status\BankInfo as BankInfoStatus;
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
     */
    public function __construct(VendorInterface $vendor, BankInfoStatus $status)
    {
        $this->vendor = $vendor;
        $this->status = $status;
    }

    /**
     * @return VendorInterface
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @param VendorInterface $vendor
     * @return UnconfirmedBankAccountException
     */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     * @return UnconfirmedBankAccountException
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'bank.account.unconfirmed';
    }
}