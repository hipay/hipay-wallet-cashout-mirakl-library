<?php
namespace Hipay\MiraklConnector\Cashout\Model\Transaction;
/**
 * File ValidatorInterface.php
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
interface ValidatorInterface
{
    /**
     * Validate a transaction
     *
     * @param array $transaction
     *
     * @return boolean
     */
    public function isValid(array $transaction);
}