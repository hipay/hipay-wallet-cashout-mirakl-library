<?php

namespace HiPay\Wallet\Mirakl\Test\Cashout;

use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\BankInfo;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\Status;
use HiPay\Wallet\Mirakl\Cashout\Withdraw;
use HiPay\Wallet\Mirakl\Test\Common\AbstractProcessorTest;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\Operation;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\LogOperations;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\Vendor;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Prophecy\Argument;
use Symfony\Component\Validator\Constraints\DateTime;

class WithdrawTest extends AbstractProcessorTest
{
    /** @var  Processor */
    protected $withdrawProcessor;

    /** @var  VendorInterface */
    protected $vendorArgument;

    public function setUp()
    {
        parent::setUp();

        /** @var VendorInterface vendorArgument */
        $this->vendorArgument = Argument::type("\\HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorInterface");

        $this->withdrawProcessor = new Withdraw(
            $this->eventDispatcher->reveal(),
            $this->logger->reveal(),
            $this->apiFactory->reveal(),
            $this->operationManager->reveal(),
            $this->vendorManager->reveal(),
            $this->operator,
            $this->logOperationsManager->reveal()
        );

        /** @var OperationInterface $operationArgument */
        $operationArgument = Argument::type("\\HiPay\\Wallet\\Mirakl\\Cashout\\Model\\Operation\\OperationInterface");

        $this->operationManager->generatePrivateLabel($operationArgument)->willReturn($this->getRandomString());

        $this->operationManager->generatePublicLabel($operationArgument)->willReturn($this->getRandomString());

        $this->operationManager->generateWithdrawLabel($operationArgument)->willReturn($this->getRandomString());

        $this->operationManager->save($operationArgument)->willReturn()->shouldBeCalled();

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

        $this->hipay->isWalletExist(Argument::cetera())
            ->willReturn(true)
            ->shouldBeCalled();

        $this->hipay->isIdentified($operatorArgument)
            ->willReturn(true)
            ->shouldBeCalled();
        $this->hipay->bankInfosStatus($operatorArgument)
            ->willReturn(BankInfo::VALIDATED)
            ->shouldBeCalled();
        $this->hipay->getBalance($operatorArgument)
            ->willReturn($amount + 1)
            ->shouldBeCalled();
        $this->hipay->withdraw($operatorArgument, Argument::is($amount), Argument::type("string"), null)
            ->willReturn($withdrawId)
            ->shouldBeCalled();

        $this->logOperationsManager->save(Argument::any())->willReturn()->shouldBeCalled();

        $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(Argument::any(), Argument::any())->willReturn(new LogOperations(200, 2001))->shouldBeCalled();

        $this->operationManager->findVendorOperationsByPaymentVoucherId(Argument::any())->willReturn(new Operation(2000, new DateTime(), "000001", false));

        $this->vendorManager->findByMiraklId(Argument::any())->willReturn(new Vendor("test@test.com", rand(), rand()));

        $result = $this->withdrawProcessor->withdraw($operation);

        $this->assertEquals($withdrawId, $result);

        $this->assertEquals($amount, $operation->getWithdrawnAmount());

        $this->assertEquals(Status::WITHDRAW_REQUESTED, $operation->getStatus());

        $this->assertEquals($withdrawId, $operation->getWithdrawId());
    }

