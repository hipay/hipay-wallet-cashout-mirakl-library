<?php

namespace Hipay\MiraklConnector\Exception;

use Exception;
use Hipay\MiraklConnector\Cashout\Model\Operation\OperationInterface;

/**
 * Class AlreadyCreatedOperationException.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class AlreadyCreatedOperationException extends DispatchableException
{
    /**
     * @var OperationInterface
     */
    protected $operation;

    /**
     * AlreadyCreatedOperationException constructor.
     *
     * @param OperationInterface $operation
     * @param string             $message
     * @param int                $code
     * @param Exception          $previous
     */
    public function __construct(
        $operation,
        $message = '',
        $code = 0,
        Exception $previous = null
    ) {
        $this->operation = $operation;
        $miraklId = $operation->getMiraklId() ?: 'operateur';
        parent::__construct(
            $message ?:
            "An operation for $miraklId is already
            created for the cycle {$operation->getCycleDate()->format('Y-m-d H:i')}",
            $code,
            $previous
        );
    }

    /**
     * @return OperationInterface
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'operation.already.created';
    }
}
