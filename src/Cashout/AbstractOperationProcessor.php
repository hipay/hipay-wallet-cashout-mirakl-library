<?php


namespace HiPay\Wallet\Mirakl\Cashout;

use HiPay\Wallet\Mirakl\Common\AbstractApiProcessor;
use HiPay\Wallet\Mirakl\Service\Validation\ModelValidator;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorManagerInterface as VendorManager;
use HiPay\Wallet\Mirakl\Notification\Model\LogOperationsManagerInterface as LogOperationsManager;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use HiPay\Wallet\Mirakl\Api\Factory;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\ManagerInterface as OperationManager;
use HiPay\Wallet\Mirakl\Exception\WrongWalletBalance;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\Status;

abstract class AbstractOperationProcessor extends AbstractApiProcessor
{
    const SCALE = 2;

    protected $operationManager;

    protected $vendorManager;

    protected $operator;

    protected $logOperationsManager;

    /**
     * AbstractOperationProcessor constructor.
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param Factory $factory
     * @param OperationManager $operationManager
     * @param VendorManager $vendorManager
     * @param LogOperationsManager $logOperationsManager
     * @param VendorInterface $operator
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        Factory $factory,
        OperationManager $operationManager,
        VendorManager $vendorManager,
        LogOperationsManager $logOperationsManager,
        VendorInterface $operator
    ) {
        parent::__construct($dispatcher, $logger, $factory);

        ModelValidator::validate($operator, 'Operator');
        $this->operator = $operator;

        $this->operationManager = $operationManager;

        $this->vendorManager = $vendorManager;

        $this->logOperationsManager = $logOperationsManager;

    }


    /**
     * Return the right vendor for an operation
     *
     * @param OperationInterface $operation
     *
     * @return VendorInterface|null
     */
    protected function getVendor(OperationInterface $operation)
    {
        if ($operation->getMiraklId()) {
            return $this->vendorManager->findByMiraklId($operation->getMiraklId());
        }
        return $this->operator;
    }

    /**
     * Log Operations
     * @param type $miraklId
     * @param type $paymentVoucherNumber
     * @param type $status
     * @param type $message
     */
    protected function logOperation($miraklId, $paymentVoucherNumber, $status, $message)
    {
        $logOperation = $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(
            $miraklId,
            $paymentVoucherNumber
        );
        if ($logOperation == null) {
            $this->logger->warning(
                "Could not find existing log for this operations : paymentVoucherNumber = " . $paymentVoucherNumber,
                array("action" => "Operation process", "miraklId" => $miraklId)
            );
        } else {
            switch ($status) {
                case Status::WITHDRAW_FAILED :
                case Status::WITHDRAW_NEGATIVE :
                case Status::WITHDRAW_REQUESTED :
                    $logOperation->setStatusWithDrawal($status);
                    break;
                case Status::TRANSFER_FAILED :
                case Status::TRANSFER_NEGATIVE :
                case Status::TRANSFER_SUCCESS :
                    $logOperation->setStatusTransferts($status);
                    break;
                case Status::INVALID_AMOUNT:
                case Status::ADJUSTED_OPERATIONS :
                    $logOperation->setStatusTransferts($status);
                    $logOperation->setStatusWithDrawal($status);
                    break;
            }
            $logOperation->setMessage($message);
            $logOperation->setDateCreated(new \DateTime());
            $this->logOperationsManager->save($logOperation);
        }
    }

    /**
     * Check if technical account has sufficient funds.
     *
     * @param $amount
     * @param $vendor
     * @param bool $transfer
     * @throws WrongWalletBalance
     */
    public function hasSufficientFunds($amount, $vendor, $transfer = false)
    {
        $balance = round($this->hipay->getBalance($vendor), static::SCALE);

        if ($balance < round($amount, static::SCALE)) {
            if ($transfer) {
                throw new WrongWalletBalance('technical', 'transfer', $amount, $balance);
            }

            throw new WrongWalletBalance($vendor->getHipayId(), 'withdraw', $amount, $balance);
        }
    }
}