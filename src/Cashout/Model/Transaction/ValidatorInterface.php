<?php

namespace HiPay\Wallet\Mirakl\Cashout\Model\Transaction;

/**
 * Class ValidatorInterface
 * Used to validated transaction like orders.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
interface ValidatorInterface
{
    /**
     * Validate a transaction.
     *
     * @param array $transaction
     *
     * @return bool
     */
    public function isValid(array $transaction);
}
