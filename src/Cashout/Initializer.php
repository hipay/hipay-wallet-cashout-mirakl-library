<?php
namespace Hipay\MiraklConnector\Cashout;

use DateTime;
use Hipay\MiraklConnector\Cashout\Event\CreateOperation;
use Hipay\MiraklConnector\Cashout\Event\ValidateTransactions;
use Hipay\MiraklConnector\Cashout\Model\Operation\OperationInterface;
use Hipay\MiraklConnector\Cashout\Model\Operation\Status;
use Hipay\MiraklConnector\Common\AbstractProcessor;
use Hipay\MiraklConnector\Api\Mirakl\ConfigurationInterface
    as MiraklConfiguration;
use Hipay\MiraklConnector\Api\Hipay\ConfigurationInterface
    as HipayConfiguration;
use Hipay\MiraklConnector\Service\ModelValidator;
use Hipay\MiraklConnector\Vendor\VendorInterface;
use Hipay\MiraklConnector\Cashout\Model\Transaction\ValidatorInterface;
use Hipay\MiraklConnector\Cashout\Model\Operation\HandlerInterface
    as OperationHandlerInterface;
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

    /** @var OperationHandlerInterface */
    protected $operationHandler;

    /**
     * Initializer constructor.
     * @param MiraklConfiguration $miraklConfig
     * @param HipayConfiguration $hipayConfig
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param VendorInterface $operatorAccount
     * @param VendorInterface $technicalAccount
     * @param ValidatorInterface $transactionValidator
     * @param OperationHandlerInterface $operationHandler
     */
    public function __construct(
        MiraklConfiguration $miraklConfig,
        HipayConfiguration $hipayConfig,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        VendorInterface $operatorAccount,
        VendorInterface $technicalAccount,
        ValidatorInterface $transactionValidator,
        OperationHandlerInterface $operationHandler
    )
    {
        parent::__construct($miraklConfig, $hipayConfig, $dispatcher, $logger);
        $this->operator = $operatorAccount;
        $this->technicalAccount = $technicalAccount;
        $this->operationHandler = $operationHandler;
    }

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @throws \Exception
     */
    public function process(DateTime $startDate, DateTime $endDate)
    {
        $paymentTransactions = $this->getPayementTransactions(
            $startDate,
            $endDate
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
        $transactionErrors = array();

        foreach ($paymentVouchersByShopId as $shopId => $paymentVouchers) {
            $vendorAmount = 0;
            foreach ($paymentVouchers as $paymentVoucher) {

                $orderTransactions = $this->getOrderTransactions(
                    $paymentVoucher
                );

                $transactionErrors = array_merge_recursive(
                    $transactionErrors, $this->validateTransactions(
                        $orderTransactions
                    )
                );

                $vendorAmount += $this->computeVendorAmount(
                    $orderTransactions,
                    $balancesByPaymentVoucher[$paymentVoucher]
                );
                $operatorAmount += $this->computeOperatorAmountByVendor(
                    $orderTransactions
                );
            };
            $totalAmount += $vendorAmount;

            //Create the vendor operation
            $operations[] = $this->createOperation(
                $vendorAmount, $startDate, $endDate, $shopId
            );
        }
        $totalAmount += $operatorAmount;
        // Create operator operation
        $operations[] = $this->createOperation(
            $operatorAmount, $startDate, $endDate, false
        );

        if (!empty($transactionErrors)) {
            $this->transactionValidator->handleErrors($transactionErrors);
        }

        if (!$this->hasSufficientFunds($totalAmount)) {
            throw new \Exception("No enough funds in the tech account");
        }

        foreach ($operations as $operation) {
            ModelValidator::validate($operation);
            if ($this->operationHandler->isValid($operation)) {
                $this->operationHandler->save($operation);
            };
        }
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
     * @throws \Exception
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
     * Validate transaction and remove those who where bad
     * Dispatch the <b>'before.transactions.validate'</b> Event
     *
     * @param $transactions
     *
     * @return array
     */
    protected function validateTransactions(&$transactions)
    {
        $event = new ValidateTransactions($transactions);
        $this->dispatcher->dispatch(
            'before.transactions.validate',
            $event
        );

        $errors = array();

        foreach ($transactions as $index => $transaction) {
            $transactionErrors = $this->transactionValidator->validate(
                $transaction
            );
            if (!empty($transactionErrors)) {
                $errors += $transactionErrors;
                unset($transactions[$index]);
            }
        }

        return $errors;
    }

    /**
     * Compute the vendor amount to withdrawed from the technical account
     *
     * @param $transactions
     * @param $paymentTransaction
     *
     * @return int
     *
     * @throws \Exception
     */
    protected function computeVendorAmount(
        $transactions,
        $paymentTransaction
    )
    {
        $amount = 0;
        foreach ($transactions as $transaction) {
            $amount += $transaction['balance'];
        }
        if ($amount != $paymentTransaction['balance']) {
            throw new \Exception(
                'There is a difference between the transaction'
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
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param bool|int $shopId false if it an operator operation
     *
     * @return OperationInterface
     */
    protected function createOperation(
        $amount,
        DateTime $startDate,
        DateTime $endDate,
        $shopId = false
    )
    {
        $operation = $this->operationHandler->create(
            $amount,
            $shopId,
            $startDate,
            $endDate
        );
        $event = new CreateOperation($operation);
        $this->dispatcher->dispatch('after.operation.create', $event);
        $operation = $event->getOperation();
        $operation->setStatus(new Status(Status::CREATED));

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