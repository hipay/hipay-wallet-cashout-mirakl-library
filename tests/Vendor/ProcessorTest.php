<?php

namespace HiPay\Wallet\Mirakl\Test\Vendor;

use DateTime;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\ClientErrorResponseException;
use HiPay\Wallet\Mirakl\Api\HiPay;
//use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\BankInfo;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Rest\BankInfo;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\BankInfo as BankInfoStatus;
use HiPay\Wallet\Mirakl\Test\Common\AbstractProcessorTest;
use HiPay\Wallet\Mirakl\Test\Stub\Api\Mirakl;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\Document;
use HiPay\Wallet\Mirakl\Test\Stub\Entity\Vendor;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use HiPay\Wallet\Mirakl\Vendor\Processor;
use phpmock\prophecy\PHPProphet;
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
        $this->bankInfoArgument = Argument::type("\\HiPay\\Wallet\\Mirakl\\Api\\HiPay\\Model\\Rest\\BankInfo");

        $this->vendorProcessor = new Processor(
            $this->eventDispatcher->reveal(),
            $this->logger->reveal(),
            $this->apiFactory->reveal(),
            $this->vendorManager->reveal(),
            $this->documentManager->reveal(),
            $this->logVendorManager->reveal()
        );
    }

    /**
     * @covers ::getVendors
     */
    public function testGetVendors()
    {
        $this->mirakl->getVendors(Argument::is(null), Argument::any(), Argument::any())->will(function () {
            return Mirakl::getVendors();
        })->shouldBeCalled();

        $vendors = $this->vendorProcessor->getVendors();

        $this->assertInternalType('array', $vendors);

        $this->assertEquals(2, count($vendors));

    }

    /**
     * @covers ::getVendors
     */
    public function testGetVendorWithDate()
    {
        $this->mirakl->getVendors(Argument::type('DateTime'), Argument::any(), Argument::any())->will(function ($args) {
            return Mirakl::getVendors($args[0]);
        })->shouldBeCalled();

        $lastUpdate = new DateTime("2016-10-06T00:00:00Z");

        $vendors = $this->vendorProcessor->getVendors($lastUpdate);

        $this->assertInternalType('array', $vendors);

        $this->assertEquals(1, count($vendors));
    }

    /**
     * @cover ::registerWallets
     */
    public function testNewWallets()
    {
        $this->hipay->isAvailable(Argument::containingString('@'), Argument::is(false))->willReturn(true);

        $walletInfo = new HiPay\Wallet\AccountInfo(mt_rand(), mt_rand(), true, mt_rand());

        $this->hipay->createFullUseraccountV2(
            Argument::type("\\HiPay\\Wallet\\Mirakl\\Api\\HiPay\\Model\\Rest\\UserAccount")
        )->willReturn($walletInfo)->shouldBeCalled();

        $this->vendorManager->findByMiraklId(Argument::any())->willReturn()->shouldBeCalled();

        $this->vendorManager->create(
            $this->emailArgument,
            Argument::type('integer'),
            $walletInfo->getUserAccountld(),
            $walletInfo->getUserSpaceld(),
            $walletInfo->getIdentified(),
            Argument::any(),
            Argument::any(),
            Argument::type('array')
        )

            ->will(function ($args) {
                return new Vendor($args[0], rand(), $args[2], $args[3]);
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
    public function testAlreadyRecordedWalletsNewEmail()
    {
        $this->vendorManager->findByMiraklId(2001)->will(function ($email) {
            return new Vendor("foo@bar.com", rand(), rand());
        })->shouldBeCalled();

        $this->vendorManager
            ->update($this->vendorArgument, Argument::type('array'))
            ->willReturn()
            ->shouldBeCalled();

        $this->vendorManager->isValid($this->vendorArgument)->willReturn(true)->shouldBeCalled();

        $walletInfo = new HiPay\Wallet\AccountInfo(mt_rand(), mt_rand(), true, mt_rand());

        $this->hipay->getWalletInfo(
            Argument::type("\\HiPay\\Wallet\\Mirakl\\Api\\HiPay\\Model\\Rest\\UserAccount"),
            Argument::type("HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorInterface")
        )->willReturn($walletInfo)->shouldBeCalled();

        $this->hipay->updateEmail(
            "test+1@test.com",
            Argument::type("HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorInterface")
        )->willReturn(true)->shouldBeCalled();


        $vendors = $this->vendorProcessor->registerWallets(Mirakl::getVendor());

        $this->assertInternalType('array', $vendors);

        $this->assertEquals(1, count($vendors));

        $this->assertContainsOnlyInstancesOf("HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorInterface", $vendors);
    }

    /**
     * @cover ::registerWallets
     */
    public function testAlreadyRecordedWalletsSameEmail()
    {
        $this->vendorManager->findByMiraklId(2001)->will(function ($email) {
            return new Vendor("test+1@test.com", rand(), rand());
        })->shouldBeCalled();

        $this->vendorManager
            ->update($this->vendorArgument, Argument::type('array'))
            ->willReturn()
            ->shouldBeCalled();

        $this->vendorManager->isValid($this->vendorArgument)->willReturn(true)->shouldBeCalled();

        $walletInfo = new HiPay\Wallet\AccountInfo(mt_rand(), mt_rand(), true, mt_rand());

        $this->hipay->getWalletInfo(
            Argument::type("\\HiPay\\Wallet\\Mirakl\\Api\\HiPay\\Model\\Rest\\UserAccount"),
            Argument::type("HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorInterface")
        )->willReturn($walletInfo)->shouldBeCalled();

        $this->hipay->updateEmail(
            "test+1@test.com",
            Argument::type("HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorInterface")
        )->shouldNotBeCalled();


        $vendors = $this->vendorProcessor->registerWallets(Mirakl::getVendor());

        $this->assertInternalType('array', $vendors);

        $this->assertEquals(1, count($vendors));

        $this->assertContainsOnlyInstancesOf("HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorInterface", $vendors);
    }

    /**
     * @cover ::registerWallets
     */
    public function testAlreadyRecordedWalletsUpdateException()
    {
        $this->vendorManager->findByMiraklId(2001)->will(function ($email) {
            return new Vendor("foo@bar.com", rand(), rand());
        })->shouldBeCalled();

        $this->vendorManager
            ->update($this->vendorArgument, Argument::type('array'))
            ->willReturn()
            ->shouldNotBeCalled();

        $walletInfo = new HiPay\Wallet\AccountInfo(mt_rand(), mt_rand(), true, mt_rand());

        $this->hipay->getWalletInfo(
            Argument::type("\\HiPay\\Wallet\\Mirakl\\Api\\HiPay\\Model\\Rest\\UserAccount"),
            Argument::type("HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorInterface")
        )->willReturn($walletInfo)->shouldBeCalled();

        $this->hipay->updateEmail(
            "test+1@test.com",
            Argument::type("HiPay\\Wallet\\Mirakl\\Vendor\\Model\\VendorInterface")
        )->willThrow(new \Exception())
            ->shouldBeCalled();


        $vendors = $this->vendorProcessor->registerWallets(Mirakl::getVendor());

        $this->assertInternalType('array', $vendors);

        $this->assertEquals(0, count($vendors));
    }

    /**
     * @covers ::handleBankInfo
     */
    public function testBankInfoBlank()
    {
        $tmpDir = '/tmp/dir';
        $docContent1 = 'data1';
        $vendors = Mirakl::getVendor();
        $miraklData = reset($vendors);
        $vendor = $this->getVendorInstance($miraklData);
        $miraklData = array($vendor->getMiraklId() => $miraklData);

        /** @var VendorInterface $vendorArgument */
        $vendorArgument = Argument::is($vendor);

        $this->mirakl->getFiles(array($vendor->getMiraklId()))->willReturn(Mirakl::getShopDocuments(array($vendor->getMiraklId())))->shouldBeCalled();

        $this->hipay->bankInfosStatus(
                $vendorArgument
            )->willReturn(BankInfoStatus::BLANK)->shouldBeCalled();

        $this->hipay
            ->bankInfosRegister($vendorArgument, $this->bankInfoArgument)
            ->willReturn(true)
            ->shouldBeCalled();

        $this->vendorProcessor->handleBankInfo(array($vendor), $miraklData, $tmpDir);
    }

    /**
     * @cover ::handleBankInfo
     */
    public function testBankInfoValidatedAndNotSynchronized()
    {
        $tmpDir = '/tmp/dir';
        $vendors = Mirakl::getVendor();
        $miraklData = reset($vendors);
        $vendor = $this->getVendorInstance($miraklData);
        $miraklData = array($vendor->getMiraklId() => $miraklData);

        /** @var VendorInterface $vendorArgument */
        $vendorArgument = Argument::is($vendor);

        $this->mirakl->getFiles(
            array($vendor->getMiraklId()))->willReturn(Mirakl::getShopDocuments(array($vendor->getMiraklId()))
        )->shouldBeCalled();

        $this->hipay->bankInfosStatus($this->vendorArgument)
                    ->willReturn(BankInfoStatus::VALIDATED)
                    ->shouldBeCalled();

        $this->hipay->bankInfosCheck(Argument::is($vendor))->will(function () use ($miraklData, $vendor) {
            $bankInfo = new BankInfo();
            return $bankInfo->setMiraklData(array("payment_info" => array("iban"=> "test")));
        })->shouldBeCalled();

        $this->hipay
            ->bankInfosRegister($vendorArgument, $this->bankInfoArgument)
            ->willReturn(true)
            ->shouldBeCalled();

        $this->vendorProcessor->handleBankInfo(array($vendor), $miraklData, $tmpDir);
    }

    /**
     * @cover ::handleBankInfo
     */
    public function testBankInfoValidatedAndSynchronized()
    {
        $tmpDir = '/tmp/dir';
        $vendors = Mirakl::getVendor();
        $miraklData = reset($vendors);
        $vendor = $this->getVendorInstance($miraklData);
        $miraklData = array($vendor->getMiraklId() => $miraklData);

        /** @var VendorInterface $vendorArgument */
        $vendorArgument = Argument::is($vendor);

        $this->mirakl->getFiles(
            array($vendor->getMiraklId()))->willReturn(Mirakl::getShopDocuments(array($vendor->getMiraklId()))
        )->shouldBeCalled();

        $this->hipay->bankInfosStatus($this->vendorArgument)
                    ->willReturn(BankInfoStatus::VALIDATED)
                    ->shouldBeCalled();

        $this->hipay->bankInfosCheck(Argument::is($vendor))->will(function () use ($miraklData, $vendor) {
            $bankInfo = new BankInfo();
            return $bankInfo->setMiraklData($miraklData[$vendor->getMiraklId()]);
        })->shouldBeCalled();

        $this->hipay
            ->bankInfosRegister($vendorArgument, $this->bankInfoArgument)
            ->willReturn(true)
            ->shouldNotBeCalled();

        $this->vendorProcessor->handleBankInfo(array($vendor), $miraklData, $tmpDir);
    }

    /**
     * @covers ::transferFiles
     */
    public function testTransferFiles()
    {
        $shops = array(119200, 1321);
        $tmpDir = '/tmp/dir';
        $docContent1 = 'data1';
        $docContent2 = 'data2';
        $docContent3 = 'data3';
        $docContent4 = 'data4';
        $vendor1 = new Vendor('test@ex1.com', 120001, mt_rand(), 771);
        $vendor2 = new Vendor('test@ex2.com', 120002, mt_rand(), 772);

        $document1 = new Document(2006);
        $document2 = new Document(3011);

        // Getting documents list
        $this->mirakl->getFiles($shops)->willReturn(Mirakl::getShopDocuments($shops))->shouldBeCalled();

        // Retrieving vendors
        $this->vendorManager->findByMiraklId(119200)->willReturn($vendor1)->shouldBeCalledTimes(1);
        $this->vendorManager->findByMiraklId(1321)->willReturn($vendor2)->shouldBeCalledTimes(1);

        // Checking documents
        $this->documentManager->findByVendor($vendor1)->willReturn(array(
                new Document(2008, "LEGAL_IDENTITY_OF_REPRESENTATIVE")
            ))->shouldBeCalledTimes(1);

        $this->documentManager->findByVendor($vendor2)->willReturn(array(
                new Document(3006, "ALL_PROOF_OF_BANK_ACCOUNT")
            ))->shouldBeCalledTimes(1);

        // Download missing documents
        $this->mirakl->downloadDocuments(array(2006), Argument::any())->willReturn($docContent1)->shouldBeCalledTimes(0);
        $this->mirakl->downloadDocuments(array(3008), Argument::any())->willReturn($docContent2)->shouldBeCalledTimes(1);
        $this->mirakl->downloadDocuments(array(30082), Argument::any())->willReturn($docContent2)->shouldBeCalledTimes(1);
        $this->mirakl->downloadDocuments(array(3011), Argument::any())->willReturn($docContent3)->shouldBeCalledTimes(1);

        // Save files on disk
        $prophet = new PHPProphet();

        $prophecy = $prophet->prophesize('HiPay\Wallet\Mirakl\Vendor');
        $prophecy->file_put_contents(Argument::containingString('/tmp/dir/'), Argument::containingString('data'))->willReturn(true)->shouldBeCalledTimes(3);
        $prophecy->reveal();

        // Sending documents to HiPay Wallet
        $this->hipay->uploadDocument(771, 120001, HiPay::DOCUMENT_ALL_PROOF_OF_BANK_ACCOUNT, Argument::any(), Argument::any(), null)->shouldBeCalledTimes(0);
        $this->hipay->uploadDocument(772, 120002, HiPay::DOCUMENT_LEGAL_IDENTITY_OF_REPRESENTATIVE, Argument::any(), Argument::any(), Argument::any())->willThrow(new ClientErrorResponseException())->shouldBeCalledTimes(1);
        $this->hipay->uploadDocument(772, 120002, HiPay::DOCUMENT_LEGAL_PROOF_OF_REGISTRATION_NUMBER, Argument::any(), Argument::any(), null)->shouldBeCalledTimes(1);

        // Save document in DB
        $this->documentManager->create(2006, Argument::type("DateTime"), "ALL_PROOF_OF_BANK_ACCOUNT", $vendor1)->willReturn($document1)->shouldBeCalledTimes(0);
        $this->documentManager->create(3011, Argument::type("DateTime"), "LEGAL_PROOF_OF_REGISTRATION_NUMBER", $vendor2)->willReturn($document2)->shouldBeCalledTimes(1);

        $this->documentManager->save(Argument::exact($document1))->shouldBeCalledTimes(0);
        $this->documentManager->save(Argument::exact($document2))->shouldBeCalledTimes(1);

        $this->vendorProcessor->transferFiles($shops, $tmpDir);

        $prophet->checkPredictions();
    }

    /**
     *
     * @covers ::transferFiles
     */
    public function testWrongExtensionTransferFiles(){
        $shops = array(777);
        $tmpDir = '/tmp/dir';
        $vendor = new Vendor('test@ex1.com', 120001, mt_rand(), 771);

        // Getting documents list
        $this->mirakl->getFiles($shops)->willReturn(Mirakl::getShopDocuments($shops))->shouldBeCalled();

        $this->vendorManager->findByMiraklId(777)->willReturn($vendor)->shouldBeCalledTimes(1);

        $this->documentManager->findByVendor($vendor)->willReturn(array())->shouldBeCalledTimes(1);

        $this->mirakl->downloadDocuments(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->hipay->uploadDocument(Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->vendorProcessor->transferFiles($shops, $tmpDir);
    }

    /**
     * Return the correct vendor instance from mirakl data
     *
     * @param $miraklData
     * @return Vendor
     */
    private function getVendorInstance($miraklData)
    {
        return new Vendor($miraklData['contact_informations']['email'], rand(), $miraklData['shop_id'], $miraklData['pro_details']['VAT_number']);
    }
}
