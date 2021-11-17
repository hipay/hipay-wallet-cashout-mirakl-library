<?php

namespace HiPay\Wallet\Mirakl\Test\Cashout;

use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\WithdrawStatus;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\Status;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\TransferStatus;
use HiPay\Wallet\Mirakl\Cashout\TransactionStatus;
use HiPay\Wallet\Mirakl\Test\Common\AbstractProcessorTest;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\Operation;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\LogOperations;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\Vendor;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Prophecy\Argument;
use Symfony\Component\Validator\Constraints\DateTime;

class TransactionStatusTest extends AbstractProcessorTest
{
    /** @var  Processor */
    protected $transactionProcessor;

    /** @var  VendorInterface */
    protected $vendorArgument;


    public function setUp()
    {
        parent::setUp();

        /** @var VendorInterface vendorArgument */
        $this->vendorArgument = Argument::type("\\HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorInterface");

        $this->transactionProcessor = new TransactionStatus(
            $this->eventDispatcher->reveal(),
            $this->logger->reveal(),
            $this->apiFactory->reveal(),
            $this->operationManager->reveal(),
            $this->vendorManager->reveal(),
            $this->operator,
            $this->logOperationsManager->reveal()
        );
    }

    /**
     * @cover ::withdraw
     * @group withdraw
     */
    public function testTransferSyncSuccessful()
    {

        $amount = floatval(rand());
        $operation = new Operation($amount, new DateTime(), "000001", null);
        $operation->setStatus(new Status(Status::TRANSFER_REQUESTED));
        $operation->setTransferId('transferID');

        $this->operationManager->findByStatus(new Status(Status::WITHDRAW_REQUESTED))
            ->willReturn(array());


        $this->operationManager->findByStatus(new Status(Status::TRANSFER_REQUESTED))
            ->willReturn(array($operation));


        $this->hipay->getTransaction(Argument::is('transferID'), Argument::is(null))
            ->willReturn(array('transaction_status' => TransferStatus::CAPTURED))
            ->shouldBeCalled();

        $this->logOperationsManager
            ->findByMiraklIdAndPaymentVoucherNumber(null, "000001")
            ->willReturn(new LogOperations(200, 2001));

        $this->transactionProcessor->process();

        $this->logger->info(
            "Operation to sync : 1",
            array('miraklId' => null, "action" => "transactionSync")
        )->shouldBeCalled();

        $this->logger->info(
            "Sync Transaction transferID",
            array('miraklId' => null, "action" => "transactionSync")
        )->shouldBeCalled();

        $this->logger->info(
            "New status : 3",
            array('miraklId' => null, "action" => "transactionSync")
        )->shouldBeCalled();

        $this->operationManager->save(Argument::any())->shouldBeCalled();

        $this->operationManager->findByStatus(new Status(Status::WITHDRAW_REQUESTED))
            ->shouldBeCalled();

        $this->operationManager->findByStatus(new Status(Status::TRANSFER_REQUESTED))
            ->shouldBeCalled();

        $logOperation = new LogOperations(200, 2001);
        $logOperation->setStatusTransferts(Status::TRANSFER_SUCCESS);
        $logOperation->setDateCreated(Argument::type('DateTime'));
        $this->logOperationsManager->save(Argument::any())->shouldBeCalled();
    }

    /**
     * @cover ::withdraw
     * @group withdraw
     */
    public function testTransferSyncFailed()
    {

        $amount = floatval(rand());
        $operation = new Operation($amount, new DateTime(), "000001", null);
        $operation->setStatus(new Status(Status::TRANSFER_REQUESTED));
        $operation->setTransferId('transferID');

        $this->operationManager->findByStatus(new Status(Status::WITHDRAW_REQUESTED))
            ->willReturn(array());


        $this->operationManager->findByStatus(new Status(Status::TRANSFER_REQUESTED))
            ->willReturn(array($operation));


        $this->hipay->getTransaction(Argument::is('transferID'), Argument::is(null))
            ->willReturn(array('transaction_status' => 'FAILED_STATUS'))
            ->shouldBeCalled();

        $this->logOperationsManager
            ->findByMiraklIdAndPaymentVoucherNumber(null, "000001")
            ->willReturn(new LogOperations(200, 2001));

        $this->transactionProcessor->process();

        $this->logger->info(
            "Operation to sync : 1",
            array('miraklId' => null, "action" => "transactionSync")
        )->shouldBeCalled();

        $this->logger->info(
            "Sync Transaction transferID",
            array('miraklId' => null, "action" => "transactionSync")
        )->shouldBeCalled();

        $this->logger->info(
            "New status : -9",
            array('miraklId' => null, "action" => "transactionSync")
        )->shouldBeCalled();

        $this->operationManager->save(Argument::any())->shouldBeCalled();

        $this->operationManager->findByStatus(new Status(Status::WITHDRAW_REQUESTED))
            ->shouldBeCalled();

        $this->operationManager->findByStatus(new Status(Status::TRANSFER_REQUESTED))
            ->shouldBeCalled();

        $this->logOperationsManager->save(Argument::any())->shouldBeCalled();
    }

