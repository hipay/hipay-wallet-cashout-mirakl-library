<?php

namespace HiPay\Wallet\Mirakl\Cashout;

use DateTime;
use Exception;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\Status;
use HiPay\Wallet\Mirakl\Api\Factory;
use HiPay\Wallet\Mirakl\Cashout\AbstractOperationProcessor;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\ManagerInterface as OperationManager;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Service\Validation\ModelValidator;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorManagerInterface as VendorManager;
use HiPay\Wallet\Mirakl\Notification\Model\LogOperationsManagerInterface as LogOperationsManager;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\Transfer as TransferModel;
use HiPay\Wallet\Mirakl\Exception\WalletNotFoundException;
use Psr\Log\LoggerInterface;
use HiPay\Wallet\Mirakl\Exception\WrongWalletBalance;

/**
 * Generate and save the operation to be executed by the processor.
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
class Transfer extends AbstractOperationProcessor
{

    protected $technicalAccount;

    /**
     *
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param Factory $factory
     * @param VendorInterface $operatorAccount
     * @param VendorInterface $technicalAccount
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
        OperationManager $operationHandler,
        LogOperationsManager $logOperationsManager,
        VendorManager $vendorManager)
    {

        parent::__construct($dispatcher, $logger, $factory, $operationHandler, $vendorManager, $logOperationsManager, $operatorAccount);

        ModelValidator::validate($technicalAccount, 'Operator');

        $this->technicalAccount = $technicalAccount;

    }

    /**
     *
     */
    public function process()
    {

        $this->logger->info("Transfer operations", array('miraklId' => null, "action" => "Transfer"));

        $toTransfer = $this->getTransferableOperations();

        $this->transferOperations($toTransfer);

        $this->logger->info("Operation to transfer : ".count($toTransfer),
                                                             array('miraklId' => null, "action" => "Transfer"));
    }

    /**
     * Execute the operation needing transfer.
     * @param array $operations
     */
    public function transferOperations(array $operations)
    {
        foreach ($operations as $operation) {
            try {

                $this->transfer($operation);
                $this->logger->info(
                    "[OK] Transfer operation ".$operation->getTransferId()." executed",
                    array('miraklId' => $operation->getMiraklId(), "action" => "Transfer")
                    );

            } catch (Exception $e) {
                $this->logger->warning(
                    "[KO] Transfer operation failed",
                    array('miraklId' => $operation->getMiraklId(), "action" => "Transfer")
                    );
                $this->handleException($e, 'critical');
            }
        }
    }

    /**
     * Transfer money between the technical
     * wallet and the operator|seller wallet.
     *
     * @param OperationInterface $operation
     * @return type
     * @throws Exception
     * @throws WalletNotFoundException
     */
    public function transfer(OperationInterface $operation){
        try {
            $vendor = $this->getVendor($operation);

            if (!$vendor || $this->hipay->isAvailable($vendor->getEmail())) {
                throw new WalletNotFoundException($vendor);
            }

            $this->hasSufficientFunds($operation->getAmount(), $this->technicalAccount);

            $operation->setHiPayId($vendor->getHiPayId());

            $transfer = new TransferModel(
                round($operation->getAmount(), self::SCALE),
                $vendor,
                $this->operationManager->generatePrivateLabel($operation),
                $this->operationManager->generatePublicLabel($operation)
            );

            //Transfer
            $transferId = $this->hipay->transfer($transfer, $vendor);

            $operation->setStatus(new Status(Status::TRANSFER_SUCCESS));
            $operation->setTransferId($transferId);
            $operation->setUpdatedAt(new DateTime());
            $this->operationManager->save($operation);

            $this->logOperation(
                $operation->getMiraklId(),
                $operation->getPaymentVoucher(),
                Status::TRANSFER_SUCCESS,
                ""
            );

            return $transferId;
        } catch (WrongWalletBalance $e) {
            $operation->setStatus(new Status(Status::TRANSFER_NEGATIVE));
            $operation->setUpdatedAt(new DateTime());
            $this->operationManager->save($operation);

            $this->logOperation(
                $operation->getMiraklId(), $operation->getPaymentVoucher(), Status::TRANSFER_NEGATIVE, $e->getMessage()
            );

            throw $e;
        } catch (Exception $e) {
            $operation->setStatus(new Status(Status::TRANSFER_FAILED));
            $operation->setUpdatedAt(new DateTime());
            $this->operationManager->save($operation);

            $this->logOperation(
                $operation->getMiraklId(),
                $operation->getPaymentVoucher(),
                Status::TRANSFER_FAILED,
                $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * Fetch the operation to transfer from the storage
     * @return OperationInterface[]
     */
    protected function getTransferableOperations()
    {
        //Transfer
        $toTransferCreated = $this->operationManager->findByStatus(
            new Status(Status::CREATED)
        );

        $toTransferFailed = $this->operationManager->findByStatus(
            new Status(Status::TRANSFER_FAILED)
        );

        $toTransferNegative = $this->operationManager->findByStatus(
            new Status(Status::TRANSFER_NEGATIVE)
        );

        $toTransfer = array_merge($toTransferNegative, $toTransferFailed, $toTransferCreated);

        return $toTransfer;
    }

}