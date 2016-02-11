<?php

namespace HiPay\Wallet\Mirakl\Cashout\Event;

use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event object used when the event 'before.availability.check'
 * is dispatched from the processor.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class OperationEvent extends Event
{
    /** @var  OperationInterface */
    protected $operation;

    /** @var  int */
    protected $transferId;

    /** @var  int */
    protected $withdrawId;
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

    /**
     * @return int
     */
    public function getTransferId()
    {
        return $this->transferId;
    }

    /**
     * @param int $transferId
     */
    public function setTransferId($transferId)
    {
        $this->transferId = $transferId;
    }

    /**
     * @return int
     */
    public function getWithdrawId()
    {
        return $this->withdrawId;
    }

    /**
     * @param int $withdrawId
     */
    public function setWithdrawId($withdrawId)
    {
        $this->withdrawId = $withdrawId;
    }
}
