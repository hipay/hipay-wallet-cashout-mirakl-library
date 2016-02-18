<?php

namespace HiPay\Wallet\Mirakl\Test\Vendor;

use DateTime;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\BankInfo;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\BankInfo as BankInfoStatus;
use HiPay\Wallet\Mirakl\Service\Ftp\Factory as FTPFactory;
use HiPay\Wallet\Mirakl\Test\Common\AbstractProcessorTest;
use HiPay\Wallet\Mirakl\Test\Stub\Api\Mirakl;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\Vendor;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use HiPay\Wallet\Mirakl\Vendor\Processor;
use Prophecy\Argument;

/**
 * VendorProcessor test
 *
 * @coversDefaultClass \HiPay\Wallet\Mirakl\Vendor\Processor
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class ProcessorTest extends AbstractProcessorTest
{
    /** @var  Processor */
    protected $vendorProcessor;

    /** @var string emailArgument */
    private $emailArgument;

    /** @var VendorInterface vendorArgument */
    private $vendorArgument;

    /** @var BankInfo bankInfoArgument */
    private $bankInfoArgument;

    public function setUp()
    {
        parent::setUp();

        /** @var string emailArgument */
        $this->emailArgument = Argument::containingString('@');

        /** @var VendorInterface vendorArgument */
        $this->vendorArgument = Argument::type("\\HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorInterface");

        /** @var BankInfo bankInfoArgument */
        $this->bankInfoArgument = Argument::type("\\HiPay\\Wallet\\Mirakl\\Api\\HiPay\\Model\\Soap\\BankInfo");

        /** @var FTPFactory $factory */
        $factory = $this->prophesize("\\HiPay\\Wallet\\Mirakl\\Service\\Ftp\\Factory");

        $ftp = $this->prophesize("\\Touki\\\FTP\\FTP");

        $factory->getFTP()->willReturn($ftp->reveal());

        $this->vendorProcessor = new Processor(
            $this->eventDispatcher->reveal(),
            $this->logger->reveal(),
            $this->apiFactory->reveal(),
            $factory->reveal(),
            $this->vendorManager->reveal()
        );
    }

    /**
     * @covers ::getVendors
     */
    public function testGetVendors()
    {
        $this->mirakl->getVendors(Argument::is(null))->will(function () {
            return Mirakl::getVendors();
        })->shouldBeCalled();

        $vendors = $this->vendorProcessor->getVendors();

        $this->assertInternalType('array', $vendors);

        $this->assertEquals(6, count($vendors));

    }

    /**
     * @covers ::getVendors
     */
    public function testGetVendorWithDate()
    {
        $this->mirakl->getVendors(Argument::type('DateTime'))->will(function ($args) {
            return Mirakl::getVendors($args[0]);
        })->shouldBeCalled();

        $lastUpdate = new DateTime("2016-10-06T00:00:00Z");

        $vendors = $this->vendorProcessor->getVendors($lastUpdate);

        $this->assertInternalType('array', $vendors);

        $this->assertEquals(3, count($vendors));
    }

    /**
     * @cover ::registerWallets
     */
    public function testUnavailableEmail()
    {
        $this->hipay->isAvailable($this->emailArgument, Argument::any())->willReturn(false);

        $this->hipay->getWalletId($this->emailArgument)->willReturn(rand())->shouldBeCalled();

        $this->vendorManager->findByEmail($this->emailArgument)->willReturn()->shouldBeCalled();

        $this->vendorManager->update(
            $this->vendorArgument,
            Argument::type('array')
        )->willReturn()->shouldBeCalled();

        $this->vendorManager->create(
            $this->emailArgument,
            Argument::type('integer'),
            Argument::type('integer'),
            Argument::type('array')
        )
            ->will(function ($args) {
                return new Vendor($args[0], rand(), $args[2]);
            })
            ->shouldBeCalled();

        $this->vendorManager->isValid(
            $this->vendorArgument
        )->willReturn(true)->shouldBeCalled();

        $vendors = $this->vendorProcessor->registerWallets(Mirakl::getVendor());

        $this->assertInternalType('array', $vendors);

        $this->assertEquals(1, count($vendors));

        $this->assertContainsOnlyInstancesOf("HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorInterface", $vendors);
    }

    /**
     * @cover ::registerWallets
     */
    public function testNewWallets()
    {
        $this->hipay->isAvailable(Argument::containingString('@'), Argument::is(false))->willReturn(true);

        $this->hipay->createFullUseraccount(
            Argument::type("\\HiPay\\Wallet\\Mirakl\\Api\\HiPay\\Model\\Soap\\UserAccountBasic"),
            Argument::type("\\HiPay\\Wallet\\Mirakl\\Api\\HiPay\\Model\\Soap\\UserAccountDetails"),
            Argument::type("\\HiPay\\Wallet\\Mirakl\\Api\\HiPay\\Model\\Soap\\MerchantData")
        )->willReturn(rand())->shouldBeCalled();

        $this->vendorManager->findByEmail(Argument::containingString('@'))->willReturn()->shouldBeCalled();

        $this->vendorManager->create(
            $this->emailArgument,
            Argument::type('integer'),
            Argument::type('integer'),
            Argument::type('array')
        )
            ->will(function ($args) {
                return new Vendor($args[0], rand(), $args[2]);
            })
            ->shouldBeCalled();

        $this->vendorManager->isValid(
            $this->vendorArgument
        )->willReturn(true)->shouldBeCalled();

        $this->vendorManager->update(
            $this->vendorArgument,
            Argument::type('array')
        )->willReturn()->shouldBeCalled();

        $vendors = $this->vendorProcessor->registerWallets(Mirakl::getVendor());

        $this->assertInternalType('array', $vendors);

        $this->assertEquals(1, count($vendors));

        $this->assertContainsOnlyInstancesOf("HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorInterface", $vendors);
    }

    /**
     * @cover ::registerWallets
     */
    public function testAlreadyRecordedWallets()
    {
        $this->vendorManager->findByEmail($this->emailArgument)->will(function ($email) {
            return new Vendor(reset($email), rand(), rand());
        })->shouldBeCalled();

        $this->vendorManager->update(
            $this->vendorArgument,
            Argument::type('array')
        )->willReturn()->shouldBeCalled();

        $this->vendorManager->isValid(
            $this->vendorArgument
        )->willReturn(true)->shouldBeCalled();

        $vendors = $this->vendorProcessor->registerWallets(Mirakl::getVendor());

        $this->assertInternalType('array', $vendors);

        $this->assertEquals(1, count($vendors));

        $this->assertContainsOnlyInstancesOf("HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorInterface", $vendors);
    }

    /**
     * @covers ::handleBankInfo
     */
    public function testBankInfoBlank()
    {
        $vendors = Mirakl::getVendor();
        $miraklData = reset($vendors);
        $vendor = $this->getVendorInstance($miraklData);
        $miraklData = array($vendor->getMiraklId() => $miraklData);

        /** @var VendorInterface $vendorArgument */
        $vendorArgument = Argument::is($vendor);

        $this->hipay->bankInfosStatus($vendorArgument)->willReturn(BankInfoStatus::BLANK)->shouldBeCalled();

        $this->hipay
            ->bankInfosRegister($vendorArgument, $this->bankInfoArgument)
            ->willReturn(true)
            ->shouldBeCalled();

        $this->hipay->bankInfosRegister($vendorArgument, $this->bankInfoArgument)->willReturn(true)->shouldBeCalled();

        $this->vendorProcessor->handleBankInfo(array($vendor), $miraklData);
    }

    /**
     * @cover ::handleBankInfo
     */
    public function testBankInfoValidate()
    {
        $vendors = Mirakl::getVendor();
        $miraklData = reset($vendors);
        $vendor = $this->getVendorInstance($miraklData);
        $miraklData = array($vendor->getMiraklId() => $miraklData);

        $this->hipay->bankInfosStatus($this->vendorArgument)
                    ->willReturn(BankInfoStatus::VALIDATED)
                    ->shouldBeCalled();

        $this->hipay->bankInfosCheck(Argument::is($vendor))->will(function () use ($miraklData, $vendor) {
            $bankInfo = new BankInfo();
            return $bankInfo->setMiraklData($miraklData[$vendor->getMiraklId()]);
        })->shouldBeCalled();

        $this->vendorProcessor->handleBankInfo(array($vendor), $miraklData);
    }

    /**
     * @covers ::handleBankInfo
     */
    public function testBankInfoOther()
    {
        $vendors = Mirakl::getVendor();
        $miraklData = reset($vendors);
        $vendor = $this->getVendorInstance($miraklData);
        $miraklData = array($vendor->getMiraklId() => $miraklData);

        $this->hipay->bankInfosStatus($this->vendorArgument)
                    ->willReturn(BankInfoStatus::TO_VALIDATE)
                    ->shouldBeCalled();

        $this->vendorProcessor->handleBankInfo(array($vendor), $miraklData);
    }

    /**
     * Return the correct vendor instance from mirakl data
     *
     * @param $miraklData
     * @return Vendor
     */
    private function getVendorInstance($miraklData)
    {
        return new Vendor($miraklData['contact_informations']['email'], rand(), $miraklData['shop_id']);
    }
}
