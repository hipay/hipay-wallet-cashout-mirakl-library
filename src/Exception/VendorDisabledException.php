<?php

namespace HiPay\Wallet\Mirakl\Exception;

use Exception;

/**
 * Class VendorDisabledException
 * @package HiPay\Wallet\Mirakl\Exception
 */
class VendorDisabledException extends DispatchableException
{

    /**
     * VendorDisabledException constructor.
     * @param string $miraklId
     * @param int $operation
     * @param Exception $amount
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(
        $miraklId,
        $operation,
        $code = 0,
        Exception $previous = null
    ) {
        parent::__construct(
            "This vendor (Mirakl id : {$miraklId}) is disabled. Operation type $operation will not be treated",
            $code,
            $previous
        );
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'vendor.disabled';
    }

}
