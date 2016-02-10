<?php

namespace HiPay\Wallet\Mirakl\Exception;

use Exception;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\BankInfo as BankInfoStatus;

/**
 * Class UnconfirmedBankAccountException.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class UnconfirmedBankAccountException extends DispatchableException
{
    /** @var  int|false */
    protected $miraklId;

    /** @var  BankInfoStatus */
    protected $status;

    /**
     * UnconfirmedBankAccountException constructor.
     *
     * @param BankInfoStatus $status
     * @param int|bool $miraklId
     * @param string $message
     * @param int $code
     * @param Exception $previous
     */
    public function __construct(
        BankInfoStatus $status,
        $miraklId = null,
        $message = '',
        $code = 0,
        Exception $previous = null
    ) {
        $this->miraklId = $miraklId;
        $this->status = $status;
        $identity = $miraklId ? "This vendor ($miraklId)" : "The operator";
        parent::__construct(
            $message ?:
            "$identity bank account is not validated.\n
             Please contact HiPay",
            $code,
            $previous
        );
    }

    /**
     * @return VendorInterface
     */
    public function getMiraklId()
    {
        return $this->miraklId;
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
