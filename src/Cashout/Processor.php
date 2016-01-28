<?php

namespace Hipay\MiraklConnector\Cashout;

use DateTime;
use Exception;
use Hipay\MiraklConnector\Api\Hipay\Model\Soap\Transfer;
use Hipay\MiraklConnector\Cashout\Model\Operation\OperationInterface;
use Hipay\MiraklConnector\Cashout\Model\Operation\Status;
use Hipay\MiraklConnector\Common\AbstractProcessor;
use Hipay\MiraklConnector\Api\Mirakl\ConfigurationInterface
    as MiraklConfiguration;
use Hipay\MiraklConnector\Api\Hipay;
use Hipay\MiraklConnector\Api\Hipay\ConfigurationInterface
    as HipayConfiguration;
use Hipay\MiraklConnector\Exception\DispatchableException;
use Hipay\MiraklConnector\Exception\Event\ThrowException;
use Hipay\MiraklConnector\Exception\WrongWalletBalance;
use Hipay\MiraklConnector\Exception\NoWalletFoundException;
use Hipay\MiraklConnector\Exception\UnconfirmedBankAccountException;
use Hipay\MiraklConnector\Exception\UnidentifiedWalletException;
use Hipay\MiraklConnector\Vendor\Model\VendorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Hipay\MiraklConnector\Cashout\Model\Operation\ManagerInterface
    as OperationManager;
use Hipay\MiraklConnector\Vendor\Model\ManagerInterface as VendorManager;
use Hipay\MiraklConnector\Api\Hipay\Model\Status\BankInfo as BankInfoStatus;

