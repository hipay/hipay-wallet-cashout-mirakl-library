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

        //$this->operationManager->save($operationArgument)->willReturn()->shouldBeCalled();
        
    }

    /**
     * @cover ::hasSufficientFunds
     */
    public function testHasSufficientFunds()
    {
        $this->hipay->getBalance($this->technicalAccountArgument)->willReturn(2001)->shouldBeCalled();
        $this->assertNull($this->transferProcessor->hasSufficientFunds(2000));
    }

    /**
     * @cover ::hasSufficientFunds
     */
    public function testHasNotEnoughFunds()
    {
        $this->hipay->getBalance($this->technicalAccountArgument)->willReturn(2001)->shouldBeCalled();

        $this->setExpectedException("HiPay\Wallet\Mirakl\Exception\WrongWalletBalance");

        $this->transferProcessor->hasSufficientFunds(3000);
    }


    /**
     * @cover ::transfer
     * @group transfer
     */
    public function testVendorTransferSuccessful()
    {
        $transferId = rand();

        $this->vendorManager->findByMiraklId(Argument::type("integer"))
                            ->willReturn(new Vendor("test@test.com", rand(), rand()))
                            ->shouldBeCalled();

        $this->hipay->isAvailable(Argument::containingString("@"), Argument::any())
                    ->willReturn(false)
                    ->shouldBeCalled();

        $this->hipay->transfer($this->transferArgument, Argument::cetera())
                    ->willReturn($transferId)
                    ->shouldBeCalled();

        $operation = new Operation(2000, new DateTime(), "000001", rand());

        $this->hipay->getBalance($this->technicalAccountArgument)->willReturn(2001)->shouldBeCalled();

        $this->operationManager->save($this->operationArgument)->willReturn()->shouldBeCalled();

        $this->logOperationsManager->save(Argument::any())->willReturn()->shouldBeCalled();

        $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(Argument::any(), Argument::any())->willReturn(new LogOperations(200, 2001))->shouldBeCalled();

        $result = $this->transferProcessor->transfer($operation);

        $this->assertInternalType("integer", $result);
        $this->assertEquals($transferId, $result);
        $this->assertEquals(Status::TRANSFER_SUCCESS, $operation->getStatus());
    }


     /**
     * @cover ::transfer
     * @group transfer
     */
    public function testOperatorTransferSuccessful()
    {
        $transferId = rand();

        $operation = new Operation(2000, new DateTime(), "000001", false);

        $this->hipay->getBalance($this->technicalAccountArgument)->willReturn(2001)->shouldBeCalled();

        $this->operationManager->save($this->operationArgument)->willReturn()->shouldBeCalled();

        $this->hipay->isAvailable(Argument::containingString("@"), Argument::any())->willReturn(false)->shouldBeCalled();

        $this->hipay->transfer($this->transferArgument, Argument::cetera())->willReturn($transferId)->shouldBeCalled();

        $this->logOperationsManager->save(Argument::any())->willReturn()->shouldBeCalled();

        $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(Argument::any(), Argument::any())->willReturn(new LogOperations(200, 2001))->shouldBeCalled();

        $result = $this->transferProcessor->transfer($operation);

        $this->assertInternalType("integer", $result);
        $this->assertEquals($transferId, $result);
        $this->assertEquals(Status::TRANSFER_SUCCESS, $operation->getStatus());
    }


    /**
     * @cover ::transfer
     * @group transfer
     */
    public function testVendorTransferWrongWalletBalance()
    {
        $transferId = rand();

        $this->vendorManager->findByMiraklId(Argument::type("integer"))
                            ->willReturn(new Vendor("test@test.com", rand(), rand()))
                            ->shouldBeCalled();

        $this->hipay->isAvailable(Argument::containingString("@"), Argument::any())
                    ->willReturn(false)
                    ->shouldBeCalled();

        $this->hipay->transfer($this->transferArgument, Argument::cetera())
                    ->willReturn($transferId)
                    ->shouldNotBeCalled();

        $operation = new Operation(2000, new DateTime(), "000001", rand());

        $this->hipay->getBalance($this->technicalAccountArgument)->willReturn(500)->shouldBeCalled();

        $this->operationManager->save($this->operationArgument)->willReturn()->shouldBeCalled();

        $this->logOperationsManager->save(Argument::any())->willReturn()->shouldBeCalled();

        $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(Argument::any(), Argument::any())->willReturn(new LogOperations(200, 2001))->shouldBeCalled();

        $this->setExpectedException("HiPay\Wallet\Mirakl\Exception\WrongWalletBalance");

        $result = $this->transferProcessor->transfer($operation);

        $this->assertEquals(Status::TRANSFER_NEGATIVE, $operation->getStatus());
        
    }

    /**
     * @cover ::transfer
     * @group transfer
     */
    public function testTransferWalletNotFound()
    {
        $transferId = rand();

        $this->vendorManager->findByMiraklId(Argument::type("integer"))
                            ->willReturn(new Vendor("test@test.com", rand(), rand()))
                            ->shouldBeCalled();

        $this->hipay->isAvailable(Argument::containingString("@"), Argument::any())
                    ->willReturn(true)
                    ->shouldBeCalled();

        $this->hipay->transfer($this->transferArgument, Argument::cetera())
                    ->willReturn($transferId)
                    ->shouldNotBeCalled();

        $operation = new Operation(2000, new DateTime(), "000001", rand());

        $this->hipay->getBalance($this->technicalAccountArgument)->willReturn(2001)->shouldNotBeCalled();

        $this->operationManager->save($this->operationArgument)->willReturn()->shouldBeCalled();

        $this->logOperationsManager->save(Argument::any())->willReturn()->shouldBeCalled();

        $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(Argument::any(), Argument::any())->willReturn(new LogOperations(200, 2001))->shouldBeCalled();

        $this->setExpectedException("HiPay\Wallet\Mirakl\Exception\WalletNotFoundException");

        $result = $this->transferProcessor->transfer($operation);

        $this->assertEquals(Status::TRANSFER_NEGATIVE, $operation->getStatus());

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