    /**
     * @throws \Exception
     */
    public function testOperatorWithdrawWrongBalance()
    {
        $withdrawId = rand();
        $amount = floatval(rand());
        $balance = $amount - 1;
        $operation = new Operation($amount, new DateTime(), "000001", false);
        $operation->setStatus(new Status(Status::TRANSFER_SUCCESS));
        /** @var VendorInterface $operatorArgument */
        $operatorArgument = Argument::is($this->operator);

        $this->hipay->isWalletExist(Argument::cetera())
            ->willReturn(true)
            ->shouldBeCalled();

        $this->hipay->isIdentified($operatorArgument)
            ->willReturn(true);
        $this->hipay->bankInfosStatus($operatorArgument)
            ->willReturn(BankInfo::VALIDATED)
            ->shouldBeCalled();
        $this->hipay->getBalance($operatorArgument)
            ->willReturn($balance)->shouldBeCalled();
        $this->hipay->withdraw($operatorArgument, Argument::is($balance), Argument::type("string"), null)
            ->willReturn($withdrawId)->shouldBeCalled();

        $this->logOperationsManager->save(Argument::any())->willReturn()->shouldBeCalled();

        $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(Argument::any(), Argument::any())->willReturn(new LogOperations(200, 2001))->shouldBeCalled();

        $this->operationManager->findVendorOperationsByPaymentVoucherId(Argument::any())->willReturn(new Operation(2000, new DateTime(), "000001", false));

        $this->vendorManager->findByMiraklId(Argument::any())->willReturn(new Vendor("test@test.com", rand(), rand()));

        $result = $this->withdrawProcessor->withdraw($operation);

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

        $this->hipay->isWalletExist(Argument::cetera())
            ->willReturn(true)
            ->shouldBeCalled();

        $this->hipay->isIdentified($vendorArgument)
            ->willReturn(true)
            ->shouldBeCalled();

        $this->hipay->getBalance($vendorArgument)
            ->willReturn($balance)
            ->shouldBeCalled();
        $this->hipay->bankInfosStatus($vendorArgument)
            ->willReturn(BankInfo::VALIDATED)
            ->shouldBeCalled();
        $this->hipay->withdraw($vendorArgument, Argument::is($amount), Argument::type("string"), null)
            ->willReturn($withdrawId)
            ->shouldBeCalled();

        $this->logOperationsManager->save(Argument::any())->willReturn()->shouldBeCalled();

        $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(Argument::any(), Argument::any())->willReturn(new LogOperations(200, 2001))->shouldBeCalled();

        $result = $this->withdrawProcessor->withdraw($operation);

        $this->assertEquals($withdrawId, $result);

        $this->assertEquals($amount, $operation->getWithdrawnAmount());

        $this->assertEquals(Status::WITHDRAW_REQUESTED, $operation->getStatus());

        $this->assertEquals($withdrawId, $operation->getWithdrawId());
    }