    /**
     * @cover ::withdraw
     * @group withdraw
     */
    public function testWithdrawSyncSuccessful()
    {

        $amount = floatval(rand());
        $operation = new Operation($amount, new DateTime(), "000001", 'miraklID');
        $operation->setStatus(new Status(Status::WITHDRAW_REQUESTED));
        $operation->setTransferId('transferID');
        $operation->setWithdrawId('withdrawID');
        $operation->setHipayId('hipayID');

        $this->operationManager->findByStatus(new Status(Status::WITHDRAW_REQUESTED))
            ->willReturn(array($operation));


        $this->operationManager->findByStatus(new Status(Status::TRANSFER_REQUESTED))
            ->willReturn(array());


        $this->hipay->getTransaction('withdrawID', 'hipayID')
            ->willReturn(array('transaction_status' => WithdrawStatus::AUTHED));

        $this->hipay->getTransaction('withdrawID', 'hipayID')
            ->shouldBeCalled();

        $this->logOperationsManager
            ->findByMiraklIdAndPaymentVoucherNumber('miraklID', "000001")
            ->willReturn(new LogOperations(200, 2001));

        $this->transactionProcessor->process();

        $this->logger->info(
            "Operation to sync : 1",
            array('miraklId' => null, "action" => "transactionSync")
        )->shouldBeCalled();

        $this->logger->info(
            "Sync Transaction withdrawID",
            array('miraklId' => null, "action" => "transactionSync")
        )->shouldBeCalled();

        $this->logger->info(
            "New status : 6",
            array('miraklId' => null, "action" => "transactionSync")
        )->shouldBeCalled();


        $this->operationManager->save(Argument::any())->shouldBeCalled();

        $this->operationManager->findByStatus(new Status(Status::WITHDRAW_REQUESTED))
            ->shouldBeCalled();

        $this->operationManager->findByStatus(new Status(Status::TRANSFER_REQUESTED))
            ->shouldBeCalled();

        $logOperation = new LogOperations(200, 2001);
        $logOperation->setStatusTransferts(Status::TRANSFER_SUCCESS);

        $this->logOperationsManager->save(Argument::any())->shouldBeCalled();
    }

    /**
     * @cover ::withdraw
     * @group withdraw
     */
    public function testWithdrawSyncFail()
    {

        $amount = floatval(rand());
        $operation = new Operation($amount, new DateTime(), "000001", 'miraklID');
        $operation->setStatus(new Status(Status::WITHDRAW_REQUESTED));
        $operation->setTransferId('transferID');
        $operation->setWithdrawId('withdrawID');
        $operation->setHipayId('hipayID');

        $this->operationManager->findByStatus(new Status(Status::WITHDRAW_REQUESTED))
            ->willReturn(array($operation));


        $this->operationManager->findByStatus(new Status(Status::TRANSFER_REQUESTED))
            ->willReturn(array());


        $this->hipay->getTransaction('withdrawID', 'hipayID')
            ->willReturn(array('transaction_status' => 'FAILED_STATUS'));

        $this->hipay->getTransaction('withdrawID', 'hipayID')
            ->shouldBeCalled();

        $this->logOperationsManager
            ->findByMiraklIdAndPaymentVoucherNumber('miraklID', "000001")
            ->willReturn(new LogOperations(200, 2001));

        $this->transactionProcessor->process();

        $this->logger->info(
            "Operation to sync : 1",
            array('miraklId' => null, "action" => "transactionSync")
        )->shouldBeCalled();

        $this->logger->info(
            "Sync Transaction withdrawID",
            array('miraklId' => null, "action" => "transactionSync")
        )->shouldBeCalled();

        $this->logger->info(
            "New status : -7",
            array('miraklId' => null, "action" => "transactionSync")
        )->shouldBeCalled();


        $this->operationManager->save(Argument::any())->shouldBeCalled();

        $this->operationManager->findByStatus(new Status(Status::WITHDRAW_REQUESTED))
            ->shouldBeCalled();

        $this->operationManager->findByStatus(new Status(Status::TRANSFER_REQUESTED))
            ->shouldBeCalled();

        $logOperation = new LogOperations(200, 2001);
        $logOperation->setStatusTransferts(Status::TRANSFER_SUCCESS);

        $this->logOperationsManager->save(Argument::any())->shouldBeCalled();
    }
}
