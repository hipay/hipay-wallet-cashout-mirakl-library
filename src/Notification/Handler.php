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
use HiPay\Wallet\Mirakl\Api\Factory as ApiFactory;
use HiPay\Wallet\Mirakl\Api\HiPay;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use HiPay\Wallet\Mirakl\Notification\Model\LogVendorsInterface;
use HiPay\Wallet\Mirakl\Notification\Model\LogVendorsManagerInterface;

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
    protected $logVendorManager;

    /**
     * @var FormatNotification class
     */
    protected $formatNotification;

    /** @var  HiPay */
    protected $hipay;

    /**
     * Handler constructor.
     *
     * @param OperationManager $operationManager
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param VendorManagerInterface $vendorManager
     */
    public function __construct(
    EventDispatcherInterface $dispatcher, LoggerInterface $logger, OperationManager $operationManager,
    VendorManagerInterface $vendorManager, LogVendorsManagerInterface $logVendorManager, ApiFactory $factory
    )
    {
        parent::__construct($dispatcher, $logger);
        $this->operationManager   = $operationManager;
        $this->vendorManager      = $vendorManager;
        $this->formatNotification = new FormatNotification();
        $this->hipay              = $factory->getHiPay();
        $this->logVendorManager   = $logVendorManager;
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

        //Check if callback_salt is updated else use the new callback_salt
        /** @noinspection PhpUndefinedFieldInspection */
        $hipayId = (int) $xml->result->account_id;

        //Find the vendor by his account id
        $vendor = $this->vendorManager->findByHiPayId($hipayId);

        //Call API user-account
        $userAccount = $this->hipay->getAccountHiPay($hipayId);

        $callback_salt = $vendor->getCallbackSalt();

        //Check if callback_salt is changed
        if ($callback_salt != $userAccount['callback_salt']) {
            //Save the new callback_salt
            $vendor->setCallbackSalt($userAccount['callback_salt']);
            $this->vendorManager->save($vendor);
            $callback_salt = $vendor->getCallbackSalt();
        }

        //Check content
        /** @noinspection PhpUndefinedFieldInspection */
        $md5string = strtr($xml->result->asXML(), array("\n" => '', "\t" => ''));
        $md5string = trim(preg_replace("#\>( )+?\<#", "><", $md5string));

        /** @noinspection PhpUndefinedFieldInspection */
        if (md5($md5string.$callback_salt) != $xml->md5content) {
            throw new ChecksumFailedException();
        }

        /** @noinspection PhpUndefinedFieldInspection */
        $operation = (string) $xml->result->operation;
        /** @noinspection PhpUndefinedFieldInspection */
        $status    = ($xml->result->status == NotificationStatus::OK);
        /** @noinspection PhpUndefinedFieldInspection */
        $date      = new DateTime((string) $xml->result->date.' '.(string) $xml->result->time);

        switch ($operation) {
            case Notification::BANK_INFO_VALIDATION:
                $this->bankInfoValidation(
                    $hipayId, $date, $status
                );
                break;
            case Notification::IDENTIFICATION:
                $this->identification(
                    $hipayId, $date, $status
                );
                break;
            case Notification::WITHDRAW_VALIDATION:
                /** @noinspection PhpUndefinedFieldInspection */
                $this->withdrawalValidation(
                    $hipayId, $date, (string) $xml->result->transid, $status
                );
                break;
            case Notification::OTHER:
                /** @noinspection PhpUndefinedFieldInspection */
                $this->other(
                    (float) $xml->result->amount, (string) $xml->result->currency, (string) $xml->result->label,
                    $hipayId, $date, $status
                );
                break;
            case Notification::DOCUMENT_VALIDATION:
                $title        = 'Error - Document validation';
                $infos        = array(
                    'shopId' => '-',
                    'HipayId' => $hipayId,
                    'Email' => '-',
                    'Type' => 'Error'
                );
                $exceptionMsg = implode(
                    HiPay::LINEMKD.HiPay::SEPARMKD.'- ',
                    array(
                    'Operation' => $operation,
                    'Status' => $xml->result->status,
                    'Message' => $xml->result->message,
                    'Date' => $date->format('Y-m-d H:i:s'),
                    'Document_type' => $xml->result->document_type,
                    'Document_type_label' => $xml->result->document_type_label,
                ));
                $exceptionMsg = HiPay::LINEMKD.HiPay::SEPARMKD.'- Operation: '.$operation.
                    HiPay::LINEMKD.HiPay::SEPARMKD.'- Status: '.$xml->result->status.
                    HiPay::LINEMKD.HiPay::SEPARMKD.'- Message: '.$xml->result->message.
                    HiPay::LINEMKD.HiPay::SEPARMKD.'- Date: '.$date->format('Y-m-d H:i:s').
                    HiPay::LINEMKD.HiPay::SEPARMKD.'- Document_type: '.$xml->result->document_type.
                    HiPay::LINEMKD.HiPay::SEPARMKD.'- Document_type_label: '.$xml->result->document_type_label.
                    HiPay::LINEMKD.HiPay::SEPARMKD;
                $message      = $this->formatNotification->formatMessage($title, $infos, $exceptionMsg);
                $this->logger->error($message);
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
    $hipayId, DateTime $date, $withdrawalId, $status
    )
    {
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
            $eventName           = 'identification.notification.success';
            $statusRequest       = LogVendorsInterface::SUCCESS;
            $statusWalletAccount = LogVendorsInterface::WALLET_IDENTIFIED;
        } else {
            $eventName           = 'identification.notification.failed';
            $statusRequest       = LogVendorsInterface::WARNING;
            $statusWalletAccount = LogVendorsInterface::WALLET_NOT_IDENTIFIED;
        }

        $vendor = $this->vendorManager->findByHiPayId($hipayId);

        if ($vendor !== null) {
            $vendor->setHiPayIdentified($status);
            $this->vendorManager->save($vendor);
            $logVendor = $this->logVendorManager->findByMiraklId($vendor->getMiraklId());

            if ($logVendor !== null) {
                $logVendor->setStatusWalletAccount($statusWalletAccount);
                $logVendor->setStatus($statusRequest);
                $logVendor->setMessage($eventName);
                $this->logVendorManager->save($logVendor);
            } else {
                $logVendor = $this->logVendorManager->create($vendor->getMiraklId(), $hipayId, null,
                                                             $statusWalletAccount, $statusRequest, $eventName, 0);
                $this->logVendorManager->save($logVendor);
            }
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
    $amount, $currency, $label, $hipayId, $date, $status
    )
    {
        if ($status) {
            $eventName = 'other.notification.success';
        } else {
            $eventName = 'other.notification.failed';
        }

        $event = new Other(
            $hipayId, $date, $amount, $currency, $label
        );

        $this->dispatcher->dispatch($eventName, $event);
    }
}