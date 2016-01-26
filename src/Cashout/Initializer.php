<?php

namespace Hipay\MiraklConnector\Cashout;

use DateTime;
use Exception;
use Hipay\MiraklConnector\Cashout\Event\CreateOperation;
use Hipay\MiraklConnector\Cashout\Model\Operation\OperationInterface;
use Hipay\MiraklConnector\Cashout\Model\Operation\Status;
use Hipay\MiraklConnector\Common\AbstractProcessor;
use Hipay\MiraklConnector\Api\Mirakl\ConfigurationInterface
    as MiraklConfiguration;
use Hipay\MiraklConnector\Api\Hipay\ConfigurationInterface
    as HipayConfiguration;
use Hipay\MiraklConnector\Exception\AlreadyCreatedOperationException;
use Hipay\MiraklConnector\Exception\DispatchableException;
use Hipay\MiraklConnector\Exception\Event\ThrowException;
use Hipay\MiraklConnector\Cashout\Model\Transaction\ValidatorInterface;
use Hipay\MiraklConnector\Cashout\Model\Operation\ManagerInterface
    as OperationManager;
use Hipay\MiraklConnector\Vendor\Model\ManagerInterface
    as VendorManager;
use Hipay\MiraklConnector\Exception\InvalidOperationException;
use Hipay\MiraklConnector\Exception\NotEnoughFunds;
use Hipay\MiraklConnector\Exception\TransactionException;
use Hipay\MiraklConnector\Service\Validation\ModelValidator;
use Hipay\MiraklConnector\Vendor\Model\VendorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class Initializer
 * Generate and save the operation to be executed by the processor.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Initializer extends AbstractProcessor
{
    /** @var VendorInterface */
    protected $operator;

    /** @var VendorInterface */
    protected $technicalAccount;

    /** @var  ValidatorInterface */
    protected $transactionValidator;

    /** @var OperationManager */
    protected $operationManager;

    /** @var  VendorManager */
    protected $vendorManager;

    /**
     * Initializer constructor.
     *
     * @param MiraklConfiguration      $miraklConfig
     * @param HipayConfiguration       $hipayConfig
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface          $logger
     * @param VendorInterface          $operatorAccount
     * @param VendorInterface          $technicalAccount
     * @param ValidatorInterface       $transactionValidator
     * @param OperationManager         $operationHandler
     * @param VendorManager            $vendorManager
     */
    public function __construct(
        MiraklConfiguration $miraklConfig,
        HipayConfiguration $hipayConfig,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        VendorInterface $operatorAccount,
        VendorInterface $technicalAccount,
        ValidatorInterface $transactionValidator,
        OperationManager $operationHandler,
        VendorManager $vendorManager
    ) {
        parent::__construct($miraklConfig, $hipayConfig, $dispatcher, $logger);
        $this->operator = $operatorAccount;
        $this->technicalAccount = $technicalAccount;
        $this->operationManager = $operationHandler;
        $this->transactionValidator = $transactionValidator;
        $this->vendorManager = $vendorManager;
    }

    /**
     * Main processing function
     * Generate and save operations.
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param DateTime $cycleDate
     *
     * @throws Exception
     */
    public function process(
        DateTime $startDate,
        DateTime $endDate,
        DateTime $cycleDate
    ) {
        $this->logger->info('Cashout Initializer');

        $this->logger->info(
            'Fetch payment transaction from Mirakl from '.
            $startDate->format('Y-m-d H:i').
            ' to '.
            $endDate->format('Y-m-d H:i')
        );
        $paymentTransactions = $this->getPaymentTransactions(
            $startDate,
            $endDate
        );
        $this->logger->info(
            '[OK] Fetched '.
            count($paymentTransactions).
            ' payment transactions'
        );

        $paymentVouchersByShopId = $this->indexArray(
            $paymentTransactions,
            'shop_id',
            array('payment_voucher_number')
        );
        $paymentDebitByPaymentVoucher = $this->indexArray(
            $paymentTransactions,
            'payment_voucher_number',
            array('amount_debited')
        );
        $operatorAmount = 0;
        $totalAmount = 0;
        $operations = array();
        $transactionError = null;

        $this->logger->info('Compute amounts and create vendor operation');
        foreach ($paymentVouchersByShopId as $miraklId => $paymentVouchers) {
            $this->logger->debug(
                "ShopId : $miraklId",
                array('shopId' => $miraklId)
            );
            $vendorAmount = 0;
            $orderTransactions = array();
            foreach ($paymentVouchers as $paymentVoucher) {
                try {
                    $orderTransactions = $this->getOrderTransactions(
                        $paymentVoucher
                    );

                    $vendorAmount += $this->computeVendorAmount(
                        $orderTransactions,
                        $paymentDebitByPaymentVoucher[$paymentVoucher]
                    );

                    $operatorAmount += $this->computeOperatorAmountByVendor(
                        $orderTransactions
                    );
                } catch (Exception $e) {
                    $this->logger->warning($e);
                    /** @var Exception $transactionError */
                    $transactionError = new TransactionException(
                        $orderTransactions,
                        $e->getMessage(),
                        $e->getCode(),
                        $transactionError
                    );
                }
            };
            $this->logger->debug("Vendor amount " . $vendorAmount);
            $totalAmount += $vendorAmount;

            $vendor = $this->vendorManager->findByMiraklId($miraklId);
            if ($vendorAmount && $vendor) {
                //Create the vendor operation
                $operations[] = $this->createOperation(
                    $vendorAmount,
                    $cycleDate,
                    $vendor->getHipayId(),
                    $miraklId
                );
            } else {
                $this->logger->notice(
                    "Vendor operation wasn't created"
                );
            }
        }
        $this->logger->debug("Operator amount " . $operatorAmount);
        $totalAmount += $operatorAmount;
        $this->logger->debug("Total amount " . $totalAmount);
        if ($operatorAmount) {
            // Create operator operation
            $operations[] = $this->createOperation(
                $operatorAmount,
                $cycleDate,
                $this->operator->getHipayId()
            );
        } else {
            $this->logger->notice(
                "Operator operation wasn't created due to nul amount"
            );
        }

        if ($transactionError) {
            $this->dispatcher->dispatch(
                'transaction.validation.failed',
                new ThrowException($transactionError)
            );
            throw $transactionError;
        }

        $this->logger->info(
            "Check if technical account has sufficient funds"
        );
        if (!$this->hasSufficientFunds($totalAmount)) {
            throw new NotEnoughFunds();
        }
        $this->logger->info('[OK] Technical account has sufficient funds');

        //Valid the operation and check if operation wasn't created before
        $this->logger->info('Validate the operations');
        /**
         * @var int                index
         * @var OperationInterface $operation
         */
        foreach ($operations as $index => $operation) {
            try {
                if ($this->operationManager
                    ->findByHipayIdAndCycleDate(
                        $operation->getMiraklId(),
                        $operation->getCycleDate()
                    )
                ) {
                    throw new AlreadyCreatedOperationException($operation);
                }
                if (!$this->operationManager->isValid($operation)) {
                    throw new InvalidOperationException($operation);
                }

                ModelValidator::validate($operation);
            } catch (DispatchableException $e) {
                $this->logger->warning($e->getMessage());

                //remove faulty operation
                unset($operations[$index]);

                $this->dispatcher->dispatch(
                    $e->getEventName(),
                    new ThrowException($e)
                );
            }
        }
        $this->logger->info('[OK] Operations validated');

        $this->logger->info('Save operations');
        $this->operationManager->saveAll($operations);
        $this->logger->info('[OK] Operations saved');
    }

    /**
     * Fetch from mirakl the payments transaction.
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     *
     * @return array
     */
    protected function getPaymentTransactions(
        DateTime $startDate,
        DateTime $endDate
    ) {
        $transactions = $this->mirakl->getTransactions(
            null,
            $startDate,
            $endDate,
            null,
            null,
            null,
            null,
            null,
            array('PAYMENT')
        );

        return $transactions;
    }

    /**
     * Fetch from mirakl the payment related to the orders.
     *
     * @param $paymentVoucher
     *
     * @return array
     *
     * @throws Exception
     */
    protected function getOrderTransactions($paymentVoucher)
    {
        $transactions = $this->mirakl->getTransactions(
            null,
            null,
            null,
            null,
            null,
            null,
            $paymentVoucher,
            null,
            $this->getOrderTransactionTypes()
        );

        return $transactions;
    }

    /**
     * Returns the transaction types to get on the second call from to TL01.
     *
     * @return array
     */
    protected function getOrderTransactionTypes()
    {
        return array(
            'COMMISSION_FEE',
            'COMMISSION_VAT',
            'REFUND_COMMISSION_FEE',
            'REFUND_COMMISSION_VAT',
            'SUBSCRIPTION_FEE',
            'SUBSCRIPTION_VAT',
            'ORDER_AMOUNT',
            'ORDER_SHIPPING_AMOUNT',
            'REFUND_ORDER_SHIPPING_AMOUNT',
            'REFUND_ORDER_AMOUNT',
            'MANUAL_CREDIT',
            'MANUAL_CREDIT_VAT',
        );
    }

    /**
     * Compute the vendor amount to withdrawn from the technical account.
     *
     * @param $transactions
     * @param $paymentTransaction
     *
     * @return int
     *
     * @throws Exception
     */
    protected function computeVendorAmount(
        $transactions,
        $paymentTransaction
    ) {
        $amount = 0;
        $errors = false;
        foreach ($transactions as $transaction) {
            $amount += $transaction['amount_credited'] - $transaction['amount_debited'];
            $errors |= !$this->transactionValidator->isValid($transaction);
        }
        if (round($amount, 2) !=
            round($paymentTransaction['amount_debited'], 2)
        ) {
            throw new TransactionException(
                'There is a difference between the transactions'.
                PHP_EOL."$amount for the transactions".
                PHP_EOL."{$paymentTransaction['amount_debited']} for the earlier payment transaction"
            );
        }
        if ($errors) {
            throw new TransactionException(
                'There are errors in the transactions'
            );
        }

        return $amount;
    }

    /**
     * Compute the amount due to the operator by vendor.
     *
     * @param $transactions
     *
     * @return int
     */
    protected function computeOperatorAmountByVendor($transactions)
    {
        $amount = 0;
        foreach ($transactions as $transaction) {
            if (in_array(
                $transaction['transaction_type'],
                $this->getOperatorTransactionTypes()
            )
            ) {
                $amount += $transaction['amount_credited'] - $transaction['amount_debited'];
            }
        }

        return (-1) * $amount;
    }

    /**
     * Return the transaction type used to calculate the operator amount.
     *
     * @return array
     */
    protected function getOperatorTransactionTypes()
    {
        return array(
            'COMMISSION_FEE',
            'COMMISSION_VAT',
            'REFUND_COMMISSION_FEE',
            'REFUND_COMMISSION_VAT',
            'SUBSCRIPTION_FEE',
            'SUBSCRIPTION_VAT',
        );
    }

    /**
     * Create the vendor operation
     * dispatch <b>after.operation.create</b>.
     *
     * @param int      $amount
     * @param DateTime $cycleDate
     * @param $hipayId
     * @param bool|int $shopId false if it an operator operation
     *
     * @return OperationInterface
     */
    protected function createOperation(
        $amount,
        DateTime $cycleDate,
        $hipayId,
        $shopId = false
    ) {
        $operation = $this->operationManager->create($shopId);
        $event = new CreateOperation($operation);
        $this->dispatcher->dispatch('after.operation.create', $event);
        $operation = $event->getOperation();

        //Sets mandatory value
        $operation->setHipayId($hipayId);
        $operation->setStatus(new Status(Status::CREATED));
        $operation->setAmount($amount);
        $operation->setCycleDate($cycleDate);

        return $operation;
    }

    /**
     * Check if technical account has sufficient funds.
     *
     * @param $amount
     *
     * @returns boolean
     */
    public function hasSufficientFunds($amount)
    {
        return $this->hipay->getBalance($this->technicalAccount) >= $amount;
    }
}
