<?php

namespace HiPay\Wallet\Mirakl\Exception;

/**
 * Thrown when there is not enough funds in the technical wallet balance
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class NotEnoughFunds extends DispatchableException
{
    /**
     * TransactionException constructor.
     *
     * @param string $message
     * @param int    $code
     * @param $previousException
     */
    public function __construct(
        $message = '',
        $code = 0,
        $previousException = null
    ) {
        parent::__construct(
            $message ?: "The technical account don't have enough fund",
            $code,
            $previousException
        );
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'not.enough.funds';
    }
}
