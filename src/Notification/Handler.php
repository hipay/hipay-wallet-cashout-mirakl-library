<?php

namespace HiPay\Wallet\Mirakl\Notification;

use DateTime;
use Exception;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\Notification;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\NotificationStatus;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\ManagerInterface as OperationManager;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\Status;
use HiPay\Wallet\Mirakl\Common\AbstractProcessor;
use HiPay\Wallet\Mirakl\Exception\ChecksumFailedException;
use HiPay\Wallet\Mirakl\Exception\IllegalNotificationOperationException;
use HiPay\Wallet\Mirakl\Exception\OperationNotFound;
use HiPay\Wallet\Mirakl\Exception\WrongOperationStatus;
use HiPay\Wallet\Mirakl\Notification\Event\BankInfo;
use HiPay\Wallet\Mirakl\Notification\Event\Identification;
use HiPay\Wallet\Mirakl\Notification\Event\Other;
use HiPay\Wallet\Mirakl\Notification\Event\Withdraw;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorManagerInterface;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handle the notification server-server
 * sent by HiPay after the significant events
 * Hook in the notification by using the event dispatcher.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Handler extends AbstractProcessor
{
    /** @var  OperationManager */
    protected $operationManager;

    /** @var  VendorManagerInterface */
    protected $vendorManager;

    /**
     * Handler constructor.
     *
     * @param OperationManager $operationManager
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param VendorManagerInterface $vendorManager
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        OperationManager $operationManager,
        VendorManagerInterface $vendorManager
    ) {
        parent::__construct($dispatcher, $logger);
        $this->operationManager = $operationManager;
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
    public function handleHiPayNotification($app, $xml)
    {
        if (!$xml) {
            return;
        }

        if (is_string($xml)) {
            $xml = strtr(rawurldecode($xml), array("\n" => ''));
            $xml = new SimpleXMLElement($xml);
        }

        //Check content
        /** @noinspection PhpUndefinedFieldInspection */
        $md5string = strtr($xml->result->asXML(), array("\n" => '', "\t" => ''));
        $md5string = trim(preg_replace("#\>( )+?\<#", "><", $md5string));

        /** @noinspection PhpUndefinedFieldInspection */
        if (md5($md5string) !=  $xml->md5content) {
            throw new ChecksumFailedException();
        }
        /** @noinspection PhpUndefinedFieldInspection */
        $operation = (string) $xml->result->operation;
        /** @noinspection PhpUndefinedFieldInspection */
        $status = ($xml->result->status == NotificationStatus::OK);
        /** @noinspection PhpUndefinedFieldInspection */
        $date = new DateTime((string) $xml->result->date.' '. (string) $xml->result->time);
        /** @noinspection PhpUndefinedFieldInspection */
        $hipayId = (int) $xml->result->account_id;

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
                    (string) $xml->result->transid,
                    $status
                );
                break;
            case Notification::OTHER:
                /** @noinspection PhpUndefinedFieldInspection */
                $this->other(
                    (float) $xml->result->amount,
                    (string) $xml->result->currency,
                    (string) $xml->result->label,
                    $hipayId,
                    $date,
                    $status
                );
                break;
            default:
                throw new IllegalNotificationOperationException($operation);
        }
        // init email content with response API
        $body = '<ul>
                    <li>Operation: ' . $operation . '</li>
                    <li>Status: ' . $status . '</li>
                    <li>Message: ' . $xml->result->message . '</li>
                    <li>Date: ' . $date . '</li>
                    <li>Document type: ' . $xml->result->document_type . '</li>
                    <li>Document type label: ' . $xml->result->document_type_label . '</li>
                    <li>Account ID: ' . $hipayId . '</li>
                </ul>';

        // Send email to operator
        $message = \Swift_Message::newInstance()
            ->setSubject('[HiPay Notification - ' .$hipayId. '] ' . $operation)
            ->setFrom(array($app['parameters']['mail.from']))
            ->setTo(array($app['parameters']['mail.to']))
            ->setBody($body);

        $app['mailer']->send($message);

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
            $this->logger->info("Withdraw {$operation->getWithdrawId()} successful");
            $eventName = 'withdraw.notification.success';
        } else {
            $operation->setStatus(new Status(Status::WITHDRAW_CANCELED));
            $this->logger->info("Withdraw {$operation->getWithdrawId()} canceled");
            $eventName = 'withdraw.notification.canceled';
        }

        $this->operationManager->save($operation);

        $event = new Withdraw($hipayId, $date, $operation);
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

        $event = new BankInfo($hipayId, $date);

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

        $vendor = $this->vendorManager->findByHiPayId($hipayId);

        if ($vendor !== null) {
            $vendor->setHiPayIdentified($status);
            $this->vendorManager->save($vendor);
        }

        $event = new Identification($hipayId, $date);

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

        $event = new Other(
            $hipayId,
            $date,
            $amount,
            $currency,
            $label
        );

        $this->dispatcher->dispatch($eventName, $event);
    }
}
