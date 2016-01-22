<?php
namespace Hipay\MiraklConnector\Vendor;

use DateTime;
use Hipay\MiraklConnector\Api\Hipay;
use Hipay\MiraklConnector\Api\Hipay\ConfigurationInterface
    as HipayConfiguration;
use Hipay\MiraklConnector\Api\Hipay\Model\Status\BankInfo as BankInfoStatus;
use Hipay\MiraklConnector\Api\Hipay\Model\Soap\BankInfo;
use Hipay\MiraklConnector\Api\Hipay\Model\Soap\MerchantData;
use Hipay\MiraklConnector\Api\Hipay\Model\Soap\UserAccountBasic;
use Hipay\MiraklConnector\Api\Hipay\Model\Soap\UserAccountDetails;
use Hipay\MiraklConnector\Api\Mirakl;
use Hipay\MiraklConnector\Api\Mirakl\ConfigurationInterface
    as MiraklConfiguration;
use Hipay\MiraklConnector\Common\AbstractProcessor;
use Hipay\MiraklConnector\Exception\BankAccountCreationFailedException;
use Hipay\MiraklConnector\Exception\DispatchableException;
use Hipay\MiraklConnector\Exception\Event\ThrowException;
use Hipay\MiraklConnector\Exception\InvalidBankInfoException;
use Hipay\MiraklConnector\Service\Ftp;
use Hipay\MiraklConnector\Service\Ftp\ConfigurationInterface
    as FtpConfiguration;
