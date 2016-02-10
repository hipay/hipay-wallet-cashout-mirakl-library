<?php

namespace HiPay\Wallet\Mirakl\Exception;

use Exception;

/**
 * Class NoFundsAvailableException.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class WrongWalletBalance extends DispatchableException
{
    /** @var int  */
    protected $miraklId;

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
        $message = '',
        $code = 0,
        Exception $previous = null
    ) {
        $this->miraklId = $miraklId;
        parent::__construct(
            $message ?:
            "This vendor ({$miraklId}) balance is wrong",
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
}
