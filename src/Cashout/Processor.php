<?php
namespace Hipay\MiraklConnector\Cashout;

use Hipay\MiraklConnector\Api\Hipay\Model\Soap\Transfer;
use Hipay\MiraklConnector\Cashout\Model\Operation\OperationInterface;
use Hipay\MiraklConnector\Cashout\Model\Operation\Status;
use Hipay\MiraklConnector\Common\AbstractProcessor;
use Hipay\MiraklConnector\Api\Mirakl;
use Hipay\MiraklConnector\Api\Mirakl\ConfigurationInterface
    as MiraklConfiguration;
use Hipay\MiraklConnector\Api\Hipay;
use Hipay\MiraklConnector\Api\Hipay\ConfigurationInterface
    as HipayConfiguration;
use Hipay\MiraklConnector\Vendor\VendorManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Hipay\MiraklConnector\Cashout\Model\Operation\HandlerInterface as OperationHandler;
/**
 * File Processor.php
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Processor extends AbstractProcessor
{
    /** @var  OperationHandler */
    protected $operationHandler;

    /** @var  VendorManager */
    protected $vendorManager;

    /**
     * Processor constructor.
     * @param MiraklConfiguration $miraklConfig
     * @param HipayConfiguration $hipayConfig
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param OperationHandler $handler
     */
    public function __construct(
        MiraklConfiguration $miraklConfig,
        HipayConfiguration $hipayConfig,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        OperationHandler $handler
    )
    {
        parent::__construct($miraklConfig, $hipayConfig, $dispatcher, $logger);
        $this->operationHandler = $handler;
    }

    /**
     * Main processing function
     *
     * @param $publicLabel
     * @param $privateLabel
     */
    public function process($publicLabel, $privateLabel)
    {
        $nextDay = new \DateTime("+1 day");

        //Transfer
        $toTransfer = $this->operationHandler->find(
            new Status(Status::CREATED)
        );
        $toTransfer = $toTransfer + $this->operationHandler->find(
            new Status(Status::TRANSFER_FAILED), $nextDay
        );

        foreach ($toTransfer as $operation) {
            $this->transfer($operation, $publicLabel, $privateLabel);
        }

        $this->operationHandler->saveAll($toTransfer);

        //Withdraw
        $toWithdraw = $this->operationHandler->find(
            new Status(Status::TRANSFER_SUCCESS)
        );
        $toWithdraw = $toWithdraw + $this->operationHandler->find(
            new Status(Status::WITHDRAW_FAILED), $nextDay
        );
        foreach ($toWithdraw as $operation) {
            $this->withdraw($operation);
        }

        $this->operationHandler->saveAll($toWithdraw);

    }

    /**
     * @param OperationInterface $operation
     * @param string $publicLabel
     * @param string $privateLabel
     */
    public function transfer(
        OperationInterface $operation,
        $publicLabel,
        $privateLabel
    )
    {
        $vendor = $this->vendorManager->findByHipayId($operation->getHipayId());
        if (!$vendor) {
            //TODO Throw exception
            $operation->setStatus(new Status(Status::TRANSFER_FAILED));
        }

        if ($this->hipay->isAvailable($vendor->getEmail())) {
            //TODo Throw exceptiion
            $operation->setStatus(new Status(Status::TRANSFER_FAILED));
        }

        $transfer = new Transfer(
            $operation->getAmount(),
            $vendor,
            $publicLabel,
            $privateLabel
        );
        $this->hipay->direct($transfer);
        $operation->setStatus(new Status(Status::TRANSFER_SUCCESS));
    }

    public function withdraw(OperationInterface $operation)
    {
        if (!$this->hipay->isIdentified($operation->getHipayId())) {
            //Throw exception
            $operation->setStatus(new Status(Status::WITHDRAW_FAILED));
        }

        $vendor = $this->vendorManager->findByHipayId($operation->getHipayId());

        if ($this->hipay->bankInfosStatus() != BankInfoStatus::VALIDATED) {
            //Throw exception
            $operation->setStatus(new Status(Status::WITHDRAW_FAILED));
        }

        $operation->setStatus(new Status(Status::WITHDRAW_REQUESTED));
    }
}