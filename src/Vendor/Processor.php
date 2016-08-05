<?php

namespace HiPay\Wallet\Mirakl\Vendor;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Guzzle\Http\Exception\ClientErrorResponseException;
use HiPay\Wallet\Mirakl\Api\Factory as ApiFactory;
use HiPay\Wallet\Mirakl\Api\HiPay;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\BankInfo;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\MerchantData;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\UserAccountBasic;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\UserAccountDetails;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\BankInfo as BankInfoStatus;
use HiPay\Wallet\Mirakl\Api\Mirakl;
use HiPay\Wallet\Mirakl\Common\AbstractApiProcessor;
use HiPay\Wallet\Mirakl\Exception\BankAccountCreationFailedException;
use HiPay\Wallet\Mirakl\Exception\DispatchableException;
use HiPay\Wallet\Mirakl\Exception\InvalidBankInfoException;
use HiPay\Wallet\Mirakl\Exception\InvalidVendorException;
use HiPay\Wallet\Mirakl\Service\Validation\ModelValidator;
use HiPay\Wallet\Mirakl\Vendor\Event\AddBankAccount;
use HiPay\Wallet\Mirakl\Vendor\Event\CheckAvailability;
use HiPay\Wallet\Mirakl\Vendor\Event\CheckBankInfos;
use HiPay\Wallet\Mirakl\Vendor\Event\CreateWallet;
use HiPay\Wallet\Mirakl\Vendor\Model\DocumentInterface;
use HiPay\Wallet\Mirakl\Vendor\Model\DocumentManagerInterface;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorManagerInterface;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use HiPay\Wallet\Mirakl\Api\HiPay\Wallet\AccountInfo;

