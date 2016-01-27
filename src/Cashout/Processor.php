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
use Mustache_Engine;
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
    private $operator;

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
     */
    public function __construct(
        MiraklConfiguration $miraklConfig,
        HipayConfiguration $hipayConfig,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        OperationManager $operationManager,
        VendorManager $vendorManager,
        VendorInterface $operator
    ) {
        parent::__construct($miraklConfig, $hipayConfig, $dispatcher, $logger);
        $this->operationManager = $operationManager;
        $this->vendorManager = $vendorManager;
        $this->operator = $operator;
    }

    /**
     * Main processing function.
     *
     * @param $publicLabelTemplate
     * @param $privateLabelTemplate
     * @param $withdrawLabelTemplate
     *
     * @throws WrongWalletBalance
     * @throws NoWalletFoundException
     * @throws UnconfirmedBankAccountException
     * @throws UnidentifiedWalletException
     */
    public function process(
        $publicLabelTemplate,
        $privateLabelTemplate,
        $withdrawLabelTemplate
    ) {
        $previousDay = new DateTime('-1 day');

        //Transfer
        $this->transferOperations(
            $previousDay,
            $publicLabelTemplate,
            $privateLabelTemplate
        );

        //Withdraw
        $this->withdrawOperations($previousDay, $withdrawLabelTemplate);
    }

    /**
     * Execute the operation needing transfer.
     *
     * @param DateTime $previousDay
     * @param string   $publicLabelTemplate
     * @param string   $privateLabelTemplate
     */
    protected function transferOperations(
        DateTime $previousDay,
        $publicLabelTemplate,
        $privateLabelTemplate
    ) {
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

        $transferSuccess = new Status(Status::TRANSFER_SUCCESS);
        $transferFailed = new Status(Status::TRANSFER_FAILED);
        /** @var OperationInterface $operation */
        foreach ($toTransfer as $operation) {
            try {
                $this->operationManager->save($operation);
                $transferId = $this->transferOperation(
                    $operation,
                    $this->generateLabel($publicLabelTemplate, $operation),
                    $this->generateLabel($privateLabelTemplate, $operation)
                );
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
        }
    }
    /**
     * Execute the operation needing withdrawal.
     *
     * @param $previousDay
     * @param $withdrawLabelTemplate
     */
    protected function withdrawOperations($previousDay, $withdrawLabelTemplate)
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
                $withdrawId = $this->withdrawOperation(
                    $operation,
                    $this->generateLabel($withdrawLabelTemplate, $operation)
                );
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
        }
    }

    /**
     * Transfer money between the technical
     * wallet and the operator|seller wallet.
     *
     * @param OperationInterface $operation
     * @param string             $publicLabel
     * @param string             $privateLabel
     *
     * @return int
     *
     * @throws NoWalletFoundException if the wallet is not found
     */
    public function transferOperation(
        OperationInterface $operation,
        $publicLabel,
        $privateLabel
    ) {
        $vendor = $this->getVendor($operation);

        if (!$vendor || $this->hipay->isAvailable($vendor->getEmail())) {
            throw new NoWalletFoundException($vendor);
        }

        $transfer = new Transfer(
            round($operation->getAmount(), 2),
            $vendor,
            $publicLabel,
            $privateLabel
        );

        $operation->setHipayId($vendor->getHipayId());

        //Transfer
        return $this->hipay->transfer($transfer);
    }

    /**
     * Put the money into the real bank account of the operator|seller.
     *
     * @param OperationInterface $operation
     * @param $label
     * @return int
     * @throws NoWalletFoundException
     * @throws UnconfirmedBankAccountException if the bank account
     *                                         information is not the the status validated at Hipay
     * @throws UnidentifiedWalletException if the account is not identified by Hipay
     * @throws WrongWalletBalance if the hipay wallet balance is
     *                                         lower than the transaction amount to be sent to the bank account
     */
    public function withdrawOperation(OperationInterface $operation, $label)
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
        return $this->hipay->withdraw($vendor, $amount, $label);
    }

    /**
     * Generate the label from a template.
     *
     * @param $labelTemplate
     * @param $operation
     *
     * @return string
     */
    public function generateLabel($labelTemplate, OperationInterface $operation)
    {
        $m = new Mustache_Engine();

        return $m->render($labelTemplate, array(
            'miraklId' => $operation->getMiraklId(),
            'amount' => round($operation->getAmount(), 2),
            'hipayId' => $operation->getHipayId(),
            'cycleDate' => $operation->getCycleDate()->format('Y-m-d'),
            'cycleDateTime' => $operation->getCycleDate()->format(
                'Y-m-d H:i:s'
            ),
            'cycleTime' => $operation->getCycleDate()->format('H:i:s'),
            'date' => date('Y-m-d'),
            'datetime' => date('Y-m-d H:i:s'),
            'time' => date('H:i:s'),
        ));
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
