<?php

namespace HiPay\Wallet\Mirakl\Exception;

use Exception;

/**
 * Class PaymentBlockedException
 * @package HiPay\Wallet\Mirakl\Exception
 */
class PaymentBlockedException extends DispatchableException
{

    /**
     * PaymentBlockedException constructor.
     * @param string $miraklId
     * @param int $operation
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
            "This vendor (Mirakl id : {$miraklId}) payments are blocked. Operation type $operation will not be treated",
            $code,
            $previous
        );
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'vendor.payment.blocked';
    }

}
