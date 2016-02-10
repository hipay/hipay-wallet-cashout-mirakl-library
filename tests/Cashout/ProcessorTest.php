<?php

namespace HiPay\Wallet\Mirakl\Test\Cashout;

use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\Transfer;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\BankInfo;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\Status;
use HiPay\Wallet\Mirakl\Cashout\Processor;
use HiPay\Wallet\Mirakl\Test\Common\AbstractProcessorTest;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\Operation;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\Vendor;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Prophecy\Argument;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * File Processor.php.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 *
 * @coversDefaultClass \HiPay\Wallet\Mirakl\Cashout\Initializer
 */
class ProcessorTest extends AbstractProcessorTest
{
    /** @var  Processor */
    protected $cashoutProcessor;

    /** @var  VendorInterface */
    protected $vendorArgument;

    /** @var Transfer  */
    protected $transferArgument;
    
    public function setUp()
    {
        parent::setUp();

        /** @var VendorInterface vendorArgument */
        $this->vendorArgument = Argument::type("\\HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorInterface");

        $this->transferArgument = Argument::type("\\HiPay\\Wallet\\Mirakl\\Api\\HiPay\\Model\\Soap\\Transfer");
        
        $this->cashoutProcessor = new Processor(
            $this->eventDispatcher->reveal(),
            $this->logger->reveal(),
            $this->apiFactory->reveal(),
            $this->operationManager->reveal(),
            $this->vendorManager->reveal(),
            $this->operator
        );

        /** @var OperationInterface $operationArgument */
        $operationArgument = Argument::type("\\HiPay\\Wallet\\Mirakl\\Cashout\\Model\\Operation\\OperationInterface");

        $this->operationManager->generatePrivateLabel($operationArgument)->willReturn($this->getRandomString());

        $this->operationManager->generatePublicLabel($operationArgument)->willReturn($this->getRandomString());

        $this->operationManager->generateWithdrawLabel($operationArgument)->willReturn($this->getRandomString());

        $this->operationManager->save($operationArgument)->willReturn()->shouldBeCalled();
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

        $this->hipay->isAvailable(Argument::containingString("@"))
                    ->willReturn(false)
                    ->shouldBeCalled();

        $this->hipay->transfer($this->transferArgument, Argument::cetera())
                    ->willReturn($transferId)
                    ->shouldBeCalled();

        $operation = new Operation(2000, new DateTime(), "000001", rand());
        
        $result = $this->cashoutProcessor->transfer($operation);

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

        $this->hipay->isAvailable(Argument::containingString("@"))->willReturn(false)->shouldBeCalled();
        $this->hipay->transfer($this->transferArgument, Argument::cetera())->willReturn($transferId)->shouldBeCalled();

        $result = $this->cashoutProcessor->transfer($operation);

        $this->assertInternalType("integer", $result);

        $this->assertEquals($transferId, $result);
        
        $this->assertEquals(Status::TRANSFER_SUCCESS, $operation->getStatus());
    }

    /**
     * @cover ::transfer
     * @group transfer
     */
    public function testTransferWalletNotFound()
    {
        $operation = new Operation(2000, new DateTime(), "000001", rand());

        $this->hipay->isAvailable(Argument::containingString("@"))->willReturn(true)->shouldBeCalled();

        $this->hipay->transfer(Argument::any())->shouldNotBeCalled();

        $this->vendorManager->findByMiraklId(Argument::type("integer"))
                            ->willReturn(new Vendor("test@test.com", rand(), rand()))
                            ->shouldBeCalled();

        $this->setExpectedException("\\HiPay\\Wallet\\Mirakl\\Exception\\WalletNotFoundException");

        $this->cashoutProcessor->transfer($operation);

        $this->assertEquals(Status::TRANSFER_FAILED, $operation->getStatus());
    }

    /**
     * @cover ::withdraw
     * @group withdraw
     */
    public function testOperatorWithdrawSuccessful()
    {
        $withdrawId = rand();
        $amount = floatval(rand());
        $operation = new Operation($amount, new DateTime(), "000001", false);
        $operation->setStatus(new Status(Status::TRANSFER_SUCCESS));
        /** @var VendorInterface $operatorArgument */
        $operatorArgument = Argument::is($this->operator);

        $this->hipay->isAvailable(Argument::containingString("@"), Argument::any())
                    ->willReturn(false)
                    ->shouldBeCalled();
        $this->hipay->isIdentified(Argument::containingString("@"))
                    ->willReturn(true)
                    ->shouldBeCalled();
        $this->hipay->bankInfosStatus($operatorArgument)
            ->willReturn(BankInfo::VALIDATED)
            ->shouldBeCalled();
        $this->hipay->getBalance($operatorArgument)
                    ->willReturn($amount + 1)
                    ->shouldBeCalled();
        $this->hipay->withdraw($operatorArgument, Argument::is($amount), Argument::type("string"))
                    ->willReturn($withdrawId)
                    ->shouldBeCalled();

        $result = $this->cashoutProcessor->withdraw($operation);

        $this->assertEquals($withdrawId, $result);

        $this->assertEquals($amount, $operation->getWithdrawnAmount());

        $this->assertEquals(Status::WITHDRAW_REQUESTED, $operation->getStatus());

        $this->assertEquals($withdrawId, $operation->getWithdrawId());
    }

