<?php

namespace Hipay\MiraklConnector\Notification;

use DateTime;
use Exception;
use Hipay\MiraklConnector\Api\Hipay\Model\Status\NotificationOperation;
use Hipay\MiraklConnector\Api\Hipay\Model\Status\NotificationStatus;
use Hipay\MiraklConnector\Cashout\Model\Operation\ManagerInterface
    as OperationManager;
use Hipay\MiraklConnector\Exception\IllegalNotificationOperationException;
use Hipay\MiraklConnector\Notification\Event\BankInfoNotification;
use Hipay\MiraklConnector\Notification\Event\IdentificationNotification;
use Hipay\MiraklConnector\Notification\Event\OtherNotification;
use Hipay\MiraklConnector\Vendor\Model\ManagerInterface as VendorManager;
use Hipay\MiraklConnector\Cashout\Model\Operation\Status;
use Hipay\MiraklConnector\Notification\Event\WithdrawNotification;
use Hipay\MiraklConnector\Vendor\Model\VendorInterface;
use SimpleXMLElement;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class Handler
 * Handle the notification server-server
 * sent by Hipay after the significant events
 * Hook in the notification by using the event dispatcher.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Handler
{
    /** @var  OperationManager */
    protected $operationManager;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var VendorManager */
    private $vendorManager;

    /**
     * Handler constructor.
     *
     * @param OperationManager         $operationManager
     * @param VendorManager            $vendorManager
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        OperationManager $operationManager,
        VendorManager $vendorManager,
        EventDispatcherInterface $dispatcher
    ) {
        $this->operationManager = $operationManager;
        $this->dispatcher = $dispatcher;
        $this->vendorManager = $vendorManager;
    }

    /**
     * Handle the notification sent by Hipay.
     *
     * @param $xml
     *
     * @throws Exception
     * @throws IllegalNotificationOperationException
     */
    public function handleHipayNotification($xml)
    {
        $xml = new SimpleXMLElement($xml);

        if (md5($xml->result) !=  $xml->md5content) {
            throw new Exception('Wrong checksum');
        }

        $operation = $xml->result->operation;
        $status = $xml->result->status == NotificationStatus::OK;
        $date = new \DateTime($xml->result->date.' '.$xml->result->time);
        $vendor = $this->vendorManager->findByHipayId($xml->result->account_id);

        switch ($operation) {
            case NotificationOperation::BANK_INFO_VALIDATION:
                $this->bankInfoValidation(
                    $vendor,
                    $date,
                    $status
                );
                break;
            case NotificationOperation::IDENTIFICATION:
                $this->identification(
                    $vendor,
                    $date,
                    $status
                );
                break;
            case NotificationOperation::WITHDRAW_VALIDATION:
                $this->withdrawalValidation(
                    $xml->result->transid,
                    $vendor,
                    $date,
                    $status
                );
                break;
            case NotificationOperation::OTHER:
                $this->other(
                    $xml->result->amount,
                    $xml->result->currency,
                    $xml->result->label,
                    $vendor,
                    $date,
                    $status
                );
                break;
            default:
                throw new IllegalNotificationOperationException($operation);
        }
    }

    /**
     * @param $transactionId
     * @param VendorInterface $vendor
     * @param DateTime        $date
     * @param bool            $status
     *
     * @throws Exception
     */
    protected function withdrawalValidation(
        $transactionId,
        VendorInterface $vendor,
        \DateTime $date,
        $status
    ) {
        $operation = $this->operationManager
            ->findByWithdrawalId($transactionId);

        if (!$operation) {
            throw new Exception('Operation not found');
        }

        if ($operation->getStatus() != Status::WITHDRAW_REQUESTED) {
            throw new Exception('Wrong operation status in the database');
        }

        if ($status) {
            $operation->setStatus(new Status(Status::WITHDRAW_SUCCESS));
            $eventName = 'withdraw.notification.success';
        } else {
            $operation->setStatus(new Status(Status::WITHDRAW_CANCELED));
            $eventName = 'withdraw.notification.failed';
        }

        $this->operationManager->save($operation);
        $event = new WithdrawNotification($operation, $vendor, $date);

        $this->dispatcher->dispatch($eventName, $event);
    }

    /**
     * @param VendorInterface $vendor
     * @param DateTime        $date
     * @param bool            $status
     */
    protected function bankInfoValidation($vendor, $date, $status)
    {
        if ($status) {
            $eventName = 'bankInfos.validation.notification.success';
        } else {
            $eventName = 'bankInfos.validation.notification.failed';
        }

        $event = new BankInfoNotification($vendor, $date);

        $this->dispatcher->dispatch($eventName, $event);
    }

    /**
     * @param VendorInterface $vendor
     * @param DateTime        $date
     * @param bool            $status
     */
    protected function identification($vendor, $date, $status)
    {
        if ($status) {
            $eventName = 'identification.notification.success';
        } else {
            $eventName = 'identification.notification.failed';
        }

        $event = new IdentificationNotification($vendor, $date);

        $this->dispatcher->dispatch($eventName, $event);
    }

    /**
     * @param float           $amount
     * @param string          $currency
     * @param string          $label
     * @param VendorInterface $vendor
     * @param DateTime        $date
     * @param bool            $status
     */
    protected function other(
        $amount,
        $currency,
        $label,
        $vendor,
        $date,
        $status
    ) {
        if ($status) {
            $eventName = 'other.notification.success';
        } else {
            $eventName = 'other.notification.failed';
        }

        $event = new OtherNotification(
            $amount,
            $currency,
            $label,
            $vendor,
            $date
        );

        $this->dispatcher->dispatch($eventName, $event);
    }
}
