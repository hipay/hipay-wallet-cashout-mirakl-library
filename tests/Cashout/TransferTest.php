<?php

namespace HiPay\Wallet\Mirakl\Test\Cashout;

use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\BankInfo;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\Status;
use HiPay\Wallet\Mirakl\Cashout\Transfer;
use HiPay\Wallet\Mirakl\Test\Common\AbstractProcessorTest;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\Operation;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\LogOperations;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\Vendor;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Prophecy\Argument;
use Symfony\Component\Validator\Constraints\DateTime;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\Transfer as TransferModel;

/**
 *
 * @coversDefaultClass \HiPay\Wallet\Mirakl\Cashout\Transfer
 */
class TransferTest extends AbstractProcessorTest
{
    /** @var  Processor */
    protected $cashoutProcessor;

    /** @var  VendorInterface */
    protected $vendorArgument;


    public function setUp()
    {
        parent::setUp();

        /** @var VendorInterface vendorArgument */
        $this->vendorArgument = Argument::type("\\HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorInterface");

        $this->transferProcessor = new Transfer(
            $this->eventDispatcher->reveal(),
            $this->logger->reveal(),
            $this->apiFactory->reveal(),
            $this->operator,
            $this->technical,
            $this->operationManager->reveal(),
            $this->logOperationsManager->reveal(),
            $this->vendorManager->reveal()
        );

        $this->technicalAccountArgument = Argument::is($this->technical);

        /** @var OperationInterface $operationArgument */
        $this->operationArgument = Argument::type("\\HiPay\\Wallet\\Mirakl\\Cashout\\Model\\Operation\\OperationInterface");

        $this->operationManager->generatePrivateLabel($this->operationArgument)->willReturn($this->getRandomString());

        $this->operationManager->generatePublicLabel($this->operationArgument)->willReturn($this->getRandomString());

        $this->operationManager->generateWithdrawLabel($this->operationArgument)->willReturn($this->getRandomString());

        $this->transferArgument = Argument::type("\\HiPay\\Wallet\\Mirakl\\Api\\HiPay\\Model\\Soap\\Transfer");

    }

    /**
     * @cover ::hasSufficientFunds
     */
    public function testHasSufficientFunds()
    {
        $this->hipay->getBalance($this->technicalAccountArgument)->willReturn(2001)->shouldBeCalled();
        $this->assertNull($this->transferProcessor->hasSufficientFunds(2000, $this->technical));
    }

    /**
     * @cover ::hasSufficientFunds
     * @expectedException \HiPay\Wallet\Mirakl\Exception\WrongWalletBalance
     */
    public function testHasNotEnoughFunds()
    {
        $this->hipay->getBalance($this->technicalAccountArgument)->willReturn(2001)->shouldBeCalled();

        $this->transferProcessor->hasSufficientFunds(3000, $this->technical);
    }

    /**
     * @cover ::transfer
     * @group transfer
     */
    public function testVendorTransferSuccessful()
    {
        $transferId = rand();

        $this->hipay->isWalletExist(Argument::cetera())
            ->willReturn(true)
            ->shouldBeCalled();

        $this->vendorManager->findByMiraklId(Argument::type("integer"))
            ->willReturn(new Vendor("test@test.com", rand(), rand()))
            ->shouldBeCalled();

        $this->hipay->transfer($this->transferArgument, Argument::cetera())
            ->willReturn($transferId)
            ->shouldBeCalled();


        $operation = new Operation(2000, new DateTime(), "000001", rand());

        $this->hipay->getBalance($this->technicalAccountArgument)->willReturn(2001)->shouldBeCalled();

        $this->operationManager->save($this->operationArgument)->willReturn()->shouldBeCalled();

        $this->logOperationsManager->save(Argument::any())->willReturn()->shouldBeCalled();

        $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(Argument::any(),
            Argument::any())->willReturn(new LogOperations(200, 2001))->shouldBeCalled();

        $result = $this->transferProcessor->transfer($operation);

        $this->assertInternalType("integer", $result);
        $this->assertEquals($transferId, $result);
        $this->assertEquals(Status::TRANSFER_REQUESTED, $operation->getStatus());
    }


    /**
     * @cover ::transfer
     * @group transfer
     */
    public function testOperatorTransferSuccessful()
    {
        $transferId = rand();

        $operation = new Operation(2000, new DateTime(), "000001", false);

        $this->hipay->isWalletExist(Argument::cetera())
            ->willReturn(true)
            ->shouldBeCalled();

        $this->hipay->getBalance($this->technicalAccountArgument)->willReturn(2001)->shouldBeCalled();

        $this->operationManager->save($this->operationArgument)->willReturn()->shouldBeCalled();

        $this->hipay->transfer($this->transferArgument, Argument::cetera())->willReturn($transferId)->shouldBeCalled();

        $this->logOperationsManager->save(Argument::any())->willReturn()->shouldBeCalled();

        $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(Argument::any(),
            Argument::any())->willReturn(new LogOperations(200, 2001))->shouldBeCalled();

        $this->operationManager->findVendorOperationsByPaymentVoucherId(Argument::any())->willReturn(new Operation(2000,
            new DateTime(), "000001", false));

        $this->vendorManager->findByMiraklId(Argument::any())->willReturn(new Vendor("test@test.com", rand(), rand()));

        $result = $this->transferProcessor->transfer($operation);

        $this->assertInternalType("integer", $result);
        $this->assertEquals($transferId, $result);
        $this->assertEquals(Status::TRANSFER_REQUESTED, $operation->getStatus());
    }


