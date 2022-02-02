<?php

namespace HiPay\Wallet\Mirakl\Test\Notification;

use DateTime;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\Status;
use HiPay\Wallet\Mirakl\Notification\Handler;
use HiPay\Wallet\Mirakl\Test\Common\AbstractProcessorTest;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\Operation;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\Vendor;
use Prophecy\Argument;

/**
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class HandlerTest extends AbstractProcessorTest
{
    /** @var  Handler */
    protected $notificationHandler;
    protected $testFilesPath;
    protected $notificationEventClassPath;

    public function setUp()
    {
        parent::setUp();

        $this->testFilesPath = __DIR__ . "/../../data/test/notification/";

        $this->notificationHandler = new Handler(
            $this->eventDispatcher->reveal(),
            $this->logger->reveal(),
            $this->operationManager->reveal(),
            $this->vendorManager->reveal(),
            $this->logVendorManager->reveal(),
            $this->apiFactory->reveal(),
            $this->logOperationsManager->reveal()
        );
    }

    /**
     * @cover ::handleHiPayNotification
     * @throws \HiPay\Wallet\Mirakl\Exception\ChecksumFailedException
     * @throws \HiPay\Wallet\Mirakl\Exception\IllegalNotificationOperationException
     * @expectedException \HiPay\Wallet\Mirakl\Exception\ChecksumFailedException
     */
    public function testMd5Failure()
    {
        $xml = $this->readFile("md5Fail.xml");

        $vendor = new Vendor(
            "test@test.com",
            1,
            1,
            1,
            1,
            1,
            "test",
            1,
            'FR'
        );

        $this->vendorManager->findByHiPayId(Argument::any())
            ->willReturn($vendor)
            ->shouldBeCalled();

        $this->hipay->getAccountHiPay(Argument::any())
            ->willReturn(array("callback_salt" => "test"))
            ->shouldBeCalled();

        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $this->notificationHandler->handleHiPayNotification($xml);
    }

    /**
     * @cover ::handleHiPayNotification
     */
    public function testMd5Different()
    {
        $xml = $this->readFile("other.xml");

        $vendor = new Vendor(
            "test@test.com",
            1,
            1,
            1,
            1,
            1,
            "testfalse",
            1,
            'FR'
        );

        $this->vendorManager->findByHiPayId(Argument::any())
            ->willReturn($vendor)
            ->shouldBeCalled();

        $this->hipay->getAccountHiPay(Argument::any())
            ->willReturn(array("callback_salt" => "test"))
            ->shouldBeCalled();

        $this->vendorManager->save(Argument::any())->shouldBeCalled();

        $this->notificationHandler->handleHiPayNotification($xml);
    }

    /**
     * @cover ::handleHiPayNotification
     * @throws \HiPay\Wallet\Mirakl\Exception\ChecksumFailedException
     * @throws \HiPay\Wallet\Mirakl\Exception\IllegalNotificationOperationException
     */
    public function testBankInfoNotification()
    {
        $xml = $this->readFile("bankInfoValidation.xml");

        $vendor = new Vendor(
            "test@test.com",
            1,
            1,
            1,
            1,
            1,
            "test",
            1,
            'FR'
        );

        $expectedVendor  = clone $vendor;

        $expectedVendor->setPaymentBlocked(false);

        $this->mirakl->updateOneVendor(array(
            "kyc" => array("reason" => "", "status" => "APPROVED"),
            "shop_id" => $vendor->getMiraklId(),
            "payment_blocked" => false,
            "suspend" => false
        ))->shouldBeCalled();

        $this->vendorManager->findByHiPayId(Argument::any())
            ->willReturn($vendor)
            ->shouldBeCalled();

        $this->vendorManager->save($expectedVendor)->shouldBeCalled();

        $this->hipay->getAccountHiPay(Argument::any())
            ->willReturn(array("callback_salt" => "test"))
            ->shouldBeCalled();

        $this->setEventAssertion("bankInfos", "BankInfo");

        $this->notificationHandler->handleHiPayNotification($xml);
    }

    /**
     * @cover ::handleHiPayNotification
     * @throws \HiPay\Wallet\Mirakl\Exception\ChecksumFailedException
     * @throws \HiPay\Wallet\Mirakl\Exception\IllegalNotificationOperationException
     */
    public function testOtherNotification()
    {
        $xml = $this->readFile("other.xml");

        $vendor = new Vendor(
            "test@test.com",
            1,
            1,
            1,
            1,
            1,
            "test",
            1,
            'FR'
        );

        $this->vendorManager->findByHiPayId(Argument::any())
            ->willReturn($vendor)
            ->shouldBeCalled();

        $this->hipay->getAccountHiPay(Argument::any())
            ->willReturn(array("callback_salt" => "test"))
            ->shouldBeCalled();

        $this->setEventAssertion("other", "Other");
        $this->notificationHandler->handleHiPayNotification($xml);
    }

    /**
     * @cover ::handleHiPayNotification
     * @throws \HiPay\Wallet\Mirakl\Exception\ChecksumFailedException
     * @throws \HiPay\Wallet\Mirakl\Exception\IllegalNotificationOperationException
     */
    public function testIdentificationNotification()
    {
        $xml = $this->readFile("identification.xml");

        $vendor = new Vendor('test@test', 123456, null, null, false, 1, "test");

        $this->vendorManager->findByHiPayId(123456)->willReturn($vendor)->shouldBeCalledTimes(2);

        $this->hipay->getAccountHiPay(Argument::any())
            ->willReturn(array("callback_salt" => "test"))
            ->shouldBeCalled();

        $this->vendorManager->save(Argument::that(function ($vendorUpdated) {
            // Should have been updated to identified
            return $vendorUpdated->getHipayIdentified() === true;
        }))->shouldBeCalledTimes(1);

        $this->setEventAssertion("identification", "Identification");
        $this->notificationHandler->handleHiPayNotification($xml);
    }

    /**
     * @cover ::handleHiPayNotification
     * @throws \HiPay\Wallet\Mirakl\Exception\ChecksumFailedException
     * @throws \HiPay\Wallet\Mirakl\Exception\IllegalNotificationOperationException
     */
    public function testWithdrawSuccessNotification()
    {
        $xml = $this->readFile("withdrawSuccess.xml");

        $vendor = new Vendor(
            "test@test.com",
            1,
            1,
            1,
            1,
            1,
            "test",
            1,
            'FR'
        );

        $this->vendorManager->findByHiPayId(Argument::any())
            ->willReturn($vendor)
            ->shouldBeCalled();

        $this->hipay->getAccountHiPay(Argument::any())
            ->willReturn(array("callback_salt" => "test"))
            ->shouldBeCalled();

        $operation = new Operation(2000, new DateTime(), "000001", rand());

        $operation->setStatus(new Status(Status::WITHDRAW_REQUESTED));

        $this->operationManager
            ->findByWithdrawalId(Argument::type("string"))
            ->willReturn($operation)
            ->shouldBeCalled();

        $this->operationManager->save(Argument::is($operation))->shouldBeCalled();

        $this->setEventAssertion(array("withdraw", "success"), "Withdraw");

        $this->notificationHandler->handleHiPayNotification($xml);

        $this->assertEquals(Status::WITHDRAW_SUCCESS, $operation->getStatus());
    }

    /**
     * @cover ::handleHiPayNotification
     */
    public function testTransferSuccessAuthorize()
    {
        $xml = $this->readFile("authorizeSuccess.xml");

        $vendor = new Vendor(
            "test@test.com",
            1,
            1,
            1,
            1,
            1,
            "test",
            1,
            'FR'
        );

        $this->vendorManager->findByHiPayId(Argument::any())
            ->willReturn($vendor)
            ->shouldBeCalled();

        $this->hipay->getAccountHiPay(Argument::any())
            ->willReturn(array("callback_salt" => "test"))
            ->shouldBeCalled();

        $operation = new Operation(2000, new DateTime(), "000001", rand());

        $operation->setStatus(new Status(Status::TRANSFER_REQUESTED));

        $this->operationManager
            ->findOneByTransferId(Argument::type("string"))
            ->willReturn($operation)
            ->shouldBeCalled();

        $this->operationManager->save(Argument::is($operation))->shouldNotBeCalled();

        $this->notificationHandler->handleHiPayNotification($xml);

        $this->assertEquals(Status::TRANSFER_REQUESTED, $operation->getStatus());
    }

    /**
     * @cover ::handleHiPayNotification
     */
    public function testTransferFailureAuthorize()
    {
        $xml = $this->readFile("authorizeFailure.xml");

        $vendor = new Vendor(
            "test@test.com",
            1,
            1,
            1,
            1,
            1,
            "test",
            1,
            'FR'
        );

        $this->vendorManager->findByHiPayId(Argument::any())
            ->willReturn($vendor)
            ->shouldBeCalled();

        $this->hipay->getAccountHiPay(Argument::any())
            ->willReturn(array("callback_salt" => "test"))
            ->shouldBeCalled();

        $operation = new Operation(2000, new DateTime(), "000001", rand());

        $operation->setStatus(new Status(Status::TRANSFER_REQUESTED));

        $this->operationManager
            ->findOneByTransferId(Argument::type("string"))
            ->willReturn($operation)
            ->shouldBeCalled();

        $this->operationManager->save(Argument::is($operation))->shouldBeCalled();

        $this->notificationHandler->handleHiPayNotification($xml);

        $this->assertEquals(Status::TRANSFER_FAILED, $operation->getStatus());
    }

    /**
     * @cover ::handleHiPayNotification
     */
    public function testTransferCaptureSuccess()
    {
        $xml = $this->readFile("captureSuccess.xml");

        $vendor = new Vendor(
            "test@test.com",
            1,
            1,
            1,
            1,
            1,
            "test",
            1,
            'FR'
        );

        $this->vendorManager->findByHiPayId(Argument::any())
            ->willReturn($vendor)
            ->shouldBeCalled();

        $this->hipay->getAccountHiPay(Argument::any())
            ->willReturn(array("callback_salt" => "test"))
            ->shouldBeCalled();

        $operation = new Operation(2000, new DateTime(), "000001", rand());

        $operation->setStatus(new Status(Status::TRANSFER_REQUESTED));

        $this->operationManager
            ->findOneByTransferId(Argument::type("string"))
            ->willReturn($operation)
            ->shouldBeCalled();

        $this->operationManager->save(Argument::is($operation))->shouldBeCalled();

        $this->notificationHandler->handleHiPayNotification($xml);

        $this->assertEquals(Status::TRANSFER_SUCCESS, $operation->getStatus());
    }

    /**
     * @cover ::handleHiPayNotification
     */
    public function testTransferCaptureFailure()
    {
        $xml = $this->readFile("captureFailure.xml");

        $vendor = new Vendor(
            "test@test.com",
            1,
            1,
            1,
            1,
            1,
            "test",
            1,
            'FR'
        );

        $this->vendorManager->findByHiPayId(Argument::any())
            ->willReturn($vendor)
            ->shouldBeCalled();

        $this->hipay->getAccountHiPay(Argument::any())
            ->willReturn(array("callback_salt" => "test"))
            ->shouldBeCalled();

        $operation = new Operation(2000, new DateTime(), "000001", rand());

        $operation->setStatus(new Status(Status::TRANSFER_REQUESTED));

        $this->operationManager
            ->findOneByTransferId(Argument::type("string"))
            ->willReturn($operation)
            ->shouldBeCalled();

        $this->operationManager->save(Argument::is($operation))->shouldBeCalled();

        $this->notificationHandler->handleHiPayNotification($xml);

        $this->assertEquals(Status::TRANSFER_FAILED, $operation->getStatus());
    }

    /**
     * @cover ::handleHiPayNotification
     * @throws \HiPay\Wallet\Mirakl\Exception\ChecksumFailedException
     * @throws \HiPay\Wallet\Mirakl\Exception\IllegalNotificationOperationException
     */
    public function testWithdrawCancelNotification()
    {
        $xml = $this->readFile("withdrawCanceled.xml");

        $vendor = new Vendor(
            "test@test.com",
            1,
            1,
            1,
            1,
            1,
            "test",
            1,
            'FR'
        );

        $this->vendorManager->findByHiPayId(Argument::any())
            ->willReturn($vendor)
            ->shouldBeCalled();

        $this->hipay->getAccountHiPay(Argument::any())
            ->willReturn(array("callback_salt" => "test"))
            ->shouldBeCalled();

        $operation = new Operation(2000, new DateTime(), "000001", rand());

        $operation->setStatus(new Status(Status::WITHDRAW_REQUESTED));

        $this->operationManager
            ->findByWithdrawalId(Argument::type("string"))
            ->willReturn($operation)
            ->shouldBeCalled();

        $this->operationManager->save(Argument::is($operation))->shouldBeCalled();

        $this->setEventAssertion(array("withdraw", "canceled"), "Withdraw");

        $this->notificationHandler->handleHiPayNotification($xml);

        $this->assertEquals(Status::WITHDRAW_CANCELED, $operation->getStatus());
    }

    /**
     * @cover ::handleHiPayNotification
     * @throws \HiPay\Wallet\Mirakl\Exception\ChecksumFailedException
     * @throws \HiPay\Wallet\Mirakl\Exception\IllegalNotificationOperationException
     */
    public function testUnknownOperation()
    {
        $xml = $this->readFile("unknownOperation.xml");

        $vendor = new Vendor(
            "test@test.com",
            1,
            1,
            1,
            1,
            1,
            "test",
            1,
            'FR'
        );

        $this->vendorManager->findByHiPayId(Argument::any())
            ->willReturn($vendor)
            ->shouldBeCalled();

        $this->hipay->getAccountHiPay(Argument::any())
            ->willReturn(array("callback_salt" => "test"))
            ->shouldBeCalled();

        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $this->notificationHandler->handleHiPayNotification($xml);
    }

    /**
     * @cover ::handleHiPayNotification
     * @throws \HiPay\Wallet\Mirakl\Exception\ChecksumFailedException
     * @throws \HiPay\Wallet\Mirakl\Exception\IllegalNotificationOperationException
     */
    public function testDocumentValidation()
    {
        $xml = $this->readFile("documentValidation.xml");

        $vendor = new Vendor(
            "test@test.com",
            1,
            1,
            1,
            1,
            1,
            "test",
            1,
            'FR'
        );

        $this->vendorManager->findByHiPayId(Argument::any())
            ->willReturn($vendor)
            ->shouldBeCalled();

        $this->hipay->getAccountHiPay(Argument::any())
            ->willReturn(array("callback_salt" => "test"))
            ->shouldBeCalled();

        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $this->notificationHandler->handleHiPayNotification($xml);
    }

    /**
     * Read a test file
     *
     * @param $file
     * @return string
     */
    protected function readFile($file)
    {
        $path = $this->testFilesPath . $file;
        $xml = file_get_contents($path);
        return $xml;
    }

    /**
     * Add an assertion about the event dispatcher
     *
     * @param $eventNameParts
     * @param $eventClass
     */
    protected function setEventAssertion($eventNameParts, $eventClass)
    {
        $eventNameParts = (array)$eventNameParts;

        $eventNameArgument = Argument::that(function ($argument) use ($eventNameParts) {
            $part = reset($eventNameParts);
            do {
                if (strpos($argument, $part) === false) {
                    return false;
                }
            } while (false !== next($eventNameParts));
            return true;
        });

        $notificationEventClassPath = "\\HiPay\\Wallet\\Mirakl\\Notification\\Event\\";
        $eventTypeArgument = Argument::type($notificationEventClassPath . $eventClass);
        $this->eventDispatcher->dispatch($eventNameArgument, $eventTypeArgument)->shouldBeCalled();
    }
}
