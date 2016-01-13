<?php
/**
 * File Processor.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Vendor;

use Hipay\MiraklConnector\Api\Hipay\Model\BankInfo;
use Hipay\MiraklConnector\Api\Hipay\Model\MerchantData;
use Hipay\MiraklConnector\Api\Hipay\Model\UserAccountBasic;
use Hipay\MiraklConnector\Api\Hipay\Model\UserAccountDetails;
use Hipay\MiraklConnector\Common\AbstractProcessor;
use Hipay\MiraklConnector\Service\Ftp;
use Hipay\MiraklConnector\Service\Ftp\ConfigurationInterface;
use Hipay\MiraklConnector\Service\Zip;
use Hipay\MiraklConnector\Vendor\Event\AddBankAccount;
use Hipay\MiraklConnector\Vendor\Event\CheckAvailability;
use Hipay\MiraklConnector\Vendor\Event\CreateWallet;
use Hipay\MiraklConnector\Api\Mirakl;
use Hipay\MiraklConnector\Api\Hipay;
use Hipay\MiraklConnector\Api\Mirakl\ConfigurationInterface as MiraklConfiguration;
use Hipay\MiraklConnector\Service\Ftp\ConfigurationInterface as FtpConfiguration;
use Hipay\MiraklConnector\Api\Hipay\ConfigurationInterface as HipayConfiguration;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Touki\FTP\FTPFactory;
use Touki\FTP\FTPInterface;
use Touki\FTP\Model\Directory;
use Touki\FTP\Model\File;


/**
 * Class Processor
 * Vendor processor who contains method to handle the vendors
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Processor extends AbstractProcessor
{
    /** @var  FtpInterface */
    protected $ftp;

    /**
     * Processor constructor.
     * @param MiraklConfiguration $miraklConfig
     * @param HipayConfiguration $hipayConfig
     * @param FtpConfiguration $ftpConfiguration
     * @param EventDispatcherInterface $dispatcherInterface
     * @param LoggerInterface $logger
     */
    public function __construct(
        MiraklConfiguration $miraklConfig,
        HipayConfiguration $hipayConfig,
        ConfigurationInterface $ftpConfiguration,
        EventDispatcherInterface $dispatcherInterface = null,
        LoggerInterface $logger = null
    )
    {
        parent::__construct(
            $miraklConfig,
            $hipayConfig,
            $dispatcherInterface,
            $logger
        );

        $connectionFactory = new Ftp\ConnectionFactory($ftpConfiguration);
        $factory = new FTPFactory();
        $this->ftp = $factory->build($connectionFactory->build());
    }

    /**
     * Fetch the vendors from Mirakl
     *
     * @param \DateTime $lastUpdate
     * @return array
     */
    public function getVendors(\DateTime $lastUpdate = null)
    {
        return $this->mirakl->getVendors($lastUpdate);
    }

    /**
     * Check if the vendor already has a wallet
     *
     * Dispatch the event <b>before.availability.check</b> before sending the data to Hipay
     *
     * @param string $email
     * @param bool $entity
     *
     * @return bool
     */
    public function hasWallet($email, $entity = false)
    {
        $event = new CheckAvailability($email, $entity);
        $this->dispatcher->dispatch('before.availability.check', $event);
        $result = $this->hipay->isAvailable(
            $event->getEmail(),
            $event->getEntity()
        );
        return !$result;
    }

    /**
     * Create a Hipay wallet
     *
     * Dispatch the event <b>before.wallet.create</b> before sending the data to Hipay
     *
     * @param array $shopData
     * @return int the created account id
     */
    public function createWallet(
        array $shopData
    )
    {
        $userAccountBasic = new UserAccountBasic($shopData);
        $userAccountDetails = new UserAccountDetails($shopData);
        $merchantData = new MerchantData($shopData);

        $event = new CreateWallet(
            $userAccountBasic,
            $userAccountDetails,
            $merchantData
        );

        $this->dispatcher->dispatch(
            'before.wallet.create',
            $event
        );

        return $this->hipay->createFullUseraccount(
            $event->getUserAccountBasic(),
            $event->getUserAccountDetails(),
            $event->getMerchantData()
        );
    }

    /**
     * Transfer the files from Mirakl to Hipay using ftp
     *
     * @param array $shopIds
     * @param $tmpZipFilePath
     * @param $ftpShopsPath
     * @param null $tmpExtractPath
     */
    public function transferFiles(
        array $shopIds,
        $tmpZipFilePath,
        $ftpShopsPath,
        $tmpExtractPath = null)
    {
        //Downloads the zip file containg the documents
        file_put_contents(
            $tmpZipFilePath,
            $this->mirakl->downloadShopsDocuments($shopIds)
        );

        $zip = new Zip($tmpZipFilePath);

        $tmpExtractPath = $tmpExtractPath ?: dirname($tmpZipFilePath);

        if ($zip->extractFiles($tmpExtractPath)) {
            unlink($tmpZipFilePath);
        };

        $tmpExtractDirectory = opendir($tmpExtractPath);

        while (($shopId = readdir($tmpExtractDirectory)) !== false) {
            //Ignore . and .. entries
            if ($shopId == '.' || $shopId == '..' || !in_array($shopId, $shopIds)) {
                continue;
            }

            $shopDirectoryPath = $tmpExtractPath . DIRECTORY_SEPARATOR . $shopId;

            //Check if $shopDirectoryPath is a directry
            if (!is_dir($shopDirectoryPath)) {
                throw new \RuntimeException(
                    "$shopDirectoryPath should be a directory"
                );
            }

            //Construct the path for the ftp
            $ftpShopDirectoryPath = $ftpShopsPath . DIRECTORY_SEPARATOR . $shopId;

            //Check directory existance
            $ftpShopDirectory = new Directory($ftpShopDirectoryPath);
            if (!$this->ftp->directoryExists($ftpShopDirectory)) {
                //Create the ftp directory for the shop
                $this->ftp->create($ftpShopDirectory);
            };

            $shopDirectory = opendir($shopDirectoryPath);
            while (($shopDocument = readdir($shopDirectory)) !== false) {
                if ($shopDocument == '.' | $shopDocument == '..') {
                    continue;
                }
                $source = $shopDirectoryPath . DIRECTORY_SEPARATOR . $shopDocument;
                $destination = $ftpShopDirectoryPath . DIRECTORY_SEPARATOR . $shopDocument;
                //Upload the files
                if ($this->ftp->upload(
                    new File($destination),
                    $source
                ) == false) {
                    throw new \RuntimeException(
                        "The uploading of the document $source has failed."
                    );
                };
            }
        }
    }

    /**
     * Get bank info status from Hipay
     *
     * @param VendorInterface $vendor
     * @return string
     */
    public function getBankInfoStatus(
        VendorInterface $vendor
    )
    {
        $result = $this->hipay->bankInfosStatus($vendor);
        return $result['status'];
    }

    /**
     * Check that the bank information is the same in the two services
     *
     * @param VendorInterface $vendor
     * @param array $shopData
     *
     * @return bool
     */
    public function isIBANCorrect(
        VendorInterface $vendor,
        array $shopData
    )
    {
        $bankInfo = $this->hipay->bankInfosCheck($vendor);
        return $bankInfo->getIban() == $shopData['payment_info']['iban'];
    }

    /**
     * Add bank account information to Hipay
     * Dispatch the event <b>before.bankAccount.add</b>
     *
     * @param VendorInterface $vendor
     * @param array $shopData
     *
     * @return bool
     */
    public function addBankAccount(VendorInterface $vendor, array $shopData)
    {
        $bankInfo = new BankInfo($shopData);

        $event = new AddBankAccount($bankInfo);

        $this->dispatcher->dispatch(
            'before.bankAccount.add',
            $event
        );

        return $this->hipay->bankInfoRegister($vendor, $event->getBankInfo());
    }
}