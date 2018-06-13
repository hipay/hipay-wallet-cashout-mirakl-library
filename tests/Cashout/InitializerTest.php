<?php

namespace HiPay\Wallet\Mirakl\Test\Cashout;

use DateTime;
use HiPay\Wallet\Mirakl\Cashout\Initializer;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Test\Common\AbstractProcessorTest;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\Operation;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\Vendor;
use Prophecy\Argument;
use Prophecy\Argument\Token\TypeToken;


class InitializerTest extends AbstractProcessorTest
{

    /** @var  OperationInterface|TypeToken */
    private $operationArgument;

    /** @var  Initializer */
    private $cashoutInitializer;

    public function setUp()
    {
        parent::setUp();

        /** @var string emailArgument */
        $this->operationArgument =
            Argument::type("\\HiPay\\Wallet\\Mirakl\\Cashout\\Model\\Operation\\OperationInterface");

        $this->vendorArgument = Argument::type("\\HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorInterface");

        $this->cashoutInitializer = new Initializer(
            $this->eventDispatcher->reveal(),
            $this->logger->reveal(),
            $this->apiFactory->reveal(),
            $this->operator,
            $this->technical,
            $this->transactionValidator->reveal(),
            $this->operationManager->reveal(),
            $this->logOperationsManager->reveal(),
            $this->vendorManager->reveal()
        );
    }

    /**
     * @cover ::createOperation
     */
    public function testCreateVendorOperation()
    {
        $this->operationManager->create(
            Argument::type('float'),
            Argument::type('DateTime'),
            Argument::type('string'),
            Argument::type('int')
        )->will(function ($args) {
            list($amount, $cycleDate, $paymentVoucher, $miraklId) = $args;
            return new Operation($amount, $cycleDate, $paymentVoucher, $miraklId);
        })->shouldBeCalled();

        $this->operationManager->isValid($this->operationArgument)->willReturn(true)->shouldBeCalled();

        $expectedOperation = new Operation(200, new DateTime(), "000001", 2001);

        $expectedOperation->setOriginAmount(200);

        $expectedOperation->setHipayId(109);

        $this->operationManager->findByMiraklIdAndPaymentVoucherNumber(Argument::type('int'), Argument::type("string"))
            ->willReturn(null)
            ->shouldBeCalled();

        $vendor = new Vendor("test@test.com", 109, 2001);

        $resultOperation = $this->cashoutInitializer->createOperation((float) 200, (float) 200, new DateTime(), "000001", $vendor);

        $this->assertEquals($expectedOperation, $resultOperation);
    }

    /**
     * @cover ::isOperationValid
     */
    public function testOperationValid()
    {
        $operation = new Operation(200, new DateTime(), "000001", false);

        $this->operationManager->findByMiraklIdAndPaymentVoucherNumber(Argument::is(false), Argument::type("string"))
                ->willReturn(null)
                ->shouldBeCalled();

        $this->operationManager->isValid($this->operationArgument)->willReturn(true)->shouldBeCalled();

        $this->assertTrue($this->cashoutInitializer->isOperationValid($operation));
    }

    /**
     * @cover ::isOperationValid
     */
    public function testOperationAlreadyCreated()
    {
        $operation = new Operation(200, new DateTime(), "000001", false);

        $this->operationManager->findByMiraklIdAndPaymentVoucherNumber(Argument::is(false), Argument::type("string"))
            ->willReturn($operation)
            ->shouldBeCalled();

        $this->operationManager->isValid($this->operationArgument)->willReturn(true)->shouldNotBeCalled();

        $this->setExpectedException("HiPay\Wallet\Mirakl\Exception\AlreadyCreatedOperationException");

        $this->cashoutInitializer->isOperationValid($operation);

        $this->assertFalse($this->cashoutInitializer->isOperationValid($operation));
    }

    /**
     * @cover ::isOperationValid
     */
    public function testInvalidOperation()
    {
        $operation = new Operation(200, new DateTime(), "000001", false);

        $this->operationManager->findByMiraklIdAndPaymentVoucherNumber(Argument::is(false), Argument::type("string"))
            ->willReturn(null)
            ->shouldBeCalled();

        $this->operationManager->isValid($this->operationArgument)->willReturn(false)->shouldBeCalled();

        $this->setExpectedException("HiPay\Wallet\Mirakl\Exception\InvalidOperationException");

        $this->cashoutInitializer->isOperationValid($operation);
    }

    /**
     * @cover ::saveOperations
     * @cover ::areOperationValid
     */
    public function testSaveOperation()
    {
        /** @var OperationInterface[] $callback */
        $callback = Argument::that(function ($arg) {
            if (!is_array($arg)) {
                return false;
            }
            while (($value = next($arg))) {
                if (!$value instanceof OperationInterface) {
                    return false;
                }
            }
            return true;
        });

        $this->operationManager->saveAll($callback)->shouldBeCalled();

        $this->logOperationsManager->saveAll(Argument::type("array"))->shouldBeCalled();

        $this->cashoutInitializer->saveOperations(array(new Operation(200, new DateTime(), "000001", false)));
    }

}
