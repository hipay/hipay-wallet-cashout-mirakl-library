<?php

namespace HiPay\Wallet\Mirakl\Exception;

use Exception;

/**
 * Thrown when a vendor don't have the correct balance for a withdraw
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class WrongWalletBalance extends DispatchableException
{
    /** @var int  */
    protected $miraklId;
    protected $balance;

    /**
     * NoFundsAvailable constructor.
     *
     * @param int       $miraklId
     * @param string    $message
     * @param int       $code
     * @param Exception $previous
     */
    public function __construct(
        $miraklId,
        $operation,
        $amount,
        $balance,
        $code = 0,
        Exception $previous = null
    ) {
        $this->miraklId = $miraklId;
        $this->balance = $balance;
        parent::__construct(
            "This vendor ({$miraklId}) balance is insufficient. Operation type $operation / Operation amount $amount / Balance $balance",
            $code,
            $previous
        );
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'wrong.wallet.balance';
    }

    /**
     * @return int
     */
    public function getMiraklId()
    {
        return $this->miraklId;
    }

    /**
     * @return int
     */
    public function getBalance()
    {
        return $this->balance;
    }
}
