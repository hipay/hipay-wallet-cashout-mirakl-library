<?php
namespace Hipay\MiraklConnector\Exception;

/**
 * Class UnidentifiedWallet
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class UnidentifiedWalletException extends DispatchableException
{

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'wallet.unidentified';
    }
}