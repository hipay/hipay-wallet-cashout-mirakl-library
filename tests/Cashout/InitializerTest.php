<?php

namespace HiPay\Wallet\Mirakl\Test\Cashout;

use DateTime;
use HiPay\Wallet\Mirakl\Cashout\Initializer;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;
use HiPay\Wallet\Mirakl\Test\Common\AbstractProcessorTest;
use HiPay\Wallet\Mirakl\Test\Stub\Api\Mirakl;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\Operation;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Prophecy\Argument;
use Prophecy\Argument\Token\TypeToken;

/**
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 *
 * @coversDefaultClass \HiPay\Wallet\Mirakl\Cashout\Initializer
 */
class InitializerTest extends AbstractProcessorTest
{
    /** @var  VendorInterface */
    protected $technicalAccountArgument;

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

        /** @var VendorInterface vendorArgument */
        $this->technicalAccountArgument = Argument::is($this->technical);

        $this->cashoutInitializer = new Initializer(
            $this->eventDispatcher->reveal(),
            $this->logger->reveal(),
            $this->apiFactory->reveal(),
            $this->operator,
            $this->technical,
            $this->transactionValidator->reveal(),
            $this->operationManager->reveal(),
            $this->vendorManager->reveal()
        );
    }

    /**
     * @cover ::hasSufficientFunds
     */
    public function testHasSufficientFunds()
    {
        $this->hipay->getBalance($this->technicalAccountArgument)->willReturn(2001)->shouldBeCalled();

        $this->assertTrue($this->cashoutInitializer->hasSufficientFunds(2000));
    }

    /**
     * @cover ::hasSufficientFunds
     */
    public function testHasNotEnoughFunds()
    {
        $this->hipay->getBalance($this->technicalAccountArgument)->willReturn(2001)->shouldBeCalled();

        $this->assertFalse($this->cashoutInitializer->hasSufficientFunds(3000));
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

        $expectedOperation = new Operation(200, new DateTime(), "000001", 2001);
        $expectedOperation->setHipayId(null);

        $resultOperation = $this->cashoutInitializer->createOperation((float) 200, new DateTime(), "000001", 2001);

        $this->assertEquals($expectedOperation, $resultOperation);
    }

    /**
     * @cover ::createOperation
     */
    public function testCreateOperatorOperation()
    {
        $this->operationManager->create(
            Argument::type('float'),
            Argument::type('DateTime'),
            Argument::type('string'),
            Argument::is(false)
        )->will(function ($args) {
            list($amount, $cycleDate, $paymentVoucher, $miraklId) = $args;
            return new Operation($amount, $cycleDate, $paymentVoucher, $miraklId);
        })->shouldBeCalled();

        $expectedOperation = new Operation(200, new DateTime(), "000001", false);
        $expectedOperation->setHipayId($this->operator->getHipayId());

        $resultOperation = $this->cashoutInitializer->createOperation((float) 200, new DateTime(), "000001", false);

        $this->assertEquals($resultOperation->getHiPayId(), $this->operator->getHipayId());

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

        $this->assertFalse($this->cashoutInitializer->isOperationValid($operation));
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

        $this->operationManager->findByMiraklIdAndPaymentVoucherNumber(Argument::is(false), Argument::type("string"))
            ->willReturn(null)
            ->shouldBeCalled();

        $this->operationManager->isValid($this->operationArgument)->willReturn(true)->shouldBeCalled();

        $this->cashoutInitializer->saveOperations(array(new Operation(200, new DateTime(), "000001", false)));
    }

    /**
     * @covers ::getOrderTransactionTypes
     */
    public function testGetOrderTransactionTypes()
    {
        $result = $this->cashoutInitializer->getOrderTransactionTypes();

        $this->assertNotEmpty($result);

        $this->assertInternalType("array", $result);

        $this->assertContainsOnly("string", $result);
    }

    /**
     * @covers ::getOperatorTransactionTypes
     */
    public function testGetOperatorTransactionTypes()
    {
        $result = $this->cashoutInitializer->getOperatorTransactionTypes();

        $this->assertNotEmpty($result);

        $this->assertInternalType("array", $result);

        $this->assertContainsOnly("string", $result);
    }

    /**
     * @covers ::getPaymentTransaction
     */
    public function testGetPaymentTransactionTypes()
    {
        $this->mirakl->getTransactions(Argument::cetera())->shouldBeCalled()->will(function () {
            return Mirakl::getPaymentTransactions();
        });

        $result = $this->cashoutInitializer->getPaymentTransactions(new DateTime(), new DateTime());

        $this->assertContainsOnly("array", $result);

        $this->assertNotEmpty($result);

        $this->assertInternalType("array", $result);

        foreach ($result as $paymentTransaction) {
            $this->assertArrayHasKey("transaction_type", $paymentTransaction);

            $this->assertEquals("PAYMENT", $paymentTransaction["transaction_type"]);
        }
    }

    /**
     * @covers ::getOrderTransaction
     */
    public function testGetOrderTransaction()
    {
        $shopId = 2031;
        $paymentVoucher = "000002";
        $this->mirakl->getTransactions(Argument::cetera())->shouldBeCalled()
            ->will(
                function () use ($shopId, $paymentVoucher) {
                    return Mirakl::getOrderTransactions($shopId, $paymentVoucher, "miscellaneousOrders.json");
                }
            );

        $result = $this->cashoutInitializer->getOrderTransactions($shopId, $paymentVoucher);

        $this->assertContainsOnly("array", $result);

        $this->assertNotEmpty($result);

        $this->assertInternalType("array", $result);

        foreach ($result as $paymentTransaction) {
            $this->assertArrayHasKey("transaction_type", $paymentTransaction);
            $this->assertEquals(
                true,
                in_array(
                    $paymentTransaction["transaction_type"],
                    $this->cashoutInitializer->getOrderTransactionTypes()
                )
            );
        }
    }

    /**
     * @covers ::handlePaymentVoucher
     * @covers ::computeOperatorAmount
     * @covers ::computeVendorAmount
     */
    public function testSimpleOrder()
    {
        /** @var OperationInterface $operatorOperation */
        $operations = $this->setOrderTestAssertion("simpleOrder.json");

        $this->assertCount(2, $operations);

        /** @var OperationInterface $vendorOperation */
        $vendorOperation = reset($operations);

        $this->assertTrue($vendorOperation->getMiraklId() != false);

        $this->assertEquals((float) 5000, $vendorOperation->getAmount());

        /** @var OperationInterface $operatorOperation */
        $operatorOperation = end($operations);

        $this->assertNull($operatorOperation->getMiraklId());

        $this->assertEquals((float) 240, $operatorOperation->getAmount());
    }

    /**
     * @covers ::handlePaymentVoucher
     * @covers ::computeOperatorAmount
     * @covers ::computeVendorAmount
     */
    public function testWithManualCredit()
    {
        /** @var OperationInterface $operatorOperation */
        $operations = $this->setOrderTestAssertion("withManualCredit.json");

        $this->assertCount(2, $operations);

        /** @var OperationInterface $vendorOperation */
        $vendorOperation = reset($operations);

        $this->assertTrue($vendorOperation->getMiraklId() != false);

        $this->assertEquals((float) 5000, $vendorOperation->getAmount());

        /** @var OperationInterface $operatorOperation */
        $operatorOperation = end($operations);

        $this->assertNull($operatorOperation->getMiraklId());

        $this->assertEquals((float) 2400, $operatorOperation->getAmount());
    }

    /**
     * @covers ::handlePaymentVoucher
     * @covers ::computeOperatorAmount
     * @covers ::computeVendorAmount
     */
    public function testWithoutCommission()
    {
        /** @var OperationInterface $operatorOperation */
        $operations = $this->setOrderTestAssertion("withoutCommission.json", false);

        $this->assertCount(1, $operations);

        /** @var OperationInterface $vendorOperation */
        $vendorOperation = reset($operations);

        $this->assertTrue($vendorOperation->getMiraklId() != false);

        $this->assertEquals((float) 5000, $vendorOperation->getAmount());
    }

    /**
     * @covers ::handlePaymentVoucher
     * @covers ::computeOperatorAmount
     * @covers ::computeVendorAmount
     */
    public function testWithoutShipping()
    {
        /** @var OperationInterface $operatorOperation */
        $operations = $this->setOrderTestAssertion("withoutShipping.json");

        $this->assertCount(2, $operations);

        /** @var OperationInterface $vendorOperation */
        $vendorOperation = reset($operations);

        $this->assertTrue($vendorOperation->getMiraklId() != false);

        $this->assertEquals((float) 5000, $vendorOperation->getAmount());

        /** @var OperationInterface $operatorOperation */
        $operatorOperation = end($operations);

        $this->assertNull($operatorOperation->getMiraklId());

        $this->assertEquals((float) 600, $operatorOperation->getAmount());
    }

    /**
     * @covers ::handlePaymentVoucher
     * @covers ::computeOperatorAmount
     * @covers ::computeVendorAmount
     */
    public function testWithRefund()
    {
        /** @var OperationInterface $operatorOperation */
        $operations = $this->setOrderTestAssertion("withRefund.json");

        $this->assertCount(2, $operations);

        /** @var OperationInterface $vendorOperation */
        $vendorOperation = reset($operations);

        $this->assertTrue($vendorOperation->getMiraklId() != false);

        $this->assertEquals((float) 5000, $vendorOperation->getAmount());

        /** @var OperationInterface $operatorOperation */
        $operatorOperation = end($operations);

        $this->assertNull($operatorOperation->getMiraklId());

        $this->assertEquals((float) 500, $operatorOperation->getAmount());
    }

    /**
     * @param $file
     *
     * @param $withOperatorOperation
     * @return array
     */
    protected function setOrderTestAssertion($file, $withOperatorOperation = true)
    {
        $this->setOrderTestProphecy($file, $withOperatorOperation);

        $operations = $this->cashoutInitializer->handlePaymentVoucher(
            "000001",
            array(2001 => 5000),
            new DateTime()
        );

        $this->assertInternalType("array", $operations);

        $this->assertContainsOnly(
            "\\HiPay\\Wallet\\Mirakl\\Cashout\\Model\\Operation\\OperationInterface",
            $operations
        );



        return $operations;
    }

    /**
     * @param $file
     * @param bool|true $withOperatorOperation
     */
    public function setOrderTestProphecy($file, $withOperatorOperation = true)
    {
        $this->mirakl->getTransactions(
            Argument::type('integer'),
            Argument::is(null),
            Argument::is(null),
            Argument::is(null),
            Argument::is(null),
            Argument::is(null),
            Argument::type("string"),
            Argument::cetera()
        )->will(function ($args) use ($file) {
            $shopId = $args[0];
            $paymentVoucher = $args[6];
            $array = Mirakl::getOrderTransactions($shopId, $paymentVoucher, $file);
            return $array;
        })->shouldBeCalled();

        $this->transactionValidator->isValid(Argument::type('array'))->willReturn(true)->shouldBeCalled();

        $this->operationManager->create(
            Argument::type('float'),
            Argument::type('DateTime'),
            Argument::type('string'),
            Argument::type('int')
        )->will(function ($args) {
            list($amount, $cycleDate, $paymentVoucher, $miraklId) = $args;
            return new Operation($amount, $cycleDate, $paymentVoucher, $miraklId);
        })->shouldBeCalled();

        if ($withOperatorOperation) {
            $this->operationManager->create(
                Argument::type('float'),
                Argument::type('DateTime'),
                Argument::type('string'),
                Argument::is(null)
            )->will(function ($args) {
                list($amount, $cycleDate, $paymentVoucher, $miraklId) = $args;
                return new Operation($amount, $cycleDate, $paymentVoucher, $miraklId);
            })->shouldBeCalled();
        }
    }
}