/**
 * Class Processor.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Processor extends AbstractProcessor
{
    /** @var  OperationManager */
    protected $operationManager;

    /** @var  VendorManager */
    protected $vendorManager;

    /** @var VendorInterface */
    protected $operator;
    /**
     * @var VendorInterface
     */
    protected $technical;

    /**
     * Processor constructor.
     *
     * @param MiraklConfiguration $miraklConfig
     * @param HipayConfiguration $hipayConfig
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param OperationManager $operationManager ,
     * @param VendorManager $vendorManager
     * @param VendorInterface $operator
     * @param VendorInterface $technical
     */
    public function __construct(
        MiraklConfiguration $miraklConfig,
        HipayConfiguration $hipayConfig,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        OperationManager $operationManager,
        VendorManager $vendorManager,
        VendorInterface $operator,
        VendorInterface $technical
    ) {
        parent::__construct($miraklConfig, $hipayConfig, $dispatcher, $logger);
        $this->operationManager = $operationManager;
        $this->vendorManager = $vendorManager;
        $this->operator = $operator;
        $this->technical = $technical;
    }

    /**
     * Main processing function.
     *
     * @throws WrongWalletBalance
     * @throws NoWalletFoundException
     * @throws UnconfirmedBankAccountException
     * @throws UnidentifiedWalletException
     */
    public function process()
    {
        $previousDay = new DateTime('-1 day');

        $this->logger->info("Cachout Processor");

        //Check identification status of the technical account
        if (!$this->hipay->isIdentified($this->technical)) {
            throw new UnidentifiedWalletException($this->technical);
        }

        //Transfer
        $this->transferOperations($previousDay);

        //Withdraw
        $this->withdrawOperations($previousDay);
    }

    /**
     * Execute the operation needing transfer.
     *
     * @param DateTime $previousDay
     */
    protected function transferOperations(DateTime $previousDay)
    {
        $this->logger->info("Transfer operations");
        //Transfer
        $toTransfer = $this->operationManager->findByStatus(
            new Status(Status::CREATED)
        );
        $toTransfer = array_merge(
            $toTransfer,
            $this->operationManager
                ->findByStatusAndAfterCycleDate(
                    new Status(Status::TRANSFER_FAILED),
                    $previousDay
                )
        );

        $this->logger->info("Operation transfered : " . count($toTransfer));

        $transferSuccess = new Status(Status::TRANSFER_SUCCESS);
        $transferFailed = new Status(Status::TRANSFER_FAILED);
        /** @var OperationInterface $operation */
        foreach ($toTransfer as $operation) {
            try {
                $transferId = $this->transferOperation($operation);
                $operation->setStatus($transferSuccess);
                $operation->setTransferId($transferId);
            } catch (DispatchableException $e) {
                $operation->setStatus($transferFailed);
                $this->logger->warning(
                    $e->getMessage()
                );
                $this->dispatcher->dispatch(
                    $e->getEventName(),
                    new ThrowException($e)
                );
            } catch (Exception $e) {
                $operation->setStatus($transferFailed);
                $this->logger->critical(
                    $e->getMessage()
                );
            }
            $this->operationManager->save($operation);
            $this->logger->info("[OK] Transfer operation executed");
        }
    }
    /**
     * Execute the operation needing withdrawal.
     *
     * @param DateTime $previousDay
     */
    protected function withdrawOperations(DateTime $previousDay)
    {
        $toWithdraw = $this->operationManager->findByStatus(
            new Status(Status::TRANSFER_SUCCESS)
        );
        $toWithdraw = array_merge(
            $toWithdraw,
            $this->operationManager
                ->findByStatusAndAfterCycleDate(
                    new Status(Status::WITHDRAW_FAILED),
                    $previousDay
                )
        );

        $withdrawRequested = new Status(Status::WITHDRAW_REQUESTED);
        $withdrawFailed = new Status(Status::WITHDRAW_FAILED);

        /** @var OperationInterface $operation */
        foreach ($toWithdraw as $operation) {
            try {
                $withdrawId = $this->withdrawOperation($operation);
                $operation->setWithdrawId($withdrawId);
                $operation->setStatus($withdrawRequested);
            } catch (DispatchableException $e) {
                $operation->setStatus($withdrawFailed);
                $this->logger->warning(
                    $e->getMessage()
                );
                $this->dispatcher->dispatch(
                    $e->getEventName(),
                    new ThrowException($e)
                );
            } catch (Exception $e) {
                $operation->setStatus($withdrawFailed);
                $this->logger->critical(
                    $e->getMessage()
                );
            }
            $this->operationManager->save($operation);
            $this->logger->info("[OK] Withdraw operation executed");
        }
    }

    /**
     * Transfer money between the technical
     * wallet and the operator|seller wallet.
     *
     * @param OperationInterface $operation
     *
     * @return int
     *
     * @throws NoWalletFoundException if the wallet is not found
     */
    public function transferOperation(OperationInterface $operation)
    {
        $vendor = $this->getVendor($operation);

        if (!$vendor || $this->hipay->isAvailable($vendor->getEmail())) {
            throw new NoWalletFoundException($vendor);
        }

        $operation->setHipayId($vendor->getHipayId());

        $transfer = new Transfer(
            round($operation->getAmount(), 2),
            $vendor,
            $this->operationManager->generatePublicLabel($operation),
            $this->operationManager->generatePrivateLabel($operation)
        );


        //Transfer
        return $this->hipay->transfer($transfer);
    }

    /**
     * Put the money into the real bank account of the operator|seller.
     *
     * @param OperationInterface $operation
     * @return int
     * @throws NoWalletFoundException
     * @throws UnconfirmedBankAccountException if the bank account
     *                                         information is not the the status validated at Hipay
     * @throws UnidentifiedWalletException if the account is not identified by Hipay
     * @throws WrongWalletBalance if the hipay wallet balance is
     *                                         lower than the transaction amount to be sent to the bank account
     */
    public function withdrawOperation(OperationInterface $operation)
    {
        $vendor = $this->getVendor($operation);

        if (!$vendor || $this->hipay->isAvailable($vendor->getEmail())) {
            throw new NoWalletFoundException($vendor);
        }

        if (!$this->hipay->isIdentified($vendor)) {
            throw new UnidentifiedWalletException($vendor);
        }

        $bankInfoStatus = $this->hipay->bankInfosStatus($vendor);

        if ($this->hipay->bankInfosStatus($vendor)
            != BankInfoStatus::VALIDATED
        ) {
            throw new UnconfirmedBankAccountException(
                $vendor,
                new BankInfoStatus($bankInfoStatus)
            );
        }

        //Check account balance
        $amount = round(($operation->getAmount()), 2);
        $balance = round($this->hipay->getBalance($vendor), 2);
        if ($balance < $amount) {
            //Operator operation
            if (!$operation->getMiraklId()) {
                $amount = $balance;
                //Vendor operation
            } else {
                throw new WrongWalletBalance(
                    $vendor,
                    $amount,
                    $balance
                );
            }
        }

        $operation->setHipayId($vendor->getHipayId());

        //Withdraw
        return $this->hipay->withdraw(
            $vendor,
            $amount,
            $this->operationManager->generateWithdrawLabel($operation)
        );
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
}
