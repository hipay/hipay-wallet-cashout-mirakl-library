<?php

namespace HiPay\Wallet\Mirakl\Test\Common;

use HiPay\Wallet\Mirakl\Api\Factory;
use HiPay\Wallet\Mirakl\Api\HiPay\ApiInterface as HiPayApiInterface;
use HiPay\Wallet\Mirakl\Api\Mirakl\ApiInterface as MiraklApiInterface;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\ManagerInterface as OperationManagerInterface;
use HiPay\Wallet\Mirakl\Cashout\Model\Transaction\ValidatorInterface;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\Vendor;
use HiPay\Wallet\Mirakl\Vendor\Model\DocumentManagerInterface as DocumentManagerInterface;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorManagerInterface as VendorManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
abstract class AbstractProcessorTest extends TestCase
{
    /** @var  EventDispatcherInterface|ObjectProphecy */
    protected $eventDispatcher;

    /** @var  LoggerInterface|ObjectProphecy */
    protected $logger;

    /** @var  MiraklApiInterface|ObjectProphecy */
    protected $mirakl;

    /** @var  HiPayApiInterface|ObjectProphecy */
    protected $hipay;

    /** @var  Factory|ObjectProphecy */
    protected $apiFactory;

    /** @var VendorManagerInterface|ObjectProphecy  */
    protected $vendorManager;

    /** @var DocumentManagerInterface|ObjectProphecy  */
    protected $documentManager;

    /** @var LogVendorsManagerInterface|ObjectProphecy  */
    protected $logVendorManager;

    /** @var LogOperationssManagerInterface|ObjectProphecy  */
    protected $logOperationsManager;

    /** @var OperationManagerInterface|ObjectProphecy  */
    protected $operationManager;

    /** @var  ValidatorInterface|ObjectProphecy */
    protected $transactionValidator;

    /** @var  Vendor */
    protected $operator;

    /** @var  Vendor */
    protected $technical;

    public function setUp()
    {
        parent::setUp();

        /** @var LoggerInterface|ObjectProphecy $logger */
        $this->logger = $this->prophesize("\\Psr\\Log\\LoggerInterface");

        /** @var EventDispatcher|ObjectProphecy $eventDispatcher */
        $this->eventDispatcher = $this->prophesize("\\Symfony\\Component\\EventDispatcher\\EventDispatcher");

        /** @var MiraklApiInterface|ObjectProphecy mirakl */
        $this->mirakl = $this->prophesize("\\HiPay\\Wallet\\Mirakl\\Api\\Mirakl\\ApiInterface");

        /** @var HiPayApiInterface|ObjectProphecy $hipay */
        $this->hipay = $this->prophesize("\\HiPay\\Wallet\\Mirakl\\Api\\HiPay\\ApiInterface");

        $this->apiFactory = $this->prophesize("\\HiPay\\Wallet\\Mirakl\\Api\\Factory");

        $this->apiFactory->getHiPay()->willReturn($this->hipay->reveal());
        $this->apiFactory->getMirakl()->willReturn($this->mirakl->reveal());

        /** @var VendorManagerInterface|ObjectProphecy $vendorManager */
        $this->vendorManager = $this->prophesize("HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorManagerInterface");

        /** @var DocumentManagerInterface|ObjectProphecy $documentManager */
        $this->documentManager = $this->prophesize("HiPay\\Wallet\\Mirakl\\Vendor\\Model\\DocumentManagerInterface");

        /** @var LogVendorsManagerInterface|ObjectProphecy $logVendorManager */
        $this->logVendorManager = $this->prophesize("HiPay\\Wallet\\Mirakl\\Notification\\Model\\LogVendorsManagerInterface");

        /** @var LogOperationsManagerInterface|ObjectProphecy $logOperationsManager */
        $this->logOperationsManager = $this->prophesize("HiPay\\Wallet\\Mirakl\\Notification\\Model\\LogOperationsManagerInterface");

        /** @var OperationManagerInterface|ObjectProphecy $operationManager */
        $this->operationManager =
            $this->prophesize("\\HiPay\\Wallet\\Mirakl\\Cashout\\Model\\Operation\\ManagerInterface");

        /** @var ValidatorInterface|ObjectProphecy $transactionValidator */
        $this->transactionValidator =
            $this->prophesize("\\HiPay\\Wallet\\Mirakl\\Cashout\\Model\\Transaction\\ValidatorInterface");


        $this->operator = new Vendor("operator@test.com", rand());

        $this->technical = new Vendor("technical@test.com", rand());
    }


    public function tearDown()
    {
        parent::tearDown();
        $this->vendorManager->checkProphecyMethodsPredictions();
        $this->operationManager->checkProphecyMethodsPredictions();
        $this->hipay->checkProphecyMethodsPredictions();
        $this->mirakl->checkProphecyMethodsPredictions();
        $this->documentManager->checkProphecyMethodsPredictions();
        $this->logVendorManager->checkProphecyMethodsPredictions();
        $this->logOperationsManager->checkProphecyMethodsPredictions();
    }
}
