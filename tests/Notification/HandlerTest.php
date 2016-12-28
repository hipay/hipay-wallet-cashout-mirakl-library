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
            $this->vendorManager->reveal()
        );

    }

    /**
     * @cover ::handleHiPayNotification
     * @throws \HiPay\Wallet\Mirakl\Exception\ChecksumFailedException
     * @throws \HiPay\Wallet\Mirakl\Exception\IllegalNotificationOperationException
     */
    public function testMd5Failure()
    {
        $xml = $this->readFile("md5Fail.xml");

        $this->setExpectedException("\\HiPay\\Wallet\\Mirakl\\Exception\\ChecksumFailedException");

        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

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

        $vendor = new Vendor('test@test', 123456, null, null, false);

        $this->vendorManager->findByHiPayId(123456)->willReturn($vendor)->shouldBeCalledTimes(1);
        $this->vendorManager->save(Argument::that(function($vendorUpdated) {
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

        $operation = new Operation(2000, new DateTime(), "000001", rand());
        $operation->setStatus(new Status(Status::WITHDRAW_REQUESTED));

        $this->operationManager->findByWithdrawalId(Argument::type("string"))->willReturn($operation)->shouldBeCalled();

        $this->operationManager->save(Argument::is($operation))->shouldBeCalled();

        $this->setEventAssertion(array("withdraw", "success"), "Withdraw");

        $this->notificationHandler->handleHiPayNotification($xml);

        $this->assertEquals(Status::WITHDRAW_SUCCESS, $operation->getStatus());
    }

    /**
     * @cover ::handleHiPayNotification
     * @throws \HiPay\Wallet\Mirakl\Exception\ChecksumFailedException
     * @throws \HiPay\Wallet\Mirakl\Exception\IllegalNotificationOperationException
     */
    public function testWithdrawCancelNotification()
    {
        $xml = $this->readFile("withdrawCanceled.xml");

        $operation = new Operation(2000, new DateTime(), "000001", rand());
        $operation->setStatus(new Status(Status::WITHDRAW_REQUESTED));

        $this->operationManager->findByWithdrawalId(Argument::type("string"))->willReturn($operation)->shouldBeCalled();

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

        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $this->setExpectedException("HiPay\\Wallet\\Mirakl\\Exception\\IllegalNotificationOperationException");
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

        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $parameters = array(
            'mail.host' => 'smtp',
            'mail.port' => '1025',
            'mail.security' => null,
            'mail.username' => null,
            'mail.password' => null,
            'mail.subject'=> 'Mirakl HiPay Connector Notification',
            'mail.to' => 'marketplace.operator@hipay.com',
            'mail.from' => 'mirakl.hipay.connector@hipay.com',
        );

        $this->setExpectedException("HiPay\\Wallet\\Mirakl\\Exception\\IllegalNotificationOperationException");
        $this->notificationHandler->handleHiPayNotification($xml, $parameters);
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
        $eventNameParts = (array) $eventNameParts;
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
        $this->eventDispatcher->dispatch(
            $eventNameArgument,
            $eventTypeArgument
        )
            ->shouldBeCalled();
    }
}
