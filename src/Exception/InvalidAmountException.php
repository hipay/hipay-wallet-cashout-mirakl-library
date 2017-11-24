<?php

namespace HiPay\Wallet\Mirakl\Exception;

use Exception;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;

/**
 * Thrown when a wallet is not found
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class InvalidAmountException extends DispatchableException
{
    /** @var  VendorInterface */
    protected $operation;

    /**
     * InvalidAmountException constructor.
     * @param OperationInterface|null $operation
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(
        OperationInterface $operation = null,
        $message = '',
        $code = 0,
        Exception $previous = null
    ) {
        $this->operation = $operation;
        $defaultMessage = $operation ?
            "The operation amount is not valid ({$operation->getAmount()}): operation will not be treated" :
            "The operation amount is not valid: operation will not be treated";
        parent::__construct(
            $message ?: $defaultMessage,
            $code,
            $previous
        );
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'invalid.amount';
    }
}
