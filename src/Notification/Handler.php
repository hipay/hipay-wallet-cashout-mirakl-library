<?php
namespace Hipay\MiraklConnector\Notification;

use Exception;
use Hipay\MiraklConnector\Api\Hipay\Notification;
use Hipay\MiraklConnector\Cashout\Event\WithdrawFailed;
use Hipay\MiraklConnector\Cashout\Event\WithdrawSuccess;
use Hipay\MiraklConnector\Cashout\Model\Operation\ManagerInterface;
use Hipay\MiraklConnector\Cashout\Model\Operation\Status;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class Handler
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Handler
{
    /** @var  ManagerInterface */
    protected $operationManager;

    /** @var EventDispatcherInterface event */
    protected $dispatcher;

    /**
     * Handler constructor.
     * @param ManagerInterface $operationManager
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        ManagerInterface $operationManager,
        EventDispatcherInterface $dispatcher
    )
    {
        $this->operationManager = $operationManager;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Handle the notification sent by Hipay
     *
     * @param string $operation
     * @param int $transactionId
     * @param string $status
     *
     * @throws Exception
     */
    public function handleHipayNotification($operation, $transactionId, $status)
    {
        if (!$operation == Notification::WITHDRAW_VALIDATION) {
            throw new Exception('Wrong Hipay notification operation');
        }

        $operation = $this->operationManager->findByTransactionId(
            $transactionId);

        if (!$operation) {
            throw new Exception('Operation not found');
        }

        if ($operation->getStatus() != Status::WITHDRAW_REQUESTED) {
            throw new Exception('Wrong operation status');
        }

        if ($status == Notification::OK) {
            $operation->setStatus(new Status(Status::WITHDRAW_SUCCESS));
            $eventName = 'withdraw.success';
            $event = new WithdrawSuccess($operation);
        } else {
            $operation->setStatus(new Status(Status::WITHDRAW_CANCELED));
            $eventName = 'withdraw.failed';
            $event = new WithdrawFailed($operation);
        }

        $this->operationManager->save($operation);

        $this->dispatcher->dispatch($eventName, $event);
    }
}