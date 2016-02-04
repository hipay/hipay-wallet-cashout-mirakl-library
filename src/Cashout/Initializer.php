<?php

namespace HiPay\Wallet\Mirakl\Cashout;

use DateTime;
use Exception;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\Status;
use HiPay\Wallet\Mirakl\Common\AbstractProcessor;
use HiPay\Wallet\Mirakl\Api\Mirakl\ConfigurationInterface
    as MiraklConfiguration;
use HiPay\Wallet\Mirakl\Api\HiPay\ConfigurationInterface
    as HiPayConfiguration;
use HiPay\Wallet\Mirakl\Exception\AlreadyCreatedOperationException;
use HiPay\Wallet\Mirakl\Cashout\Model\Transaction\ValidatorInterface;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\ManagerInterface
    as OperationManager;
use HiPay\Wallet\Mirakl\Exception\DispatchableException;
use HiPay\Wallet\Mirakl\Exception\ValidationFailedException;
use HiPay\Wallet\Mirakl\Vendor\Model\ManagerInterface
    as VendorManager;
use HiPay\Wallet\Mirakl\Exception\InvalidOperationException;
use HiPay\Wallet\Mirakl\Exception\NotEnoughFunds;
use HiPay\Wallet\Mirakl\Exception\TransactionException;
use HiPay\Wallet\Mirakl\Service\Validation\ModelValidator;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class Initializer
 * Generate and save the operation to be executed by the processor.
 * Use bc function
 * http://php.net/manual/en/book.bc.php
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Initializer extends AbstractProcessor
{
    const SCALE = 2;

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
     * @param HiPayConfiguration       $hipayConfig
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
        HiPayConfiguration $hipayConfig,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        VendorInterface $operatorAccount,
        VendorInterface $technicalAccount,
        ValidatorInterface $transactionValidator,
        OperationManager $operationHandler,
        VendorManager $vendorManager
    ) {
        parent::__construct($miraklConfig, $hipayConfig, $dispatcher, $logger);

        ModelValidator::validate($operatorAccount, 'Operator');
        $this->operator = $operatorAccount;

        ModelValidator::validate($technicalAccount, 'Operator');
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

        //Fetch 'PAYMENT' transaction
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

        $paymentDebits = array();
        $count = 0;
        foreach ($paymentTransactions as $transaction) {
            $paymentDebits[$transaction['payment_voucher_number']][$transaction['shop_id']] =
                $transaction['amount_debited'];
            $count++;
        }

        $transactionError = null;
        $operations = array();

        $this->logger->info('[OK] Fetched '. $count . ' payment transactions');

        //Compute amounts (vendor and operator) by payment vouchers
        $this->logger->info('Compute amounts and create vendor operation');
        foreach ($paymentDebits as $paymentVoucher => $debitedAmounts) {
            $voucherOperations = $this->handlePaymentVoucher($paymentVoucher, $debitedAmounts, $cycleDate);
            if ($voucherOperations) {
                $operations = array_merge($voucherOperations, $operations);
            } else {
                $transactionError[] = $paymentVoucher;
            }
        }

        if ($transactionError) {
            foreach ($transactionError as $voucher) {
                $this->logger->error("The transaction for the payment voucher number $voucher are wrong");
            }
            return;
        }

        $totalAmount = $this->sumOperationAmounts($operations);
        $this->logger->debug("Total amount " . $totalAmount);

        $this->logger->info(
            "Check if technical account has sufficient funds"
        );
        if (!$this->hasSufficientFunds($totalAmount)) {
            $this->handleException(new NotEnoughFunds());
            return;
        }
        $this->logger->info('[OK] Technical account has sufficient funds');

        $this->saveOperations($operations);
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
     * Create the operations for a payment voucher
     *
     * @param $paymentVoucher
     * @param $debitedAmountsByShop
     * @param $cycleDate
     * @return bool|array
     */
    public function handlePaymentVoucher($paymentVoucher, $debitedAmountsByShop, $cycleDate)
    {
        $operatorAmount = 0;
        $transactionError = false;
        $this->logger->debug(
            "Payment Voucher : $paymentVoucher",
            array('paymentVoucherNumber' => $paymentVoucher)
        );
        $orderTransactions = array();
        $operations = array();
        foreach ($debitedAmountsByShop as $shopId => $debitedAmount) {
            try {
                $this->logger->debug(
                    "ShopId : $shopId",
                    array('shopId' => $shopId)
                );

                //Fetch the corresponding order transactions
                $orderTransactions = $this->getOrderTransactions(
                    $shopId,
                    $paymentVoucher
                );

                //Compute the vendor amount for this payment voucher
                $vendorAmount = $this->computeVendorAmount(
                    $orderTransactions,
                    $debitedAmount
                );

                $this->logger->debug("Vendor amount " . $vendorAmount);

                //Create the vendor operation
                $operations[] = $this->createOperation(
                    $vendorAmount,
                    $cycleDate,
                    $paymentVoucher,
                    $shopId
                );

                //Compute the operator amount for this payment voucher
                $operatorAmount = round($operatorAmount, static::SCALE) +
                    round($this->computeOperatorAmountByVendor($orderTransactions), static::SCALE);
            } catch (Exception $e) {
                $transactionError = true;
                /** @var Exception $transactionError */
                $this->handleException(
                    new TransactionException(
                        $orderTransactions,
                        $e->getMessage(),
                        $e->getCode(),
                        $e
                    )
                );
            }
        };

        $this->logger->debug("Operator amount " . $operatorAmount);

        // Create operator operation
        $operations[] = $this->createOperation(
            $operatorAmount,
            $cycleDate,
            $paymentVoucher
        );
        return $transactionError ? false : $operations;
    }

    /**
     * Fetch from mirakl the payment related to the orders.
     *
     * @param int $shopId
     * @param string $paymentVoucher
     *
     * @return array
     */
    protected function getOrderTransactions($shopId, $paymentVoucher)
    {
        $transactions = $this->mirakl->getTransactions(
            $shopId,
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
     * @param array $transactions
     * @param int $payedAmount
     *
     * @return string
     *
     * @throws Exception
     */
    protected function computeVendorAmount(
        $transactions,
        $payedAmount
    ) {
        $amount = 0;
        $errors = false;
        foreach ($transactions as $transaction) {
            $amount +=  round($transaction['amount_credited'], static::SCALE)
                - round($transaction['amount_debited'], static::SCALE);
            $errors |= !$this->transactionValidator->isValid($transaction);
        }
        if (round($amount, static::SCALE) != round($payedAmount, static::SCALE)) {
            throw new TransactionException(
                array($transactions),
                'There is a difference between the transactions'.
                PHP_EOL."$amount for the transactions".
                PHP_EOL."{$payedAmount} for the earlier payment transaction"
            );
        }
        if ($errors) {
            throw new TransactionException(
                array($transactions),
                'There are errors in the transactions'
            );
        }
        return $amount;
    }

    /**
     * Create the vendor operation
     * dispatch <b>after.operation.create</b>.
     *
     * @param int $amount
     * @param DateTime $cycleDate
     * @param string $paymentVoucher
     * @param bool|int $miraklId false if it an operator operation
     *
     * @return OperationInterface
     */
    protected function createOperation(
        $amount,
        DateTime $cycleDate,
        $paymentVoucher,
        $miraklId = null
    ) {
        if ($amount <= 0) {
            $this->logger->notice("Operation wasn't created du to null amount");
        }
        //Call implementation function
        $operation = $this->operationManager->create($amount, $cycleDate, $paymentVoucher, $miraklId);

        //Set hipay id
        $hipayId = null;
        if ($miraklId) {
            $vendor = $this->vendorManager->findByMiraklId($miraklId);
            if ($vendor) {
                $hipayId = $vendor->getHiPayId();
            }
        } else {
            $hipayId = $this->operator->getHiPayId();
        }
        $operation->setHiPayId($hipayId);

        //Sets mandatory values
        $operation->setMiraklId($miraklId);
        $operation->setStatus(new Status(Status::CREATED));
        $operation->setUpdatedAt(new DateTime());
        $operation->setAmount($amount);
        $operation->setCycleDate($cycleDate);
        $operation->setPaymentVoucher($paymentVoucher);
        return $operation;
    }

    /**
     * Compute the amount due to the operator by vendor.
     *
     * @param $transactions
     *
     * @return float
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
                $amount += round($transaction['amount_credited'], static::SCALE)
                    - round($transaction['amount_debited'], static::SCALE);
            }
        }

        return (-1) * round($amount, static::SCALE);
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
     * Sum operations amounts
     *
     * @param array $operations
     * @return mixed
     */
    public function sumOperationAmounts(array $operations)
    {
        $scale = static::SCALE;
        return array_reduce($operations, function ($carry, OperationInterface $item) use ($scale) {
            $carry = round($carry, $scale) + round($item->getAmount(), $scale);
            return $carry;
        }, 0);
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
        return bccomp($this->hipay->getBalance($this->technicalAccount), $amount, static::SCALE) >= 0;
    }

    /**
     * Save operations
     *
     * @param array $operations
     */
    public function saveOperations(array $operations)
    {
        if ($this->areOperationsValid($operations)) {
            $this->logger->info('[OK] Operations validated');

            $this->logger->info('Save operations');
            $this->operationManager->saveAll($operations);
            $this->logger->info('[OK] Operations saved');
        } else {
            $this->logger->error('Some operation were wrong. Operations not saved');
        }
    }

    /**
     * Validate operations
     *
     * @param OperationInterface[] $operations
     *
     * @return bool
     */
    protected function areOperationsValid(array $operations)
    {
        //Valid the operation and check if operation wasn't created before
        $this->logger->info('Validate the operations');

        $operationError = false;
        /** @var OperationInterface $operation */
        foreach ($operations as $operation) {
            try {
                $this->validateOperation($operation);
            } catch (DispatchableException $e) {
                $operationError = true;
                $this->handleException($e);
            }
        }

        return !$operationError;
    }

    /**
     * Validate an operation
     *
     * @param OperationInterface $operation
     * @throws AlreadyCreatedOperationException
     * @throws InvalidOperationException
     * @throws ValidationFailedException
     */
    public function validateOperation(OperationInterface $operation)
    {
        if ($this->operationManager
            ->findByMiraklIdAndPaymentVoucherNumber(
                $operation->getMiraklId(),
                $operation->getPaymentVoucher()
            )
        ) {
            throw new AlreadyCreatedOperationException($operation);
        }

        if (!$this->operationManager->isValid($operation)) {
            throw new InvalidOperationException($operation);
        }

        ModelValidator::validate($operation);
    }
}
