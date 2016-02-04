<?php

namespace HiPay\Wallet\Mirakl\Notification;

use DateTime;
use Exception;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\Notification;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\NotificationStatus;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\ManagerInterface
    as OperationManager;
use HiPay\Wallet\Mirakl\Exception\ChecksumFailedException;
use HiPay\Wallet\Mirakl\Exception\IllegalNotificationOperationException;
use HiPay\Wallet\Mirakl\Exception\OperationNotFound;
use HiPay\Wallet\Mirakl\Exception\WrongOperationStatus;
use HiPay\Wallet\Mirakl\Notification\Event\BankInfoNotification;
use HiPay\Wallet\Mirakl\Notification\Event\IdentificationNotification;
use HiPay\Wallet\Mirakl\Notification\Event\OtherNotification;
use HiPay\Wallet\Mirakl\Vendor\Model\ManagerInterface as VendorManager;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\Status;
use HiPay\Wallet\Mirakl\Notification\Event\WithdrawNotification;
use SimpleXMLElement;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class Handler
 * Handle the notification server-server
 * sent by HiPay after the significant events
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
     * Handle the notification sent by HiPay.
     *
     * @param $xml
     *
     * @throws Exception
     * @throws IllegalNotificationOperationException
     */
    public function handleHiPayNotification($xml)
    {
        if (!$xml) {
            return;
        }

        $xml = new SimpleXMLElement($xml);

        //Check content
        /** @noinspection PhpUndefinedFieldInspection */
        $md5string = preg_replace('/\n/', '', $xml->result->asXML());
        /** @noinspection PhpUndefinedFieldInspection */
        if (md5($md5string) !=  $xml->md5content) {
            throw new ChecksumFailedException();
        }
        /** @noinspection PhpUndefinedFieldInspection */
        $operation = $xml->result->operation;
        /** @noinspection PhpUndefinedFieldInspection */
        $status = ($xml->result->status == NotificationStatus::OK);
        /** @noinspection PhpUndefinedFieldInspection */
        $date = new DateTime($xml->result->date.' '.$xml->result->time);
        /** @noinspection PhpUndefinedFieldInspection */
        $hipayId = $xml->result->account_id;

        switch ($operation) {
            case Notification::BANK_INFO_VALIDATION:
                $this->bankInfoValidation(
                    $hipayId,
                    $date,
                    $status
                );
                break;
            case Notification::IDENTIFICATION:
                $this->identification(
                    $hipayId,
                    $date,
                    $status
                );
                break;
            case Notification::WITHDRAW_VALIDATION:
                /** @noinspection PhpUndefinedFieldInspection */
                $this->withdrawalValidation(
                    $hipayId,
                    $date,
                    $xml->result->transid,
                    $status
                );
                break;
            case Notification::OTHER:
                /** @noinspection PhpUndefinedFieldInspection */
                $this->other(
                    $xml->result->amount,
                    $xml->result->currency,
                    $xml->result->label,
                    $hipayId,
                    $date,
                    $status
                );
                break;
            default:
                throw new IllegalNotificationOperationException($operation);
        }
    }

    /**
     * @param int             $withdrawalId
     * @param int             $hipayId
     * @param DateTime        $date
     * @param bool            $status
     *
     * @throws Exception
     */
    protected function withdrawalValidation(
        $hipayId,
        DateTime $date,
        $withdrawalId,
        $status
    ) {
        $operation = $this->operationManager
            ->findByWithdrawalId($withdrawalId);

        if (!$operation) {
            throw new OperationNotFound($withdrawalId);
        }

        if ($operation->getStatus() != Status::WITHDRAW_REQUESTED) {
            throw new WrongOperationStatus($operation);
        }

        if ($status) {
            $operation->setStatus(new Status(Status::WITHDRAW_SUCCESS));
            $eventName = 'withdraw.notification.success';
        } else {
            $operation->setStatus(new Status(Status::WITHDRAW_CANCELED));
            $eventName = 'withdraw.notification.failed';
        }

        $this->operationManager->save($operation);
        $event = new WithdrawNotification($hipayId, $date, $operation);

        $this->dispatcher->dispatch($eventName, $event);
    }

    /**
     * @param int             $hipayId
     * @param DateTime        $date
     * @param bool            $status
     */
    protected function bankInfoValidation($hipayId, $date, $status)
    {
        if ($status) {
            $eventName = 'bankInfos.validation.notification.success';
        } else {
            $eventName = 'bankInfos.validation.notification.failed';
        }

        $event = new BankInfoNotification($hipayId, $date);

        $this->dispatcher->dispatch($eventName, $event);
    }

    /**
     * @param int             $hipayId
     * @param DateTime        $date
     * @param bool            $status
     */
    protected function identification($hipayId, $date, $status)
    {
        if ($status) {
            $eventName = 'identification.notification.success';
        } else {
            $eventName = 'identification.notification.failed';
        }

        $event = new IdentificationNotification($hipayId, $date);

        $this->dispatcher->dispatch($eventName, $event);
    }

    /**
     * @param float           $amount
     * @param string          $currency
     * @param string          $label
     * @param int             $hipayId
     * @param DateTime        $date
     * @param bool            $status
     */
    protected function other(
        $amount,
        $currency,
        $label,
        $hipayId,
        $date,
        $status
    ) {
        if ($status) {
            $eventName = 'other.notification.success';
        } else {
            $eventName = 'other.notification.failed';
        }

        $event = new OtherNotification(
            $hipayId,
            $date,
            $amount,
            $currency,
            $label
        );

        $this->dispatcher->dispatch($eventName, $event);
    }
}
