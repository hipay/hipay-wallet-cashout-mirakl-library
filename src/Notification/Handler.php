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
use HiPay\Wallet\Mirakl\Notification\Model\LogOperationsManagerInterface;

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

    protected $logOperationsManager;

    /**
     * @var FormatNotification class
     */
    protected $formatNotification;

    /** @var  HiPay */
    protected $hipay;

    /** @var  Mirakl */
    protected $mirakl;

    /**
     * Handler constructor.
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param OperationManager $operationManager
     * @param VendorManagerInterface $vendorManager
     * @param LogVendorsManagerInterface $logVendorManager
     * @param ApiFactory $factory
     * @param LogOperationsManagerInterface $logOperationsManager
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        OperationManager $operationManager,
        VendorManagerInterface $vendorManager,
        LogVendorsManagerInterface $logVendorManager,
        ApiFactory $factory,
        LogOperationsManagerInterface $logOperationsManager
    ) {
        parent::__construct($dispatcher, $logger);
        $this->operationManager = $operationManager;
        $this->vendorManager = $vendorManager;
        $this->formatNotification = new FormatNotification();
        $this->hipay = $factory->getHiPay();
        $this->mirakl = $factory->getMirakl();
        $this->logVendorManager = $logVendorManager;
        $this->logOperationsManager = $logOperationsManager;
    }

    /**
     * Handle the notification sent by HiPay.
     *
     * @param $xml
     * @throws ChecksumFailedException
     */
    public function handleHiPayNotification($xml)
    {
        if (!$xml) {
            return;
        }

        $xml = new SimpleXMLElement($xml);

        //Check if callback_salt is updated else use the new callback_salt
        /** @noinspection PhpUndefinedFieldInspection */
        $hipayId = (int)$xml->result->account_id;

        //Find the vendor by his account id
        $vendor = $this->vendorManager->findByHiPayId($hipayId);

        //Call API user-account
        $userAccount = $this->hipay->getAccountHiPay($hipayId);


        if ($vendor !== null) {
            $callback_salt = $vendor->getCallbackSalt();
        } else {
            $callback_salt = $userAccount['callback_salt'];
        }

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
        if (md5($md5string . $callback_salt) != $xml->md5content) {
            throw new ChecksumFailedException($md5string, $callback_salt, $hipayId);
        }

        /** @noinspection PhpUndefinedFieldInspection */
        $operation = (string)$xml->result->operation;
        /** @noinspection PhpUndefinedFieldInspection */
        $status = ($xml->result->status == NotificationStatus::OK);
        /** @noinspection PhpUndefinedFieldInspection */
        $date = new DateTime((string)$xml->result->date . ' ' . (string)$xml->result->time);

        switch ($operation) {
            case Notification::BANK_INFO_VALIDATION:
                $this->bankInfoValidation($vendor, $date, $status);
                break;
            case Notification::IDENTIFICATION:
                $this->identification($hipayId, $date, $status);
                break;
            case Notification::WITHDRAW_VALIDATION:
                /** @noinspection PhpUndefinedFieldInspection */
                $this->withdrawalValidation($hipayId, $date, (string)$xml->result->transid, $status);
                break;
            case Notification::AUTHORIZATION:
            case Notification::CAPTURE:
                $this->transferValidation((string)$xml->result->transid, $status, $operation);
                break;
            case Notification::OTHER:
                /** @noinspection PhpUndefinedFieldInspection */
                $this->other(
                    (float)$xml->result->amount,
                    (string)$xml->result->currency,
                    (string)$xml->result->label,
                    $hipayId,
                    $date,
                    $status
                );
                break;
            case Notification::DOCUMENT_VALIDATION:
                $title = 'Document validation';
                $infos = array(
                    'shopId' => '-',
                    'HipayId' => $hipayId,
                    'Email' => '-',
                    'Type' => 'Error'
                );
                $exceptionMsg = HiPay::LINEMKD . '- Operation: ' . $operation .
                    HiPay::LINEMKD . '- Status: ' . $xml->result->status .
                    HiPay::LINEMKD . '- Message: ' . $xml->result->message .
                    HiPay::LINEMKD . '- Date: ' . $date->format('Y-m-d H:i:s') .
                    HiPay::LINEMKD . '- Document_type: ' . $xml->result->document_type .
                    HiPay::LINEMKD . '- Document_type_label: ' . $xml->result->document_type_label .
                    HiPay::LINEMKD;
                $message = $this->formatNotification->formatMessage($title, $infos, $exceptionMsg);
                $this->logger->info($message, array('miraklId' => null, "action" => "Notification"));
                break;
            default:
                $message = "The operation $operation is not a viable notification operation (hipayId: $hipayId)";
                $this->logger->notice($message, array('miraklId' => null, "action" => "Notification"));
        }
    }


    protected function transferValidation($transferId, $status, $notificationType)
    {
        $operation = $this->operationManager->findOneByTransferId($transferId);

        if (!$operation) {
            $this->logNotFoundOperation($transferId);
            return;
        }

        if ($operation->getStatus() != Status::TRANSFER_REQUESTED) {
            throw new WrongOperationStatus($operation);
        }

        if (!$status) {
            $operation->setStatus(new Status(Status::TRANSFER_FAILED));
            $this->logger->warning(
                "Transfer {$operation->getTransferId()} failed",
                array('miraklId' => $operation->getMiraklId(), "action" => "transfer")
            );
            $eventName = 'transfer.notification.failed';

            $status = Status::TRANSFER_FAILED;
        } elseif ($notificationType === Notification::CAPTURE) {
            $operation->setStatus(new Status(Status::TRANSFER_SUCCESS));
            $this->logger->info(
                "Transfer {$operation->getTransferId()} successful",
                array('miraklId' => $operation->getMiraklId(), "action" => "transfer")
            );
            $eventName = 'transfer.notification.success';

            $status = Status::TRANSFER_SUCCESS;
        } else {
            return;
        }

        $this->operationManager->save($operation);

        $this->logOperation($operation->getMiraklId(), $operation->getPaymentVoucher(), $status, $eventName);
    }

    /**
     * @param int $withdrawalId
     * @param int $hipayId
     * @param DateTime $date
     * @param bool $status
     *
     * @throws Exception
     */
    protected function withdrawalValidation($hipayId, DateTime $date, $withdrawalId, $status)
    {
        $operation = $this->operationManager
            ->findByWithdrawalId($withdrawalId);

        if (!$operation) {
            $this->logNotFoundOperation($withdrawalId);
            return;
        }

        if ($operation->getStatus() != Status::WITHDRAW_REQUESTED) {
            throw new WrongOperationStatus($operation);
        }

        if ($status) {
            $operation->setStatus(new Status(Status::WITHDRAW_SUCCESS));
            $this->logger->info(
                "Withdraw {$operation->getWithdrawId()} successful",
                array('miraklId' => $operation->getMiraklId(), "action" => "Withdraw")
            );
            $eventName = 'withdraw.notification.success';

            $status = Status::WITHDRAW_SUCCESS;
        } else {
            $operation->setStatus(new Status(Status::WITHDRAW_CANCELED));
            $this->logger->info(
                "Withdraw {$operation->getWithdrawId()} canceled",
                array('miraklId' => $operation->getMiraklId(), "action" => "Withdraw")
            );
            $eventName = 'withdraw.notification.canceled';

            $status = Status::WITHDRAW_CANCELED;
        }

        $this->operationManager->save($operation);

        $this->logOperation($operation->getMiraklId(), $operation->getPaymentVoucher(), $status, $eventName);

        $event = new Withdraw($hipayId, $date, $operation);
        $this->dispatcher->dispatch($eventName, $event);
    }

    private function logNotFoundOperation($operationId)
    {
        $message = "No operation was found with this operation Id : 
                        $operationId, this operation might not be related to the connector";
        $this->logger->notice($message, array('miraklId' => null, "action" => "Notification"));
    }

    /**
     * @param VendorInterface $vendor
     * @param DateTime $date
     * @param bool $status
     */
    protected function bankInfoValidation($vendor, $date, $status)
    {
        // if status is OK
        if ($status) {
            $this->mirakl->updateOneVendor(
                array(
                    'kyc' => array(
                        'reason' => '',
                        'status' => 'APPROVED'
                    ),
                    'shop_id' => $vendor->getMiraklId(),
                    'payment_blocked' => false,
                    'suspend' => false
                ));

            $vendor->setPaymentBlocked(false);

            $this->vendorManager->save($vendor);

            $logVendor = $this->logVendorManager->findByMiraklId($vendor->getMiraklId());

            if ($logVendor !== null) {
                $logVendor->setPaymentBlocked(false);
                $this->logVendorManager->save($logVendor);
            }

            $eventName = 'bankInfos.validation.notification.success';
        } else {
            $this->mirakl->updateOneVendor(
                array(
                    'kyc' => array(
                        'reason' => 'Bank info not valid, please upload a new one.',
                        'status' => 'REFUSED'
                    ),
                    'shop_id' => $vendor->getMiraklId(),
                    'suspend' => true
                ));
            $eventName = 'bankInfos.validation.notification.failed';
        }

        $event = new BankInfo($vendor->getHiPayId(), $date);

        $this->dispatcher->dispatch($eventName, $event);
    }

    /**
     * @param int $hipayId
     * @param DateTime $date
     * @param bool $status
     */
    protected function identification($hipayId, $date, $status)
    {
        if ($status) {
            $eventName = 'identification.notification.success';
            $statusRequest = LogVendorsInterface::SUCCESS;
            $statusWalletAccount = LogVendorsInterface::WALLET_IDENTIFIED;
        } else {
            $eventName = 'identification.notification.failed';
            $statusRequest = LogVendorsInterface::WARNING;
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
                $logVendor = $this->logVendorManager->create(
                    $vendor->getMiraklId(),
                    $hipayId,
                    null,
                    $statusWalletAccount,
                    $statusRequest,
                    $eventName,
                    0,
                    $vendor->getCountry(),
                    $vendor->isPaymentBlocked()
                );
                $this->logVendorManager->save($logVendor);
            }
        }

        $event = new Identification($hipayId, $date);

        $this->dispatcher->dispatch($eventName, $event);
    }

    /**
     * @param float $amount
     * @param string $currency
     * @param string $label
     * @param int $hipayId
     * @param DateTime $date
     * @param bool $status
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

        $event = new Other($hipayId, $date, $amount, $currency, $label);

        $this->dispatcher->dispatch($eventName, $event);
    }

    /**
     * @param $miraklId
     * @param $paymentVoucherNumber
     * @param $status
     * @param $message
     */
    private function logOperation($miraklId, $paymentVoucherNumber, $status, $message)
    {
        $logOperation = $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(
            $miraklId,
            $paymentVoucherNumber
        );

        if ($logOperation === null) {
            $this->logger->notice(
                "Could not find existing log for this operations : paymentVoucherNumber = " . $paymentVoucherNumber,
                array("action" => "Process notification", "miraklId" => $miraklId)
            );

            return;
        }

        switch ($status) {
            case Status::WITHDRAW_SUCCESS:
            case Status::WITHDRAW_CANCELED:
            case Status::WITHDRAW_FAILED:
            case Status::WITHDRAW_REQUESTED:
                $logOperation->setStatusWithDrawal($status);
                break;
            case Status::TRANSFER_FAILED:
            case Status::TRANSFER_SUCCESS:
            case Status::TRANSFER_REQUESTED:
                $logOperation->setStatusTransferts($status);
                break;
        }

        $logOperation->setMessage($message);
        $logOperation->setDateCreated(new \DateTime());

        $this->logOperationsManager->save($logOperation);
    }
}
