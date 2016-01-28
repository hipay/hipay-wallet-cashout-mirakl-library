<?php

namespace HiPay\Wallet\Mirakl\Cashout\Event;

use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CreateOperation
 * Event object used when the event 'before.availability.check'
 * is dispatched from the processor.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class CreateOperation extends Event
{
    /** @var  OperationInterface */
    protected $operation;

    /**
     * CreateOperation constructor.
     *
     * @param OperationInterface $operation
     */
    public function __construct(OperationInterface $operation)
    {
        $this->operation = $operation;
    }

    /**
     * @return OperationInterface
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param OperationInterface $operation
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
    }
}
