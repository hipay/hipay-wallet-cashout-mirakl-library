<?php

namespace HiPay\Wallet\Mirakl\Cashout;

/**
 * Generate and save the operation to be executed by the processor.
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
class Initializer extends AbstractApiProcessor
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

    /** @var  LogOperationsManager */
    protected $logOperationsManager;

    /**
     * @var FormatNotification class
     */
    protected $formatNotification;
    protected $operationsLogs;

    /**
     * Initializer constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param Factory $factory
     * @param VendorInterface $operatorAccount
     * @param VendorInterface $technicalAccount
     * @param ValidatorInterface $transactionValidator
     * @param OperationManager $operationHandler
     * @param VendorManager $vendorManager
     * @throws ValidationFailedException
     */
    public function __construct(
    EventDispatcherInterface $dispatcher, LoggerInterface $logger, Factory $factory, VendorInterface $operatorAccount,
    VendorInterface $technicalAccount, ValidatorInterface $transactionValidator, OperationManager $operationHandler,
    LogOperationsManager $logOperationsManager, VendorManager $vendorManager
    )
    {
        parent::__construct($dispatcher, $logger, $factory);

        ModelValidator::validate($operatorAccount, 'Operator');
        $this->operator = $operatorAccount;

        ModelValidator::validate($technicalAccount, 'Operator');
        $this->technicalAccount = $technicalAccount;

        $this->operationManager = $operationHandler;

        $this->transactionValidator = $transactionValidator;

        $this->vendorManager = $vendorManager;

        $this->formatNotification = new FormatNotification();

        $this->logOperationsManager = $logOperationsManager;

        $this->operationsLogs = array();
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
            $title   = $this->criticalMessageMiraklSettings;
            $message = $this->formatNotification->formatMessage($title);
            $this->logger->critical($message, array('miraklId' => null, "action" => "Operations creation"));
        } else {
            $this->logger->info('Control Mirakl Settings OK',
                                array('miraklId' => null, "action" => "Operations creation"));
        }

        $this->logger->info('Cashout Initializer', array('miraklId' => null, "action" => "Operations creation"));

        //Fetch Invoices
        $this->logger->info(
            'Fetch invoices documents from Mirakl from '.
            $startDate->format('Y-m-d H:i').
            ' to '.
            $endDate->format('Y-m-d H:i')
            , array('miraklId' => null, "action" => "Operations creation")
        );

        $paymentTransactions = $this->getInvoices(
            $startDate, $endDate
        );
    }
    
    private function getInvoices(DateTime $startDate, DateTime $endDate){
        $invoices = $this->mirakl->getInvoices(
            $startDate, $endDate, null, null, null
        );

        return $invoices;
    }
}