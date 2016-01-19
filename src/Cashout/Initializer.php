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
use Hipay\MiraklConnector\Exception\DispatchableException;
use Hipay\MiraklConnector\Exception\Event\ThrowException;
use Hipay\MiraklConnector\Service\ModelValidator;
use Hipay\MiraklConnector\Cashout\Model\Transaction\ValidatorInterface;
use Hipay\MiraklConnector\Cashout\Model\Operation\ManagerInterface;
use Hipay\MiraklConnector\Vendor\Model\VendorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class Validator
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

    /** @var ManagerInterface */
    protected $operationManager;

    /**
     * Initializer constructor.
     * @param MiraklConfiguration $miraklConfig
     * @param HipayConfiguration $hipayConfig
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param VendorInterface $operatorAccount
     * @param VendorInterface $technicalAccount
     * @param ValidatorInterface $transactionValidator
     * @param ManagerInterface $operationHandler
     */
    public function __construct(
        MiraklConfiguration $miraklConfig,
        HipayConfiguration $hipayConfig,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        VendorInterface $operatorAccount,
        VendorInterface $technicalAccount,
        ValidatorInterface $transactionValidator,
        ManagerInterface $operationHandler
    )
    {
        parent::__construct($miraklConfig, $hipayConfig, $dispatcher, $logger);
        $this->operator = $operatorAccount;
        $this->technicalAccount = $technicalAccount;
        $this->operationManager = $operationHandler;
    }

    /**
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
    )
    {
        $this->logger->info("Cachout Initializer");
        $this->logger->info(
            "[OK] Fetch payment transaction from Mirakl from "
            . $startDate->format("Y-m-d") . " to " . $endDate->format("Y-m-d")
        );

        $paymentTransactions = $this->getPayementTransactions(
            $startDate,
            $endDate
        );

        $this->logger->info(
            "[OK] Fetched " . count($paymentTransactions) . "payment transactions"
        );

        $paymentVouchersByShopId = $this->indexArray(
            $paymentTransactions,
            'shop_id',
            array('payment_voucher')
        );

        $balancesByPaymentVoucher = $this->indexArray(
            $paymentTransactions,
            'payment_voucher',
            array('balance')
        );
        $operatorAmount = 0;
        $totalAmount = 0;

        $operations = array();
        $transactionError = false;
        $this->logger->info("Compute amounts and create vendor operation");
        foreach ($paymentVouchersByShopId as $shopId => $paymentVouchers) {
            $this->logger->debug(
                "ShopId : $shopId", array("shopId" => $shopId)
            );
            $vendorAmount = 0;
            foreach ($paymentVouchers as $paymentVoucher) {
                try {
                    $orderTransactions = $this->getOrderTransactions(
                        $paymentVoucher
                    );

                    $vendorAmount += $this->computeVendorAmount(
                        $orderTransactions,
                        $balancesByPaymentVoucher[$paymentVoucher]
                    );

                    $operatorAmount += $this->computeOperatorAmountByVendor(
                        $orderTransactions
                    );
                } catch (Exception $e) {
                    $this->logger->warning($e);
                    /** @var Exception $transactionError */
                    if ($transactionError) {
                        $transactionError = new Exception(
                            $e->getMessage(),
                            $e->getCode()
                        );
                    } else {
                        $transactionError = new Exception(
                            $e->getMessage(),
                            $e->getCode(),
                            $transactionError
                        );
                    }
                }
            };
            $totalAmount += $vendorAmount;

            //Create the vendor operation
            $this->logger->info("Create vendor operation (not saved)");
            $operations[] = $this->createOperation(
                $vendorAmount, $startDate, $shopId
            );
        }
        $totalAmount += $operatorAmount;
        if ($transactionError) {
            $this->dispatcher->dispatch(
                'transaction.validation.failed',
                new ThrowException($transactionError)
            );
            throw $transactionError;
        }
        $this->logger->info(
            "Check if technical account has sufficient funds ($totalAmount)"
        );

        if (!$this->hasSufficientFunds($totalAmount)) {
            throw new Exception("No enough funds in the tech account");
        }

        $this->logger->info("[OK] Technical account has sufficient funds");

        // Create operator operation
        $this->logger->info("Create operator operation");
        $operations[] = $this->createOperation($operatorAmount, $cycleDate);

        //Valid the operation and check if operation wasn't created before
        $this->logger->info("Validate the operations");
        foreach ($operations as $index => $operation) {
            try {
                ModelValidator::validate($operation);
                if (!$this->operationManager->isSavable($operation)) {
                    unset($operations[$index]);
                }
            } catch (DispatchableException $e) {
                $this->logger->warning($e->getMessage());

                $this->dispatcher->dispatch(
                    $e->getEventName(),
                    new ThrowException($e)
                );

                //remove faulty operation
                unset($operations[$index]);
            }
        }
        $this->logger->info("[OK] Operations validated");

        $this->logger->info("Save operations");
        $this->operationManager->saveAll($operations);
        $this->logger->info("[OK] Operations saved");
    }

    /**
     * Fetch from mirakl the payements transaction
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     *
     * @return array
     */
    protected function getPayementTransactions(
        DateTime $startDate,
        DateTime $endDate
    )
    {
        $transactions = $this->mirakl->getTransactions(
            null,
            $startDate,
            $endDate,
            null,
            null,
            null,
            null,
            null,
            array("PAYMENT")
        );
        return $transactions;
    }

    /**
     * Create an associative array with an index key
     *
     * @param array $array
     * @param $indexKey
     * @param array $keptKeys
     * @return array
     */
    protected function indexArray(
        array $array,
        $indexKey,
        array $keptKeys = array()
    )
    {
        $result = array_column($indexKey, $array);
        $result = array_flip($result);
        foreach ($array as $element) {
            $keptKeys = (count($keptKeys) > 0) ?: array_keys($element);
            $insertedElement = array_intersect_key($element, $keptKeys);
            $result[$element[$indexKey]][] = (count($keptKeys) == 1) ?
                current($insertedElement) : $insertedElement;
        }
        return $result;
    }

    /**
     * Fetch from mirakl the payment related to the orders
     *
     * @param $paymentVoucher
     * @return array
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
     * Returns the transaction types to get on the second call from to TL01
     *
     * @return array
     */
    protected function getOrderTransactionTypes()
    {
        return array(
            "COMMISION_FEE",
            "COMMISION_VAT",
            "REFUND_COMMISION_FEE",
            "REFUND_COMMISION_VAT",
            "SUBSRIRCTION_FEE",
            "SUBSRIRCTION_VAT",
            "ORDER_AMOUNT",
            "ORDER_SHIPPING_AMOUNT",
            "REFUND_ORDER_SHIPPING_AMOUNT",
            "REFUND_ORDER_AMOUNT",
            "MANUAL_CREDIT",
            "MANUAL_CREDIT_VAT"
        );
    }

    /**
     * Compute the vendor amount to withdrawed from the technical account
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
    )
    {
        $amount = 0;
        $errors = false;
        foreach ($transactions as $transaction) {
            $amount += $transaction['balance'];
            $errors |= !$this->transactionValidator->isValid($transaction);
        }
        if ($amount != $paymentTransaction['balance']) {
            throw new Exception(
                "There is a difference between the transactions
                \n $amount for the transactions
                \n {$paymentTransaction['balance']} for the earlier payment transaction"
            );
        }
        if ($errors) {
            throw new Exception(
                "There are errors in the transactions"
            );
        }
        return $amount;
    }

    /**
     * @param $transactions
     * @return int
     */
    protected function computeOperatorAmountByVendor($transactions)
    {
        $amount = 0;
        foreach ($transactions as $transaction) {
            if (
                in_array(
                    $transaction['transaction_type'],
                    $this->getOperatorTransactionTypes()
                )
            ) {
                $amount += $transaction['balance'];
            }
        }
        return (-1) * $amount;
    }

    /**
     * Return the transaction type used to calculate the operator amount
     *
     * @return array
     */
    protected function getOperatorTransactionTypes()
    {
        return array(
            "COMMISION_FEE",
            "COMMISION_VAT",
            "REFUND_COMMISION_FEE",
            "REFUND_COMMISION_VAT",
            "SUBSRIRCTION_FEE",
            "SUBSRIRCTION_VAT"
        );
    }

    /**
     * Create the vendor operation
     *
     * @param int $amount
     * @param DateTime $cycleDate
     * @param bool|int $shopId false if it an operator operation
     *
     * @return OperationInterface
     */
    protected function createOperation(
        $amount,
        DateTime $cycleDate,
        $shopId = false
    )
    {
        $operation = $this->operationManager->create($shopId);
        $event = new CreateOperation($operation);
        $this->dispatcher->dispatch('after.operation.create', $event);
        $operation = $event->getOperation();

        $operation->setStatus(new Status(Status::CREATED));
        $operation->setAmount($amount);
        $operation->setCycleDate($cycleDate);

        return $operation;
    }

    /**
     * Check if technical account has sufficient funds
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