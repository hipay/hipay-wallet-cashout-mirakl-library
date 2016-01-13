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
     * @return array
     */
    public function validate(array $transaction);

    /**
     * Validate a transaction
     *
     * @param array $errors
     *
     * @return void
     */
    public function handleErrors(array $errors);
}