/**
 * Vendor processor handling the wallet creation
 * and the bank info registration and verification.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Processor extends AbstractApiProcessor
{
    /** @var VendorManagerInterface */
    protected $vendorManager;

    /**
     * @var DocumentManagerInterface
     */
    protected $documentManager;

    private $documentTypes = array(

        // For all types of businesses
        'ALL_PROOF_OF_BANK_ACCOUNT' => HiPay::DOCUMENT_ALL_PROOF_OF_BANK_ACCOUNT,

        // For individual only
        'INDIVIDUAL_IDENTITY' => HiPay::DOCUMENT_INDIVIDUAL_IDENTITY,
        'INDIVIDUAL_PROOF_OF_ADDRESS' => HiPay::DOCUMENT_INDIVIDUAL_PROOF_OF_ADDRESS,

        // For legal entity businesses only
        'LEGAL_IDENTITY_OF_REPRESENTATIVE' => HiPay::DOCUMENT_LEGAL_IDENTITY_OF_REPRESENTATIVE,
        'LEGAL_PROOF_OF_REGISTRATION_NUMBER' => HiPay::DOCUMENT_LEGAL_PROOF_OF_REGISTRATION_NUMBER,
        'LEGAL_ARTICLES_DISTR_OF_POWERS' => HiPay::DOCUMENT_LEGAL_ARTICLES_DISTR_OF_POWERS,

        // For one man businesses only
        'SOLE_BUS_IDENTITY' => HiPay::DOCUMENT_SOLE_BUS_IDENTITY,
        'SOLE_BUS_PROOF_OF_REG_NUMBER' => HiPay::DOCUMENT_SOLE_BUS_PROOF_OF_REG_NUMBER,
        'SOLE_BUS_PROOF_OF_TAX_STATUS' => HiPay::DOCUMENT_SOLE_BUS_PROOF_OF_TAX_STATUS

    );

    /**
     * Processor constructor.
     *
     * @param EventDispatcherInterface $dispatcherInterface
     * @param LoggerInterface $logger
     * @param ApiFactory $factory
     * @param VendorManagerInterface $vendorManager
     * @param DocumentManagerInterface $documentManager
     */
    public function __construct(
        EventDispatcherInterface $dispatcherInterface,
        LoggerInterface $logger,
        ApiFactory $factory,
        VendorManagerInterface $vendorManager,
        DocumentManagerInterface $documentManager
    ) {
        parent::__construct(
            $dispatcherInterface,
            $logger,
            $factory
        );

        $this->vendorManager = $vendorManager;
        $this->documentManager = $documentManager;
    }

    /**
     * Main function to call who process the vendors
     * to create the wallets and register or verify the bank information.
     *
     * @param DateTime $lastUpdate
     * @param $tmpFilesPath
     *
     * @codeCoverageIgnore
     */
    public function process($tmpFilesPath, DateTime $lastUpdate = null)
    {
        try {
            $this->logger->info('Vendor Processing');

            //Vendor data fetching from Mirakl
            $this->logger->info('Vendors fetching from Mirakl');
            $miraklData = $this->getVendors($lastUpdate);
            $this->logger->info(
                '[OK] Fetched vendors from Mirakl : '.count($miraklData)
            );

            //Wallet creation
            $this->logger->info('Wallet creation');
            $vendorCollection = $this->registerWallets($miraklData);
            $this->logger->info('[OK] Wallets : ' . count($vendorCollection));

            //Vendor saving
            $this->logger->info("Saving vendor");
            $this->vendorManager->saveAll($vendorCollection);
            $this->logger->info("[OK] Vendor saved");

            //File transfer
            $this->logger->info('Transfer files');
            $this->transferFiles(
                array_keys($vendorCollection),
                $tmpFilesPath
            );

            // Bank data updating
            $this->logger->info('Update bank data');
            $this->handleBankInfo($vendorCollection, $miraklData);
            $this->logger->info('[OK] Bank info updated');

        } catch (ClientErrorResponseException $e) {

            try {

                $this->logger->critical(
                    $e->getMessage() . ' - ' . $e->getResponse()->getBody(true)
                );
            }

            catch(\Exception $ex) {
                $this->handleException($e, "critical");
            }
        }

        catch (\Exception $e) {

            $trace = array_map(function ($item) {
                return array(
                    'file' => $item['file'],
                    'line' => $item['line']
                );
            }, $e->getTrace());

            $this->handleException($e, "critical", $trace);
        }
    }

    /**
     * Fetch the vendors from Mirakl.
     *
     * @param DateTime $lastUpdate
     *
     * @return array
     */
    public function getVendors(DateTime $lastUpdate = null)
    {
        $this->dispatcher->dispatch('before.vendor.get');
        $return = $this->mirakl->getVendors($lastUpdate);
        $this->dispatcher->dispatch('after.vendor.get');
        return $return;
    }

    /**
     * Register wallets into HiPay.
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
                'Shop id : {shopId}',
                array('shopId' => $vendorData['shop_id'])
            );

            try {
                //Vendor recording
                $email = $vendorData['contact_informations']['email'];
                $vendor = $this->vendorManager->findByEmail($email);
                if (!$vendor) {
                    if (!$this->hasWallet($email)) {
                        //Wallet create (call to HiPay)
                        $walletInfo = $this->createWallet($vendorData);
                        $this->logger->info(
                            '[OK] Created wallet for : '.
                            $vendorData['shop_id'],
                            array('shopId' => $vendorData['shop_id'])
                        );
                    } else {
                        //Fetch the wallet id from HiPay
                        $walletInfo = $this->hipay->getWalletInfo($email);
                    }
                    $vendor = $this->createVendor(
                        $email,
                        $walletInfo->getUserAccountld(),
                        $walletInfo->getUserSpaceld(),
                        $walletInfo->getIdentified(),
                        $vendorData['shop_id'],
                        $vendorData
                    );
                }

                $previousValues = $this->getImmutableValues($vendor);
                //Put more data into the vendor
                $this->vendorManager->update($vendor, $vendorData);

                if (!$this->vendorManager->isValid($vendor)) {
                    throw new InvalidVendorException($vendor);
                };

                ModelValidator::validate($vendor);

                ModelValidator::checkImmutability($vendor, $previousValues);

                $vendorCollection[$vendor->getMiraklId()] = $vendor;
                $this->logger->info('[OK] The vendor is treated');
            } catch (DispatchableException $e) {
                $this->handleException($e, 'warning', array('shopId' => $vendorData['shop_id']));
            }
        }

        return $vendorCollection;
    }

    /**
     * Check if the vendor already has a wallet.
     *
     * Dispatch the event <b>before.availability.check</b>
     * before sending the data to HiPay
     *
     * @param string $email
     *
     * @return bool
     */
    public function hasWallet($email)
    {
        $event = new CheckAvailability($email);
        $this->dispatcher->dispatch('before.availability.check', $event);
        $result = $this->hipay->isAvailable($email, $event->getEntity());
        $this->dispatcher->dispatch('after.availability.check', $event);
        return !$result;
    }

    /**
     * Create a HiPay wallet.
     *
     * Dispatch the event <b>before.wallet.create</b>
     * before sending the data to HiPay
     *
     * @param array $shopData
     *
     * @return AccountInfo the created account info
     */
    protected function createWallet(array $shopData)
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

        $walletInfo = $this->hipay->createFullUseraccount(
            $event->getUserAccountBasic(),
            $event->getUserAccountDetails(),
            $event->getMerchantData()
        );

        $this->dispatcher->dispatch(
            'after.wallet.create',
            $event
        );

        return $walletInfo;
    }

    /**
     * To record a wallet in the database in the case there was an error.
     *
     * @param string $email
     * @param int $walletId
     * @param int $walletSpaceId
     * @param boolean $identified
     * @param int $miraklId
     * @param array $miraklData
     * @return VendorInterface
     */
    protected function createVendor($email, $walletId, $walletSpaceId, $identified, $miraklId, $miraklData)
    {
        $this->logger->debug("The wallet number is $walletId");
        $vendor = $this->vendorManager->create(
            $email,
            $miraklId,
            $walletId,
            $walletSpaceId,
            $identified,
            $miraklData
        );

        $vendor->setEmail($email);
        $vendor->setHiPayId($walletId);
        $vendor->setMiraklId($miraklId);
        $vendor->setHiPayUserSpaceId($walletSpaceId);
        $vendor->setHiPayIdentified($identified);

        $this->logger->info('[OK] Wallet recorded');

        return $vendor;
    }

    /**
     * Return the values who should't
     * change after the registration of the hipay wallet.
     *
     * @param VendorInterface $vendor
     */
    protected function getImmutableValues(VendorInterface $vendor)
    {
        $previousValues['email'] = $vendor->getEmail();
        $previousValues['hipayId'] = $vendor->getHiPayId();
        $previousValues['miraklId'] = $vendor->getMiraklId();

        return $previousValues;
    }

    /**
     * Transfer the files from Mirakl to HiPay using REST endpoint.
     *
     * @param array $shopIds
     * @param $tmpFilePath
     * @throws Exception
     */
    public function transferFiles(
        array $shopIds,
        $tmpFilePath
    ) {

        if (count($shopIds) > 0)
        {
            // Fetches all Mirakl file names
            $allMiraklFiles = array();

            foreach (array_chunk($shopIds, 50) as $someShopIds) {
                $allMiraklFiles = array_merge($allMiraklFiles, $this->mirakl->getFiles($someShopIds));
            }

            $docTypes = $this->documentTypes;

            // We only keep the files with types we know
            $files = array_filter($allMiraklFiles, function($aFile) use ($docTypes) {
                return in_array($aFile['type'], array_keys($docTypes));
            });

            foreach ($shopIds as $shopId)
            {
                $this->logger->info('Will check files for Mirakl shop '. $shopId);

                // Fetches documents already sent to HiPay Wallet
                $vendor = $this->vendorManager->findByMiraklId($shopId);
                $documents = $this->documentManager->findByVendor($vendor);

                // Keep Mirakl files for this shop only
                $theFiles = array_filter($files, function($file) use ($shopId) {
                    return $file['shop_id'] == $shopId;
                });

                $this->logger->info('Found '.count($theFiles).' files on Mirakl for shop '. $shopId);

                // Check all files for current shop
                foreach($theFiles as $theFile)
                {
                    $filesAlreadyUploaded = array_values(array_filter($documents, function (DocumentInterface $document) use ($theFile) {
                        return $document->getDocumentType() == $theFile['type'] && $document->getMiraklDocumentId() == $theFile['id'];
                    }));

                    // File not uploaded (or outdated)
                    if (count($filesAlreadyUploaded) === 0) {

                        $this->logger->info('Document '.$theFile['id'].' (type: '.$theFile['type'].') for Mirakl for shop '. $shopId.' is not uploaded or not up to date. Will upload');

                        $validityDate = null;

                        if (in_array($this->documentTypes[$theFile['type']], array(
                            HiPay::DOCUMENT_SOLE_BUS_IDENTITY,
                            HiPay::DOCUMENT_INDIVIDUAL_IDENTITY,
                            HiPay::DOCUMENT_LEGAL_IDENTITY_OF_REPRESENTATIVE
                        ))) {
                            $validityDate = new DateTime('+1 year');
                        }

                        $tmpFile = $tmpFilePath . '/mirakl_kyc_downloaded_file.tmp';

                        file_put_contents(
                            $tmpFile,
                            $this->mirakl->downloadDocuments(array($theFile['id']))
                        );

                        try {
                            $this->hipay->uploadDocument(
                                $vendor->getHiPayUserSpaceId(),
                                $this->documentTypes[$theFile['type']],
                                $tmpFile,
                                $validityDate);

                            $newDocument = $this->documentManager->create($theFile['id'], new \DateTime($theFile['date_uploaded']), $theFile['type'], $vendor);
                            $this->documentManager->save($newDocument);

                            $this->logger->info('Upload done. Document saved with ID: ' . $newDocument->getId());
                        }

                            // If this upload fails, we log the error but we continue for other files
                        catch (ClientErrorResponseException $e) {

                            try {
                                $message = 'The document '.$theFile['type'].' for Mirakl shop '.$shopId.' could not be uploaded to HiPay Wallet for the following reason: ';

                                $this->logger->critical(
                                    $message . $e->getMessage() . ' - ' . ($e->getResponse() !== null ? $e->getResponse()->getBody(true) : '')
                                );
                            }

                            catch(\Exception $ex) {
                                throw $ex;
                            }
                        }
                    }

                    else {
                        $this->logger->info('Document '.$theFile['id'].' (type: '.$theFile['type'].') for Mirakl for shop '. $shopId.' is already uploaded with ID ' . $filesAlreadyUploaded[0]->getId());
                    }
                }
            }
        }
    }

    /**
     * Register the bank account and verify the
     * synchronicity of the bank information in both platform.
     *
     * @param VendorInterface[] $vendorCollection
     * @param array[]             $miraklDataCollection mirakl data
     * Expect one mirakl data for each vendor present in the vendorCollection
     */
    public function handleBankInfo($vendorCollection, $miraklDataCollection)
    {
        //Index mirakl Data
        $miraklDataCollection = $this->indexMiraklData($miraklDataCollection);

        /** @var VendorInterface $vendor */
        foreach ($vendorCollection as $vendor) {
            $this->logger->debug(
                'Shop id : '.$vendor->getMiraklId(),
                array('shopId' => $vendor->getMiraklId())
            );

            try {
                //Check if there is data associated to the current vendor
                if (!isset($miraklDataCollection[$vendor->getMiraklId()])) {
                    $this->logger->notice("The vendor {$vendor->getMiraklId()} in the mirakl collection");
                } else {
                    $bankInfoStatus = $this->getBankInfoStatus($vendor);

                    $miraklBankInfo = new BankInfo();
                    $miraklBankInfo->setMiraklData(
                        $miraklDataCollection[$vendor->getMiraklId()]
                    );

                    $this->logger->debug($bankInfoStatus);
                    switch (trim($bankInfoStatus)) {
                        case BankInfoStatus::BLANK:
                            if ($this->sendBankAccount($vendor, $miraklBankInfo)) {
                                $this->logger->info(
                                    '[OK] Created bank account for : ' .
                                    $vendor->getMiraklId(),
                                    array('shopId' => $vendor->getMiraklId())
                                );
                            } else {
                                throw new BankAccountCreationFailedException(
                                    $vendor,
                                    $miraklBankInfo
                                );
                            }
                            break;
                        case BankInfoStatus::VALIDATED:
                            if (!$this->isBankInfosSynchronised($vendor, $miraklBankInfo)) {
                                throw new InvalidBankInfoException(
                                    $vendor,
                                    $miraklBankInfo
                                );
                            } else {
                                $this->logger->info(
                                    '[OK] The bank information is synchronized'
                                );
                            }
                            break;
                        default:
                    }
                }
            } catch (InvalidBankInfoException $e) {
                $this->handleException($e, 'critical', array('shopId' => $vendor->getMiraklId()));
            }
            catch (Exception $e) {
                $this->handleException($e, 'warning', array('shopId' => $vendor->getMiraklId()));
            }
        }
    }

    /**
     * Index mirakl data fetched with a call to S20 resource from their API
     *
     * @param $miraklData
     * @return array
     */
    protected function indexMiraklData($miraklData)
    {
        $indexedMiraklData = array();
        foreach ($miraklData as $data) {
            $indexedMiraklData[$data['shop_id']] = $data;
        }
        return $indexedMiraklData;
    }

    /**
     * Get bank info status from HiPay.
     *
     * @param VendorInterface $vendor
     *
     * @return string
     */
    public function getBankInfoStatus(
        VendorInterface $vendor
    ) {
        $result = $this->hipay->bankInfosStatus($vendor);
        return $result;
    }

    /**
     * Add bank account information to HiPay
     * Dispatch the event <b>before.bankAccount.add</b>.
     *
     * @param VendorInterface $vendor
     * @param BankInfo        $bankInfo
     *
     * @return bool
     */
    protected function sendBankAccount(VendorInterface $vendor, BankInfo $bankInfo)
    {
        $event = new AddBankAccount($bankInfo);

        $this->dispatcher->dispatch(
            'before.bankAccount.add',
            $event
        );

        return $this->hipay->bankInfosRegister($vendor, $event->getBankInfo());
    }

    /**
     * Check that the bank information is the same in the two services.
     *
     * @param VendorInterface $vendor
     * @param BankInfo        $miraklBankInfo
     *
     * @return bool
     */
    protected function isBankInfosSynchronised(
        VendorInterface $vendor,
        BankInfo $miraklBankInfo
    ) {
        $hipayBankInfo = $this->getBankInfo($vendor);
        $event = new CheckBankInfos($miraklBankInfo, $hipayBankInfo);
        $ibanCheck = ($hipayBankInfo->getIban() == $miraklBankInfo->getIban());
        $this->dispatcher->dispatch('check.bankInfos.synchronicity', $event);
        return $ibanCheck && $event->isSynchrony();
    }

    /**
     * Return the bank info from HiPay.
     *
     * @param VendorInterface $vendor
     *
     * @return BankInfo
     */
    public function getBankInfo(VendorInterface $vendor)
    {
        return $this->hipay->bankInfosCheck($vendor);
    }

    /**
     * Return true if banking information is the same at Mirakl and HiPay
     * The soap call will fail if the bank info status at HiPay is not validated
     * @param VendorInterface $vendor
     * @param array|BankInfo $miraklBankInfo
     * @param boolean $checkBankStatus set to true if you also want to check to bank info status prior to fetch them
     * @return false|true is the status of the baking information is not validated
     * @throws InvalidBankInfoException the the information is not same by
     */
    public function isBankInfoUsable(VendorInterface $vendor, $miraklBankInfo, $checkBankStatus = false)
    {
        if ($checkBankStatus) {
            $bankInfoStatus = $this->getBankInfoStatus($vendor);

            if (trim($bankInfoStatus) == BankInfoStatus::VALIDATED) {
                return false;
            }
        }

        if (is_array($miraklBankInfo)) {
            $bankInfo = new BankInfo();
            $miraklBankInfo = $bankInfo->setMiraklData($miraklBankInfo);
        }

        return $this->isBankInfosSynchronised($vendor, $miraklBankInfo);
    }

    /**
     * Add the bank information to a wallet
     * The call will fail if the bank information status is not blank
     * @param VendorInterface $vendor
     * @param array|BankInfo $miraklBankInfo
     * @param bool|false $checkBankStatus set to true if you also want to check to bank info status prior to add them
     * @return bool
     */
    public function addBankInformation($vendor, $miraklBankInfo, $checkBankStatus = false)
    {
        if ($checkBankStatus) {
            $bankInfoStatus = $this->getBankInfoStatus($vendor);

            if (trim($bankInfoStatus) == BankInfoStatus::BLANK) {
                return false;
            }
        }

        if (is_array($miraklBankInfo)) {
            $bankInfo = new BankInfo();
            $miraklBankInfo = $bankInfo->setMiraklData($miraklBankInfo);
        }

        return $this->sendBankAccount($vendor, $miraklBankInfo);
    }

    /**
     * Save a vendor in case there was an error.
     *
     * @param string $email
     * @param int $miraklId
     */
    public function recordVendor($email, $miraklId)
    {
        $miraklData = current(
            $this->mirakl->getVendors(null, false, array($miraklId))
        );
        $hipayInfo = $this->hipay->getWalletInfo($miraklData['contact_informations']['email']);
        $vendor = $this->createVendor($email, $hipayInfo->getUserAccountld(), $hipayInfo->getUserSpaceld(), $hipayInfo->getIdentified(), $miraklId, $miraklData);
        $this->vendorManager->save($vendor);
    }

    /**
     * Returns the wallet registered at HiPay
     *
     * @param int $merchantGroupId
     * @param DateTime|null $pastDate
     *
     * @return array
     */
    public function getWallets($merchantGroupId, DateTime $pastDate = null)
    {
        if (!$pastDate) {
            $pastDate = new DateTime('1970-01-01');
        }
        return $this->hipay->getMerchantGroupAccounts($merchantGroupId, $pastDate);
    }
}