    public function testOperatorWithdrawWrongBalance()
    {
        $withdrawId = rand();
        $amount = floatval(rand());
        $balance = $amount - 1;
        $operation = new Operation($amount, new DateTime(), "000001", false);
        $operation->setStatus(new Status(Status::TRANSFER_SUCCESS));
        /** @var VendorInterface $operatorArgument */
        $operatorArgument = Argument::is($this->operator);

        $this->hipay->isAvailable(Argument::containingString("@"), Argument::any())
            ->willReturn(false);
        $this->hipay->isIdentified(Argument::containingString("@"))
            ->willReturn(true);
        $this->hipay->bankInfosStatus($operatorArgument)
            ->willReturn(BankInfo::VALIDATED)
            ->shouldBeCalled();
        $this->hipay->getBalance($operatorArgument)
            ->willReturn($balance)->shouldBeCalled();
        $this->hipay->withdraw($operatorArgument, Argument::is($balance), Argument::type("string"))
            ->willReturn($withdrawId)->shouldBeCalled();

        $result = $this->cashoutProcessor->withdraw($operation);

        $this->assertEquals($withdrawId, $result);

        $this->assertEquals($balance, $operation->getWithdrawnAmount());

        $this->assertEquals(Status::WITHDRAW_REQUESTED, $operation->getStatus());

        $this->assertEquals($withdrawId, $operation->getWithdrawId());
    }

    /**
     * @cover ::withdraw
     * @group withdraw
     */
    public function testVendorWithdrawSuccessful()
    {
        $withdrawId = rand();
        $amount = floatval(rand());
        $balance = $amount + 1;
        $vendor = new Vendor("test@test.com", rand(), rand());
        $operation = new Operation($amount, new DateTime(), "000001", $vendor->getHipayId());
        $operation->setStatus(new Status(Status::TRANSFER_SUCCESS));

        /** @var VendorInterface $vendorArgument */
        $vendorArgument = Argument::is($vendor);

        $this->vendorManager->findByMiraklId(Argument::is($operation->getMiraklId()))->willReturn($vendor);

        $this->hipay->isAvailable(Argument::containingString("@"), Argument::any())
            ->willReturn(false)
            ->shouldBeCalled();
        $this->hipay->isIdentified(Argument::containingString("@"))
            ->willReturn(true)
            ->shouldBeCalled();
        $this->hipay->getBalance($vendorArgument)
            ->willReturn($balance)
            ->shouldBeCalled();
        $this->hipay->bankInfosStatus($vendorArgument)
            ->willReturn(BankInfo::VALIDATED)
            ->shouldBeCalled();
        $this->hipay->withdraw($vendorArgument, Argument::is($amount), Argument::type("string"))
            ->willReturn($withdrawId)
            ->shouldBeCalled();

        $result = $this->cashoutProcessor->withdraw($operation);

        $this->assertEquals($withdrawId, $result);

        $this->assertEquals($amount, $operation->getWithdrawnAmount());

        $this->assertEquals(Status::WITHDRAW_REQUESTED, $operation->getStatus());

        $this->assertEquals($withdrawId, $operation->getWithdrawId());
    }

    /**
     * @cover ::withdraw
     * @group withdraw
     */
    public function testVendorWithdrawWrongBalance()
    {
        $amount = floatval(rand());
        $vendor = new Vendor("test@test.com", rand(), rand());
        $operation = new Operation($amount, new DateTime(), "000001", $vendor->getHipayId());
        $operation->setStatus(new Status(Status::TRANSFER_SUCCESS));

        /** @var VendorInterface $vendorArgument */
        $vendorArgument = Argument::is($vendor);

        $this->vendorManager->findByMiraklId(Argument::is($operation->getMiraklId()))->willReturn($vendor);

        $this->hipay->isAvailable(Argument::containingString("@"), Argument::any())
            ->willReturn(false);
        $this->hipay->isIdentified(Argument::containingString("@"))
            ->willReturn(true);
        $this->hipay->bankInfosStatus($vendorArgument)
            ->willReturn(BankInfo::VALIDATED);
        $this->hipay->getBalance($vendorArgument)
            ->willReturn($amount - 1)
            ->shouldBeCalled();
        $this->hipay->withdraw(Argument::cetera())->shouldNotBeCalled();

        $this->setExpectedException("\\HiPay\\Wallet\\Mirakl\\Exception\\WrongWalletBalance");

        $this->cashoutProcessor->withdraw($operation);
    }