    /**
     * @cover ::withdraw
     * @group withdraw
     * @expectedException \HiPay\Wallet\Mirakl\Exception\WrongWalletBalance
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

        $this->hipay->isWalletExist(Argument::cetera())
            ->willReturn(true)
            ->shouldBeCalled();

        $this->hipay->isIdentified($vendorArgument)
            ->willReturn(true);
        $this->hipay->bankInfosStatus($vendorArgument)
            ->willReturn(BankInfo::VALIDATED);
        $this->hipay->getBalance($vendorArgument)
            ->willReturn($amount - 1)
            ->shouldBeCalled();
        $this->hipay->withdraw(Argument::cetera())->shouldNotBeCalled();

        $this->logOperationsManager->save(Argument::any())->willReturn()->shouldBeCalled();

        $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(Argument::any(), Argument::any())->willReturn(new LogOperations(200, 2001))->shouldBeCalled();

        $this->withdrawProcessor->withdraw($operation);
    }

    /**
     * @cover ::withdraw
     * @group withdraw
     * @expectedException \HiPay\Wallet\Mirakl\Exception\WalletNotFoundException
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

        $this->hipay->isWalletExist(Argument::cetera())
            ->willReturn(false)
            ->shouldBeCalled();

        $this->hipay->isIdentified($vendorArgument)
            ->willReturn(true);
        $this->hipay->bankInfosStatus($vendorArgument)
            ->willReturn(BankInfo::VALIDATED);
        $this->hipay->getBalance($vendorArgument)
            ->willReturn($amount + 1);
        $this->hipay->withdraw(Argument::cetera())
            ->shouldNotBeCalled();

        $this->logOperationsManager->save(Argument::any())->willReturn()->shouldBeCalled();

        $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(Argument::any(), Argument::any())->willReturn(new LogOperations(200, 2001))->shouldBeCalled();

        $this->withdrawProcessor->withdraw($operation);
    }

    /**
     * @cover ::withdraw
     * @group withdraw
     * @expectedException \HiPay\Wallet\Mirakl\Exception\UnidentifiedWalletException
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

        $this->hipay->isWalletExist(Argument::cetera())
            ->willReturn(true)
            ->shouldBeCalled();

        $this->hipay->isIdentified($vendorArgument)
            ->willReturn(false)->shouldBeCalled();
        $this->hipay->bankInfosStatus($vendorArgument)
            ->willReturn(BankInfo::VALIDATED);
        $this->hipay->getBalance($vendorArgument)
            ->willReturn($amount + 1);
        $this->hipay->withdraw(Argument::cetera())
            ->shouldNotBeCalled();

        $this->logOperationsManager->save(Argument::any())->willReturn()->shouldBeCalled();

        $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(Argument::any(), Argument::any())->willReturn(new LogOperations(200, 2001))->shouldBeCalled();

        $this->withdrawProcessor->withdraw($operation);
    }

    /**
     * @cover ::withdraw
     * @group withdraw
     * @expectedException \HiPay\Wallet\Mirakl\Exception\UnconfirmedBankAccountException
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

        $this->hipay->isWalletExist(Argument::cetera())
            ->willReturn(true)
            ->shouldBeCalled();

        $this->hipay->isIdentified($vendorArgument)
            ->willReturn(true);
        $this->hipay->bankInfosStatus($vendorArgument)
            ->willReturn(BankInfo::BLANK)->shouldBeCalled();
        $this->hipay->getBalance($vendorArgument)
            ->willReturn($amount + 1);
        $this->hipay->withdraw(Argument::cetera())
            ->shouldNotBeCalled();

        $this->logOperationsManager->save(Argument::any())->willReturn()->shouldBeCalled();

        $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(Argument::any(), Argument::any())->willReturn(new LogOperations(200, 2001))->shouldBeCalled();

        $this->withdrawProcessor->withdraw($operation);
    }

    /**
     * @cover ::withdraw
     * @group withdraw
     */
    public function testWithdrawMerchantUniqueIdSuccessful()
    {
        $withdrawId = rand();
        $amount = floatval(rand());
        $operation = new Operation($amount, new DateTime(), "000001", false);
        $operation->setStatus(new Status(Status::TRANSFER_SUCCESS));
        $operation->setMerchantUniqueId('OPERATOR_MUI');
        /** @var VendorInterface $operatorArgument */
        $operatorArgument = Argument::is($this->operator);

        $this->hipay->isWalletExist(Argument::cetera())
            ->willReturn(true)
            ->shouldBeCalled();

        $this->hipay->isIdentified($operatorArgument)
            ->willReturn(true)
            ->shouldBeCalled();
        $this->hipay->bankInfosStatus($operatorArgument)
            ->willReturn(BankInfo::VALIDATED)
            ->shouldBeCalled();
        $this->hipay->getBalance($operatorArgument)
            ->willReturn($amount + 1)
            ->shouldBeCalled();
        $this->hipay->withdraw($operatorArgument, Argument::is($amount), Argument::type("string"), 'WITHDRAWAL_OPERATOR_MUI')
            ->willReturn($withdrawId)
            ->shouldBeCalled();

        $this->logOperationsManager->save(Argument::any())->willReturn()->shouldBeCalled();

        $this->logOperationsManager->findByMiraklIdAndPaymentVoucherNumber(Argument::any(), Argument::any())->willReturn(new LogOperations(200, 2001))->shouldBeCalled();

        $this->operationManager->findVendorOperationsByPaymentVoucherId(Argument::any())->willReturn(new Operation(2000, new DateTime(), "000001", false));

        $this->vendorManager->findByMiraklId(Argument::any())->willReturn(new Vendor("test@test.com", rand(), rand()));

        $result = $this->withdrawProcessor->withdraw($operation);

        $this->assertEquals($withdrawId, $result);

        $this->assertEquals($amount, $operation->getWithdrawnAmount());

        $this->assertEquals(Status::WITHDRAW_REQUESTED, $operation->getStatus());

        $this->assertEquals($withdrawId, $operation->getWithdrawId());
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
