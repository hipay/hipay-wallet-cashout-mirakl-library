<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 30/09/19
 * Time: 10:04
 */

namespace HiPay\Wallet\Mirakl\Exception;


use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;


class WalletAccountUpdateException extends DispatchableException
{

    public function __construct(VendorInterface $vendor = null, \Exception $previous = null)
    {
        $message = "Error while updating wallet {$vendor->getHiPayId()}";
        $message .= PHP_EOL . $previous->getMessage();

        parent::__construct($message, $previous->getCode(), $previous);
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'wallet.update.failed';
    }
}