    /**
     * @cover ::transfer
     * @group transfer
     * @expectedException \HiPay\Wallet\Mirakl\Exception\WrongWalletBalance
     */
    public function testVendorTransferWrongWalletBalance()
    {
        $transferId = rand();

        $this->hipay->isWalletExist(Argument::cetera())
            ->willReturn(true)
            ->shouldBeCalled();

        $this->vendorManager->findByMiraklId(Argument::type("integer"))
            ->willReturn(new Vendor("test@test.com", rand(), rand()))
            ->shouldBeCalled();

        $this->hipay->transfer($this->transferArgument, Argument::cetera())
            ->willReturn($transferId)
            ->shouldNotBeCalled();

        $operation = new Operation(2000, new DateTime(), "000001", rand());

        $this->hipay->getBalance($this->technicalAccountArgument)->willReturn(500)->shouldBeCalled();

        $this->operationManager->save($this->operationArgument)->willReturn()->shouldBeCalled();

        $this->logOperationsManager->save(Argument::any())->willReturn()->shouldBeCalled();

        $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(Argument::any(),
            Argument::any())->willReturn(new LogOperations(200, 2001))->shouldBeCalled();


        $result = $this->transferProcessor->transfer($operation);

        $this->assertEquals(Status::TRANSFER_NEGATIVE, $operation->getStatus());

    }

    /**
     * @cover ::transfer
     * @group transfer
     * @expectedException \HiPay\Wallet\Mirakl\Exception\WalletNotFoundException
     */
    public function testTransferWalletNotFound()
    {
        $transferId = rand();

        $this->hipay->isWalletExist(Argument::cetera())
            ->willReturn(false)
            ->shouldBeCalled();

        $this->vendorManager->findByMiraklId(Argument::type("integer"))
            ->willReturn(new Vendor("test@test.com", rand(), rand()))
            ->shouldBeCalled();

        $this->hipay->transfer($this->transferArgument, Argument::cetera())
            ->willReturn($transferId)
            ->shouldNotBeCalled();

        $operation = new Operation(2000, new DateTime(), "000001", rand());

        $this->hipay->getBalance($this->technicalAccountArgument)->willReturn(2001)->shouldNotBeCalled();

        $this->operationManager->save($this->operationArgument)->willReturn()->shouldBeCalled();

        $this->logOperationsManager->save(Argument::any())->willReturn()->shouldBeCalled();

        $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(Argument::any(),
            Argument::any())->willReturn(new LogOperations(200, 2001))->shouldBeCalled();

        $result = $this->transferProcessor->transfer($operation);

        $this->assertEquals(Status::TRANSFER_NEGATIVE, $operation->getStatus());
    }

    /**
     * @cover ::transfer
     * @group transfer
     */
    public function testMerchantUniqueIdTransferSuccessful()
    {
        $operation = new Operation(2000, new DateTime(), "000001", rand());
        $operation->setMerchantUniqueId('VENDOR_MUI');

        $vendor = new Vendor("test@test.com", 'HIPAY_ID', 'MIRAKL_ID');

        $transferId = rand();

        $this->hipay->isWalletExist(Argument::cetera())
            ->willReturn(true)
            ->shouldBeCalled();

        $this->vendorManager->findByMiraklId(Argument::type("integer"))
            ->willReturn($vendor)
            ->shouldBeCalled();

        $this->operationManager->generatePrivateLabel($operation)->willReturn('PRIVATE_LABEL');

        $this->operationManager->generatePublicLabel($operation)->willReturn('PUBLIC_LABEL');

        $transferRequestData = new TransferModel(2000, $vendor, 'PRIVATE_LABEL', 'PUBLIC_LABEL',
            'TRANSFER_VENDOR_MUI');

        $this->hipay->transfer($transferRequestData, $vendor)
            ->willReturn($transferId)
            ->shouldBeCalled();


        $this->hipay->getBalance($this->technicalAccountArgument)->willReturn(2001)->shouldBeCalled();

        $this->operationManager->save($this->operationArgument)->willReturn()->shouldBeCalled();

        $this->logOperationsManager->save(Argument::any())->willReturn()->shouldBeCalled();

        $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(Argument::any(),
            Argument::any())->willReturn(new LogOperations(200, 2001))->shouldBeCalled();

        $result = $this->transferProcessor->transfer($operation);

        $this->assertInternalType("integer", $result);
        $this->assertEquals($transferId, $result);
        $this->assertEquals(Status::TRANSFER_REQUESTED, $operation->getStatus());
    }

    /**
     * Generate a random string
     *
     * @return string
     */
    private function getRandomString()
    {
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
    }
}
