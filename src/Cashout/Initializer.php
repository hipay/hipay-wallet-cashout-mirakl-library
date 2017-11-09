<?php

namespace HiPay\Wallet\Mirakl\Cashout;

use DateTime;
use Exception;
use HiPay\Wallet\Mirakl\Api\Factory;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\ManagerInterface as OperationManager;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\Status;
use HiPay\Wallet\Mirakl\Cashout\Model\Transaction\ValidatorInterface;
use HiPay\Wallet\Mirakl\Common\AbstractApiProcessor;
use HiPay\Wallet\Mirakl\Exception\AlreadyCreatedOperationException;
use HiPay\Wallet\Mirakl\Exception\InvalidOperationException;
use HiPay\Wallet\Mirakl\Exception\ValidationFailedException;
use HiPay\Wallet\Mirakl\Service\Validation\ModelValidator;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorManagerInterface as VendorManager;
use HiPay\Wallet\Mirakl\Notification\Model\LogOperationsManagerInterface as LogOperationsManager;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use HiPay\Wallet\Mirakl\Notification\FormatNotification;
use HiPay\Wallet\Mirakl\Api\Mirakl;

/**
 * Generate and save the operation to be executed by the processor.
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
class Initializer extends AbstractOperationProcessor
{

    /** @var VendorInterface */
    protected $technicalAccount;

    /** @var  ValidatorInterface */
    protected $transactionValidator;

    /**
     * @var FormatNotification class
     */
    protected $formatNotification;

    protected $operationsLogs;

    protected $adjustedOperations;

    /**
     * Initializer constructor.
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param Factory $factory
     * @param VendorInterface $operatorAccount
     * @param VendorInterface $technicalAccount
     * @param ValidatorInterface $transactionValidator
     * @param OperationManager $operationHandler
     * @param LogOperationsManager $logOperationsManager
     * @param VendorManager $vendorManager
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        Factory $factory,
        VendorInterface $operatorAccount,
        VendorInterface $technicalAccount,
        ValidatorInterface $transactionValidator,
        OperationManager $operationHandler,
        LogOperationsManager $logOperationsManager,
        VendorManager $vendorManager
    ) {
        parent::__construct(
            $dispatcher,
            $logger,
            $factory,
            $operationHandler,
            $vendorManager,
            $logOperationsManager,
            $operatorAccount
        );

        ModelValidator::validate($technicalAccount, 'Operator');
        $this->technicalAccount = $technicalAccount;

        $this->transactionValidator = $transactionValidator;

        $this->formatNotification = new FormatNotification();

        $this->operationsLogs = array();

        $this->adjustedOperations = array();
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
     *
     * @codeCoverageIgnore
     */
    public function process(DateTime $startDate, DateTime $endDate, DateTime $cycleDate)
    {
        $this->logger->info('Control Mirakl Settings', array('miraklId' => null, "action" => "Operations creation"));
        // control mirakl settings
        $boolControl = $this->getControlMiraklSettings($this->documentTypes);
        if ($boolControl === false) {
            // log critical
            $title = $this->criticalMessageMiraklSettings;
            $message = $this->formatNotification->formatMessage($title);
            $this->logger->critical($message, array('miraklId' => null, "action" => "Operations creation"));
        } else {
            $this->logger->info(
                'Control Mirakl Settings OK',
                array('miraklId' => null, "action" => "Operations creation")
            );
        }

        $this->logger->info('Cashout Initializer', array('miraklId' => null, "action" => "Operations creation"));

        //Fetch Invoices
        $this->logger->info(
            'Fetch invoices documents from Mirakl from ' .
            $startDate->format('Y-m-d H:i') .
            ' to ' .
            $endDate->format('Y-m-d H:i')
            ,
            array('miraklId' => null, "action" => "Operations creation")
        );

        // get invoices from Mirakl API
        $invoices = $this->getInvoices($startDate, $endDate);

        $this->logger->info(
            '[OK] Fetched ' . count($invoices) . ' invoices',
            array('miraklId' => null, "action" => "Operations creation")
        );

        $this->logger->info('Process invoices', array('miraklId' => null, "action" => "Operations creation"));

        $operations = $this->processInvoices($invoices, $cycleDate);

        $this->saveOperations($operations);

    }

    /**
     * Create the vendor operation
     * dispatch <b>after.operation.create</b>.
     *
     * @param $amount
     * @param $originAmount
     * @param DateTime $cycleDate
     * @param $paymentVoucher
     * @param $vendor
     * @param null $adjustedOperationsIds
     * @return OperationInterface
     */
    public function createOperation(
        $amount,
        $originAmount,
        DateTime $cycleDate,
        $paymentVoucher,
        $vendor,
        $adjustedOperationsIds = null
    ) {
        //Set hipay id
        $hipayId = $vendor->getHiPayId();

        //Call implementation function
        $operation = $this->operationManager->create($amount, $cycleDate, $paymentVoucher, $vendor->getMiraklId());

        $operation->setHiPayId($hipayId);

        //Sets mandatory values
        $operation->setMiraklId($vendor->getMiraklId());
        $operation->setStatus(new Status(Status::CREATED));
        $operation->setUpdatedAt(new DateTime());
        $operation->setAmount($amount);
        $operation->setOriginAmount($originAmount);
        $operation->setCycleDate($cycleDate);
        $operation->setPaymentVoucher((string)$paymentVoucher);

        // if adjustments were made, save adjusted operations
        if ($adjustedOperationsIds !== null) {
            $operation->setAdjustmentIds(json_encode($adjustedOperationsIds));
        }

        $this->isOperationValid($operation);

        $this->operationsLogs[] = $this->logOperationsManager->create(
            $vendor->getMiraklId(),
            $hipayId,
            (string)$paymentVoucher,
            $amount,
            $originAmount,
            $this->hipay->getBalance($vendor)
        );
        return $operation;
    }

    /**
     * Validate an operation
     *
     * @param OperationInterface $operation
     * @return bool
     * @throws AlreadyCreatedOperationException
     * @throws InvalidOperationException
     */
    public function isOperationValid(OperationInterface $operation)
    {
        if ($this->operationManager->findByMiraklIdAndPaymentVoucherNumber(
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

        return true;
    }

    /**
     * Save operations
     *
     * @param array $operations
     */
    public function saveOperations(array $operations)
    {

        $this->logger->info('Save operations', array('miraklId' => null, "action" => "Operations creation"));
        $this->operationManager->saveAll($operations);
        $this->logOperationsManager->saveAll($this->operationsLogs);
        $this->saveAdjustedOperations($this->adjustedOperations);
        $this->logger->info('[OK] Operations saved', array('miraklId' => null, "action" => "Operations creation"));
    }

    /**
     * Control if Mirakl Setting is ok with HiPay prerequisites
     *
     * @param $docTypes
     */
    public function getControlMiraklSettings($docTypes)
    {
        $this->mirakl->controlMiraklSettings($docTypes);
    }

    /**
     * Create operations from Mirakl invoices
     *
     * @param array $invoices
     * @param DateTime $cycleDate
     * @return type
     */
    private function processInvoices(array $invoices, DateTime $cycleDate)
    {

        $operations = array();

        foreach ($invoices as $invoice) {

            $this->logger->debug(
                "ShopId : " . $invoice['shop_id'],
                array('miraklId' => $invoice['shop_id'], "action" => "Operations creation")
            );

            $vendor = $this->vendorManager->findByMiraklId($invoice['shop_id']);

            if ($vendor === null) {
                $this->logger->info(
                    "Operation wasn't created because vendor doesn't exit in database (verify HiPay process value in Mirakl BO)",
                    array('miraklId' => $invoice['shop_id'], "action" => "Operations creation")
                );
            } else {
                $operationsFromInvoice = $this->createOperationsFromInvoice($invoice, $vendor, $cycleDate);
                if ($operationsFromInvoice) {
                    $operations = array_merge($operations, $operationsFromInvoice);
                } else {
                    //something went wrong
                    $title = "The operations for invoice n° " . $invoice['invoice_id'] . " are wrong";
                    $message = $this->formatNotification->formatMessage($title);
                    $this->logger->error($message, array('miraklId' => null, "action" => "Operations creation"));
                }
            }
        }

        return $operations;
    }

    /**
     * Create operations from Mirakl invoice
     *
     * @param array $invoice
     * @param VendorInterface $vendor
     * @param DateTime $cycleDate
     * @return boolean
     */
    private function createOperationsFromInvoice(array $invoice, VendorInterface $vendor, DateTime $cycleDate)
    {
        if ($invoice['summary']['amount_transferred'] > 0) {

            $operations = array();

            try {
                // Calculate adjusted amount (for past negative operations)
                $adjustedInfos = $this->getAdjustedAmount($invoice['summary']['amount_transferred'], $vendor);

                $this->logger->debug(
                    "Vendor origin amount " . $invoice['summary']['amount_transferred'],
                    array('miraklId' => $invoice['shop_id'], "action" => "Operations creation")
                );

                $this->logger->debug(
                    "Vendor adjusted amount " .
                    $adjustedInfos['adjustedAmount'] .
                    " (" .
                    count($adjustedInfos['adujstedOperationsIds']) .
                    " operations adjusted)",
                    array('miraklId' => $invoice['shop_id'], "action" => "Operations creation")
                );

                // Vendor operation
                $operations[] = $this->createOperation(
                    $adjustedInfos['adjustedAmount'],
                    $invoice['summary']['amount_transferred'],
                    $cycleDate,
                    $invoice['invoice_id'],
                    $vendor,
                    $adjustedInfos['adujstedOperationsIds']
                );

                // operator operation
                $operations[] = $this->createOperation(
                    $invoice['total_charged_amount'],
                    null,
                    $cycleDate,
                    $invoice['invoice_id'],
                    $this->operator
                );

                // save in memory for future saving in database
                $this->adjustedOperations = array_merge(
                    $this->adjustedOperations,
                    $adjustedInfos["adujstedOperations"]
                );

                return $operations;
            } catch (Exception $e) {
                $this->handleException($e);
                return false;
            }
        } else {
            $this->logger->warning(
                "Invoice n° " . $invoice['invoice_id'] . " has a negative amount, will not be treated",
                array('miraklId' => $invoice['shop_id'], "action" => "Operations creation")
            );
        }
    }

    /**
     * Set adjusted operations to the right status
     *
     * @param array $adjustedOperations
     */
    private function saveAdjustedOperations(array $adjustedOperations)
    {

        foreach ($adjustedOperations as $op) {
            $op->setStatus(new Status(Status::ADJUSTED_OPERATIONS));
            $this->operationManager->save($op);
            $this->logOperation(
                $op->getMiraklId(),
                $op->getPaymentVoucher(),
                Status::ADJUSTED_OPERATIONS,
                ""
            );
        }
    }

    /**
     * Calculate adjusted amount for this invoice
     *
     * @param type $originAmount
     * @param type $vendor
     * @return type
     */
    private function getAdjustedAmount($originAmount, $vendor)
    {

        $adjustedOperations = array();
        $adjustedOperationsIds = array();
        // retrieve not adjusted negative operations for vendor ID
        $negativeOperations = $this->operationManager->findNegativeOperations($vendor->getHipayId());

        foreach ($negativeOperations as $nop) {
            if (!in_array($nop, $this->adjustedOperations) && $originAmount + $nop->getAmount() > 0) {
                $originAmount += $nop->getAmount();
                $adjustedOperations[] = $nop;
                $adjustedOperationsIds[] = $nop->getId();
            }
        }

        return array(
            'adjustedAmount' => $originAmount,
            'adujstedOperations' => $adjustedOperations,
            'adujstedOperationsIds' => $adjustedOperationsIds
        );
    }

    /**
     * Get invoices from Mirakl API
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return type
     */
    private function getInvoices(DateTime $startDate, DateTime $endDate)
    {
        $offset = Mirakl::MIRAKL_API_MAX_PAGINATE;

        $invoicesData = $this->mirakl->getInvoices(
            $startDate,
            $endDate
        );

        $invoices = array_merge(array(), $invoicesData['invoices']);

        $total = $invoicesData['total_count'];

        while ( $total % $offset !== $total){

            $invoicesData = $this->mirakl->getInvoices(
                $startDate,
                $endDate,
                Mirakl::MIRAKL_API_MAX_PAGINATE,
                $offset
            );

            $invoices = array_merge($invoices, $invoicesData['invoices']);

            $offset += Mirakl::MIRAKL_API_MAX_PAGINATE;
        }

        return $invoices;
    }

}