use Hipay\MiraklConnector\Service\Validation\ModelValidator;
use Hipay\MiraklConnector\Service\Zip;
use Hipay\MiraklConnector\Vendor\Event\AddBankAccount;
use Hipay\MiraklConnector\Vendor\Event\CheckAvailability;
use Hipay\MiraklConnector\Vendor\Event\CreateWallet;
use Hipay\MiraklConnector\Vendor\Model\ManagerInterface;
use Hipay\MiraklConnector\Vendor\Model\VendorInterface;
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

    protected $vendorManager;

    /**
     * Processor constructor.
     * @param MiraklConfiguration $miraklConfig
     * @param HipayConfiguration $hipayConfig
     * @param EventDispatcherInterface $dispatcherInterface
     * @param LoggerInterface $logger
     * @param FtpConfiguration $ftpConfiguration
     * @param ManagerInterface $vendorManager
     */
    public function __construct(
        MiraklConfiguration $miraklConfig,
        HipayConfiguration $hipayConfig,
        EventDispatcherInterface $dispatcherInterface,
        LoggerInterface $logger,
        FtpConfiguration $ftpConfiguration,
        ManagerInterface $vendorManager
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

        $this->vendorManager = $vendorManager;
    }

    /**
     * Fetch the vendors from Mirakl
     *
     * @param DateTime $lastUpdate
     * @return array
     */
    public function getVendors(DateTime $lastUpdate = null)
    {
        return $this->mirakl->getVendors($lastUpdate);
    }

    /**
     * Check if the vendor already has a wallet
     *
     * Dispatch the event <b>before.availability.check</b>
     * before sending the data to Hipay
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
        $result = $this->hipay->isAvailable($event->getEmail());
        return !$result;
    }

    /**
     * Create a Hipay wallet
     *
     * Dispatch the event <b>before.wallet.create</b>
     * before sending the data to Hipay
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
        //Downloads the zip file containing the documents
        try {
            file_put_contents(
                $tmpZipFilePath,
                $this->mirakl->downloadShopsDocuments($shopIds)
            );
        } catch (\Exception $e) {
            $this->logger->notice("No file was transfered");
            return;
        }


        $zip = new Zip($tmpZipFilePath);

        $tmpExtractPath = $tmpExtractPath ?: dirname($tmpZipFilePath);

        if ($zip->extractFiles($tmpExtractPath)) {
            unlink($tmpZipFilePath);
        };

        $tmpExtractDirectory = opendir($tmpExtractPath);

        while (($shopId = readdir($tmpExtractDirectory)) !== false) {
            //Ignore . and .. entries
            if ($shopId == '.'
                || $shopId == '..'
                || !in_array($shopId, $shopIds)
            ) {
                continue;
            }

            $shopDirectoryPath = $tmpExtractPath .
                DIRECTORY_SEPARATOR . $shopId;

            //Check if $shopDirectoryPath is a directry
            if (!is_dir($shopDirectoryPath)) {
                throw new \RuntimeException(
                    "$shopDirectoryPath should be a directory"
                );
            }

            //Construct the path for the ftp
            $ftpShopDirectoryPath = $ftpShopsPath .
                DIRECTORY_SEPARATOR . $shopId;

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
                $source = $shopDirectoryPath .
                    DIRECTORY_SEPARATOR . $shopDocument;
                $destination = $ftpShopDirectoryPath .
                    DIRECTORY_SEPARATOR . $shopDocument;
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
        return $result;
    }

    /**
     * Check that the bank information is the same in the two services
     *
     * @param VendorInterface $vendor
     * @param BankInfo $miraklBankInfo
     *
     * @return bool
     *
     */
    public function isIBANCorrect(
        VendorInterface $vendor,
        BankInfo $miraklBankInfo
    )
    {
        $hipayBankInfo = $this->getBankInfo($vendor);
        return $hipayBankInfo->getIban() == $miraklBankInfo->getIban();
    }

    /**
     * Return the bank info from Hipay
     *
     * @param VendorInterface $vendor
     * @return BankInfo
     */
    public function getBankInfo(VendorInterface $vendor)
    {
        return $this->hipay->bankInfosCheck($vendor);
    }

    /**
     * Add bank account information to Hipay
     * Dispatch the event <b>before.bankAccount.add</b>
     *
     * @param VendorInterface $vendor
     * @param BankInfo $bankInfo
     * @return bool
     *
     */
    public function addBankAccount(VendorInterface $vendor, BankInfo $bankInfo)
    {
        $event = new AddBankAccount($bankInfo);

        $this->dispatcher->dispatch(
            'before.bankAccount.add',
            $event
        );

        return $this->hipay->bankInfoRegister($vendor, $event->getBankInfo());
    }

    /**
     * @param DateTime $lastUpdate
     * @param $zipPath
     * @param $ftpPath
     */
    public function process($zipPath, $ftpPath, DateTime $lastUpdate = null)
    {
        $this->logger->info("Vendor Processing");

        //Vendor data fetching from Mirakl
        $this->logger->info("Vendors fetching from Mirakl");
        $miraklData = $this->getVendors($lastUpdate);
        $this->logger->info(
            "[OK] Fetched vendors from Mirakl : " . count($miraklData)
        );

        $miraklData = $this->indexArray($miraklData, 'shop_id');

        //Wallet creation
        $this->logger->info("Wallet creation");
        $vendorCollection = $this->registerWallets($miraklData);
        $this->logger->info("Wallets created : " . count($vendorCollection));

        $this->vendorManager->saveAll($vendorCollection);

        $this->logger->info("Transfer files");
        $this->transferFiles(
            array_keys($vendorCollection),
            $zipPath,
            $ftpPath
        );
        $this->logger->info("[OK] Files transferred");

        $this->logger->info("Update bank data");
        $this->handleBankInfo($vendorCollection, $miraklData);
        $this->logger->info("[OK] Bank info updated");
    }

    /**
     * @param VendorInterface $vendor
     */
    protected function getImmutableValues(VendorInterface $vendor)
    {
        $previousValues['email'] = $vendor->getEmail();
        $previousValues['hipayId'] = $vendor->getHipayId();
        $previousValues['miraklId'] = $vendor->getMiraklId();

        return $previousValues;
    }

    /**
     * Register wallets into Hipay
     *
     * @param $miraklData
     *
     * @return VendorInterface[] an array of vendor to save
     */
    public function registerWallets($miraklData)
    {
        $vendorCollection = array();
        foreach ($miraklData as $vendorData) {
            $this->logger->debug(
                "Shop id : {shopId}",
                array("shopId" => $vendorData['shop_id'])
            );

            try {
                //Vendor recording
                $vendor = $this->vendorManager->findByEmail(
                    $vendorData['contact_informations']['email']
                );
                if (!$vendor) {
                    if (!$this->hasWallet(
                        $vendorData['contact_informations']['email']
                    )
                    ) {
                        //Wallet create (call to Hipay)
                        $hipayId = $this->createWallet($vendorData);
                        $vendor = $this->vendorManager->create(
                            $vendorData['contact_informations']['email'],
                            $vendorData['shop_id'],
                            $hipayId,
                            $vendorData
                        );
                        $this->logger->info(
                            "[OK] Created wallet for : " .
                            $vendor->getMiraklId(),
                            array("shopId" => $vendor->getMiraklId())
                        );
                    } else {
                        $vendor = $this->recordWallet(
                            $vendorData['contact_informations']['email'],
                            $vendorData['shop_id'],
                            $vendorData
                        );
                    }
                }

                $previousValues = $this->getImmutableValues($vendor);
                //Put more data into the vendor
                $this->vendorManager->update($vendor, $vendorData);

                ModelValidator::validate($vendor);

                ModelValidator::checkImmutability($vendor, $previousValues);

                if ($this->vendorManager->isValid($vendor)) {

                };
                $vendorCollection[$vendor->getMiraklId()] = $vendor;

            } catch (DispatchableException $e) {
                $this->logger->warning(
                    $e->getMessage(),
                    array("shopId" => $vendorData['shop_id'])
                );
                $this->dispatcher->dispatch(
                    $e->getEventName(), new ThrowException($e)
                );
            }
        }

        return $vendorCollection;
    }

    /**
     * Handle mirakl data collection
     *
     * @param VendorInterface[] $vendorCollection
     * @param array $miraklDataCollection mirakl data indexed by shop id
     */
    public function handleBankInfo($vendorCollection, $miraklDataCollection)
    {
        /** @var VendorInterface $vendor */
        foreach ($vendorCollection as $vendor) {
            $this->logger->debug(
                "Shop id : " . $vendor->getMiraklId(),
                array("shopId" => $vendor->getMiraklId())
            );

            $bankInfoStatus = $this->getBankInfoStatus($vendor);

            $miraklBankInfo = new BankInfo();
            $miraklBankInfo->setMiraklData(
                $miraklDataCollection[$vendor->getMiraklId()]
            );

            $this->logger->debug($bankInfoStatus);
            try {
                if ($bankInfoStatus == BankInfoStatus::BLANK) {
                    if ($this->addBankAccount($vendor, $miraklBankInfo)) {
                        $this->logger->info(
                            "[OK] Created bank account for : " .
                            $vendor->getMiraklId(),
                            array("shopId" => $vendor->getMiraklId())
                        );
                    } else {
                        throw new BankAccountCreationFailedException(
                            $vendor,
                            $miraklBankInfo
                        );
                    }
                }
                if ($bankInfoStatus == BankInfoStatus::VALIDATED) {
                    if (!$this->isIBANCorrect($vendor, $miraklBankInfo)) {
                        throw new InvalidBankInfoException(
                            $vendor,
                            $miraklBankInfo
                        );
                    } else {
                        $this->logger->info(
                            "[OK] The bank information is synchronized"
                        );
                    }
                }
            } catch (DispatchableException $e) {
                $this->logger->warning(
                    $e->getMessage(),
                    array("shopId" => $vendor->getMiraklId())
                );
                $this->dispatcher->dispatch(
                    $e->getEventName(), new ThrowException($e)
                );
            }
        }
    }

    /**
     * To record a wallet in the database in the case there was an error
     *
     * @param $email
     * @param $miraklId
     * @param $miraklData
     * @return VendorInterface
     */
    protected function recordWallet($email, $miraklId, $miraklData)
    {
        $walletId = $this->hipay->getWalletId($email);
        $this->logger->debug("The wallet number is $walletId");
        $vendor = $this->vendorManager->create(
            $email,
            $miraklId,
            $walletId,
            $miraklData
        );
        $this->logger->info("[OK] Wallet recorded");
        return $vendor;
    }

    /**
     * Fetch
     * @param $email
     * @param $miraklId
     */
    public function recordVendor($email, $miraklId)
    {
        $miraklData = current(
            $this->mirakl->getVendors(null, false, array($miraklId))
        );
        $vendor = $this->recordWallet($email, $miraklId, $miraklData);
        $this->vendorManager->save($vendor);
    }
}