    /**
     * @cover ::withdraw
     * @group withdraw
     */
    public function testWithdrawWalletNotFound()
    {
        $amount = floatval(rand());
        $vendor = new Vendor("test@test.com", rand(), rand());
        $operation = new Operation($amount, new DateTime(), "000001", $vendor->getHipayId());
        $operation->setStatus(new Status(Status::TRANSFER_SUCCESS));

        /** @var VendorInterface $vendorArgument */
        $vendorArgument = Argument::is($vendor);

        $this->vendorManager->findByMiraklId(Argument::is($operation->getMiraklId()))->willReturn($vendor);

        $this->hipay->isAvailable(Argument::containingString("@"), Argument::any())
            ->willReturn(true)->shouldBeCalled();
        $this->hipay->isIdentified(Argument::containingString("@"))
            ->willReturn(true);
        $this->hipay->bankInfosStatus($vendorArgument)
            ->willReturn(BankInfo::VALIDATED);
        $this->hipay->getBalance($vendorArgument)
            ->willReturn($amount + 1);
        $this->hipay->withdraw(Argument::cetera())
                    ->shouldNotBeCalled();

        $this->setExpectedException("\\HiPay\\Wallet\\Mirakl\\Exception\\WalletNotFoundException");

        $this->cashoutProcessor->withdraw($operation);
    }

    /**
     * @cover ::withdraw
     * @group withdraw
     */
    public function testWithdrawUnidentifiedWallet()
    {
        $amount = floatval(rand());
        $vendor = new Vendor("test@test.com", rand(), rand());
        $operation = new Operation($amount, new DateTime(), "000001", $vendor->getHipayId());
        $operation->setStatus(new Status(Status::TRANSFER_SUCCESS));

        /** @var VendorInterface $vendorArgument */
        $vendorArgument = Argument::is($vendor);

        $this->vendorManager->findByMiraklId(Argument::is($operation->getMiraklId()))->willReturn($vendor);

        $this->hipay->isAvailable(Argument::containingString("@"), Argument::any())
            ->willReturn(false);
        $this->hipay->isIdentified(Argument::containingString("@"))
            ->willReturn(false)->shouldBeCalled();
        $this->hipay->bankInfosStatus($vendorArgument)
            ->willReturn(BankInfo::VALIDATED);
        $this->hipay->getBalance($vendorArgument)
            ->willReturn($amount + 1);
        $this->hipay->withdraw(Argument::cetera())
            ->shouldNotBeCalled();

        $this->setExpectedException("\\HiPay\\Wallet\\Mirakl\\Exception\\UnidentifiedWalletException");

        $this->cashoutProcessor->withdraw($operation);
    }

    /**
     * @cover ::withdraw
     * @group withdraw
     */
    public function testWithdrawUnvalidatedBankInfo()
    {
        $amount = floatval(rand());
        $vendor = new Vendor("test@test.com", rand(), rand());
        $operation = new Operation($amount, new DateTime(), "000001", $vendor->getHipayId());
        $operation->setStatus(new Status(Status::TRANSFER_SUCCESS));

        /** @var VendorInterface $vendorArgument */
        $vendorArgument = Argument::is($vendor);

        $this->vendorManager->findByMiraklId(Argument::is($operation->getMiraklId()))->willReturn($vendor);

        $this->hipay->isAvailable(Argument::containingString("@"), Argument::any())
            ->willReturn(false);
        $this->hipay->isIdentified(Argument::containingString("@"))
            ->willReturn(true);
        $this->hipay->bankInfosStatus($vendorArgument)
            ->willReturn(BankInfo::BLANK)->shouldBeCalled();
        $this->hipay->getBalance($vendorArgument)
            ->willReturn($amount + 1);
        $this->hipay->withdraw(Argument::cetera())
            ->shouldNotBeCalled();

        $this->setExpectedException("\\HiPay\\Wallet\\Mirakl\\Exception\\UnconfirmedBankAccountException");

        $this->cashoutProcessor->withdraw($operation);
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
