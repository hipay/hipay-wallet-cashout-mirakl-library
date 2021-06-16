<?php

namespace HiPay\Wallet\Mirakl\Vendor;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Guzzle\Http\Exception\ClientErrorResponseException;
use HiPay\Wallet\Mirakl\Api\Factory as ApiFactory;
use HiPay\Wallet\Mirakl\Api\HiPay;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Rest\BankInfo;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Rest\UserAccount;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\BankInfo as BankInfoStatus;
use HiPay\Wallet\Mirakl\Api\Mirakl;
use HiPay\Wallet\Mirakl\Common\AbstractApiProcessor;
use HiPay\Wallet\Mirakl\Exception\BankAccountCreationFailedException;
use HiPay\Wallet\Mirakl\Exception\DispatchableException;
use HiPay\Wallet\Mirakl\Exception\InvalidBankInfoException;
use HiPay\Wallet\Mirakl\Exception\InvalidVendorException;
use HiPay\Wallet\Mirakl\Exception\WalletAccountUpdateException;
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
use HiPay\Wallet\Mirakl\Notification\FormatNotification;
use HiPay\Wallet\Mirakl\Notification\Model\LogVendorsManagerInterface;
use HiPay\Wallet\Mirakl\Notification\Model\LogVendorsInterface;

/**
 * Vendor processor handling the wallet creation
 * and the bank info registration and verification.
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
class Processor extends AbstractApiProcessor
{
    /** @var VendorManagerInterface */
    protected $vendorManager;

    /** @var VendorManagerInterface */
    protected $logVendorManager;

    /**
     * @var DocumentManagerInterface
     */
    protected $documentManager;

    /**
     * @var FormatNotification class
     */
    protected $formatNotification;

    /**
     * @var array
     */
    protected $vendorsLogs;

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
        DocumentManagerInterface $documentManager,
        LogVendorsManagerInterface $logVendorManager
    ) {
        parent::__construct($dispatcherInterface, $logger, $factory);

        $this->vendorManager = $vendorManager;
        $this->documentManager = $documentManager;
        $this->logVendorManager = $logVendorManager;
        $this->formatNotification = new FormatNotification();
        $this->vendorsLogs = array();
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
            $this->logger->info('Control Mirakl Settings', array('miraklId' => null, "action" => "Wallet creation"));

            // control mirakl settings
            $boolControl = $this->getControlMiraklSettings(
                array_merge($this->documentTypes,$this->bankInfoDocumentType)
            );

            if ($boolControl === false) {
                // log critical
                $title = $this->criticalMessageMiraklSettings;
                $message = $this->formatNotification->formatMessage($title);
                $this->logger->critical($message, array('miraklId' => null, "action" => "Wallet creation"));
            } else {
                $this->logger->info(
                    'Control Mirakl Settings OK',
                    array('miraklId' => null, "action" => "Wallet creation")
                );
            }

            $this->logger->info('Vendor Processing', array('miraklId' => null, "action" => "Wallet creation"));

            //Vendor data fetching from Mirakl
            $this->logger->info(
                'Vendors fetching from Mirakl',
                array('miraklId' => null, "action" => "Wallet creation")
            );
            $miraklData = $this->getVendors($lastUpdate);
            $this->logger->info(
                '[OK] Fetched vendors from Mirakl : ' . count($miraklData),
                array('miraklId' => null, "action" => "Wallet creation")
            );

            //Wallet creation
            $this->logger->info('Wallet creation', array('miraklId' => null, "action" => "Wallet creation"));
            $vendorCollection = $this->registerWallets($miraklData);
            $this->logger->info(
                '[OK] Wallets : ' . count($vendorCollection),
                array('miraklId' => null, "action" => "Wallet creation")
            );

            //Vendor saving
            $this->logger->info("Saving vendor", array('miraklId' => null, "action" => "Wallet creation"));
            $this->vendorManager->saveAll($vendorCollection);
            $this->logger->info("[OK] Vendor saved", array('miraklId' => null, "action" => "Wallet creation"));

            //File transfer
            $this->logger->info('Transfer files', array('miraklId' => null, "action" => "Wallet creation"));
            $this->transferFiles(
                array_keys($vendorCollection),
                $tmpFilesPath
            );

            // Bank data updating
            $this->logger->info('Update bank data', array('miraklId' => null, "action" => "Wallet creation"));
            $this->handleBankInfo($vendorCollection, $miraklData, $tmpFilesPath);
            $this->logger->info('[OK] Bank info updated', array('miraklId' => null, "action" => "Wallet creation"));

            $this->logVendorManager->saveAll($this->vendorsLogs);
        } catch (Exception $e) {
            try {
                // log critical
                $title = 'Error Vendor:Process ';
                $exceptionMsg = $e->getMessage();
                $message = $this->formatNotification->formatMessage($title, false, $exceptionMsg);
                $this->logger->critical($message);
            } catch (\Exception $ex) {
                $this->handleException($e, "critical", array('miraklId' => null, "action" => "Wallet creation"));
            }
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

        $return = array_filter($return, array($this, 'filterVendors'));

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
                array(
                    'shopId' => $vendorData['shop_id'],
                    'miraklId' => $vendorData['shop_id'],
                    "action" => "Wallet creation"
                )
            );

            try {
                //Vendor recording
                $email = $vendorData['contact_informations']['email'];
                $miraklId = $vendorData['shop_id'];

                $vendor = $this->vendorManager->findByMiraklId($miraklId);

                if (!$vendor) {
                    if (!$this->hasWallet($email)) {
                        //Wallet create (call to HiPay)
                        $walletInfo = $this->createWallet($vendorData);
                        $this->logger->info(
                            '[OK] Created wallet for : ' .
                            $vendorData['shop_id'],
                            array('miraklId' => $vendorData['shop_id'], "action" => "Wallet creation")
                        );
                    } else {
                        //Fetch the wallet id from HiPay
                        $walletInfo = $this->getWalletUserInfo($vendorData);
                    }
                    $vendor = $this->createVendor(
                        $email,
                        $walletInfo->getUserAccountld(),
                        $walletInfo->getUserSpaceld(),
                        $walletInfo->getIdentified(),
                        $vendorData['shop_id'],
                        $vendorData['pro_details']['VAT_number'],
                        $walletInfo->getCallbackSalt(),
                        $vendorData
                    );
                } elseif ($vendor) {

                    //Fetch the wallet id from HiPay
                    $walletInfo = $this->getWalletUserInfo($vendorData, $vendor);

                    if ($vendor->getEmail() !== $email) {
                        $this->updateWalletEmail($email, $vendor);
                    }

                    $vendor->setVatNumber($vendorData['pro_details']['VAT_number']);
                    $vendor->setCallbackSalt($walletInfo->getCallbackSalt());
                    $vendor->setHiPayIdentified($walletInfo->getIdentified());
                    $vendor->setEnabled(true);
                    $vendor->setCountry($vendorData["contact_informations"]["country"]);
                    $vendor->setPaymentBlocked($vendorData["payment_details"]["payment_blocked"]);
                    $vendor->setEmail($email);
                }

                $this->logVendor(
                    $vendorData['shop_id'],
                    $walletInfo->getUserAccountld(),
                    $this->generateLogin($vendorData),
                    ($walletInfo->getIdentified())
                        ? LogVendorsInterface::WALLET_IDENTIFIED : LogVendorsInterface::WALLET_NOT_IDENTIFIED,
                    LogVendorsInterface::SUCCESS,
                    $walletInfo->getRequestMessage(),
                    0,
                    true,
                    $vendorData["contact_informations"]["country"],
                    $vendorData["payment_details"]["payment_blocked"]
                );

                $previousValues = $this->getImmutableValues($vendor);
                //Put more data into the vendor
                $this->vendorManager->update($vendor, $vendorData);

                if (!$this->vendorManager->isValid($vendor)) {
                    throw new InvalidVendorException($vendor);
                }

                ModelValidator::validate($vendor);

                ModelValidator::checkImmutability($vendor, $previousValues);

                $vendorCollection[$vendor->getMiraklId()] = $vendor;

                $this->logger->info(
                    '[OK] The vendor is treated',
                    array('miraklId' => $vendor->getMiraklId(), "action" => "Wallet creation")
                );
            } catch (WalletAccountUpdateException $waue) {
                $this->handleException(
                    $waue,
                    'warning',
                    array('miraklId' => $vendorData['shop_id'], "action" => "Wallet creation")
                );
            } catch (Exception $e) {
                $this->logVendor(
                    $vendorData['shop_id'],
                    -1,
                    $this->generateLogin($vendorData),
                    LogVendorsInterface::WALLET_NOT_CREATED,
                    LogVendorsInterface::CRITICAL,
                    substr($e->getMessage(), 0, 255),
                    0,
                    false,
                    $vendorData["contact_informations"]["country"],
                    $vendorData["payment_details"]["payment_blocked"]
                );
                $this->handleException(
                    $e,
                    'warning',
                    array('miraklId' => $vendorData['shop_id'], "action" => "Wallet creation")
                );
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

    protected function updateWalletEmail($email, $vendor)
    {
        try {
            $this->hipay->updateEmail($email, $vendor);
        } catch (Exception $e) {
            throw new WalletAccountUpdateException($vendor, $e);
        }
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
        $userAccount = new UserAccount($shopData);

        $event = new CreateWallet(
            $userAccount
        );

        $this->dispatcher->dispatch(
            'before.wallet.create',
            $event
        );

        $walletInfo = $this->hipay->createFullUseraccountV2(
            $event->getUserAccount()
        );

        $this->dispatcher->dispatch(
            'after.wallet.create',
            $event
        );

        return $walletInfo;
    }

    /**
     * Get a HiPay wallet.
     *
     * Dispatch the event <b>before.wallet.create</b>
     * before sending the data to HiPay
     *
     * @param array $shopData
     *
     * @return AccountInfo the get account info
     */
    protected function getWalletUserInfo(array $shopData, VendorInterface $vendor = null)
    {
        $userAccount = new UserAccount($shopData);

        $event = new CreateWallet(
            $userAccount
        );

        $walletInfo = $this->hipay->getWalletInfo($event->getUserAccount(), $vendor);

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
    protected function createVendor(
        $email,
        $walletId,
        $walletSpaceId,
        $identified,
        $miraklId,
        $vatNumber,
        $callbackSalt,
        $miraklData
    ) {
        $this->logger->debug(
            "The wallet number is $walletId",
            array('miraklId' => $miraklId, "action" => "Wallet creation")
        );
        $vendor = $this->vendorManager->create(
            $email,
            $miraklId,
            $walletId,
            $walletSpaceId,
            $identified,
            $vatNumber,
            $callbackSalt,
            $miraklData
        );

        $vendor->setEmail($email);
        $vendor->setHiPayId($walletId);
        $vendor->setMiraklId($miraklId);
        $vendor->setHiPayUserSpaceId($walletSpaceId);
        $vendor->setHiPayIdentified($identified);
        $vendor->setVatNumber($vatNumber);
        $vendor->setCallbackSalt($callbackSalt);
        $vendor->setEnabled(true);
        $vendor->setCountry($miraklData["contact_informations"]["country"]);
        $vendor->setPaymentBlocked($miraklData["payment_details"]["payment_blocked"]);

        $this->logger->info('[OK] Wallet recorded', array('miraklId' => $miraklId, "action" => "Wallet creation"));

        return $vendor;
    }

    /**
     * Return the values who should't
     * change after the registration of the hipay wallet.
     * @param VendorInterface $vendor
     * @return mixed
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
    public function transferFiles(array $shopIds, $tmpFilePath)
    {
        if (count($shopIds) > 0) {
            // Fetches all Mirakl file names
            $allMiraklFiles = array();

            foreach (array_chunk($shopIds, 50) as $someShopIds) {
                $allMiraklFiles = array_merge($allMiraklFiles, $this->mirakl->getFiles($someShopIds));
            }

            $docTypes = $this->documentTypes;

            // We only keep the files with types we know
            $files = array_filter(
                $allMiraklFiles,
                function ($aFile) use ($docTypes) {
                    return in_array($aFile['type'], array_keys($docTypes));
                }
            );

            $backFilesType = $this->backDocumentTypes;

            // We only keep the files with types we know
            $backFiles = array_filter(
                $allMiraklFiles,
                function ($aFile) use ($backFilesType) {
                    return in_array($aFile['type'], $backFilesType);
                }
            );

            foreach ($shopIds as $shopId) {
                $this->logger->info(
                    'Will check files for Mirakl shop ' . $shopId,
                    array('miraklId' => $shopId, "action" => "Wallet creation")
                );

                // Fetches documents already sent to HiPay Wallet
                $vendor = $this->vendorManager->findByMiraklId($shopId);
                $documents = $this->documentManager->findByVendor($vendor);

                // Keep Mirakl files for this shop only
                $theFiles = array_filter(
                    $files,
                    function ($file) use ($shopId) {
                        return $file['shop_id'] == $shopId;
                    }
                );

                if (!empty($backFiles)) {
                    $shopBackFilesType = array_filter(
                        $backFiles,
                        function ($file) use ($shopId) {
                            return $file['shop_id'] == $shopId;
                        }
                    );
                } else {
                    $shopBackFilesType = array();
                }

                $this->logger->info(
                    'Found ' . count($theFiles) . ' files on Mirakl for shop ' . $shopId,
                    array('miraklId' => $shopId, "action" => "Wallet creation")
                );

                // Check all files for current shop
                foreach ($theFiles as $theFile) {
                    $filesAlreadyUploaded = array_values(
                        array_filter(
                            $documents,
                            function (DocumentInterface $document) use ($theFile) {
                                return $document->getDocumentType() == $theFile['type'] &&
                                    $document->getMiraklDocumentId() == $theFile['id'];
                            }
                        )
                    );


                    try {
                        $this->checkExtensionFile($theFile['file_name'], $theFile, $shopId);

                        // File not uploaded (or outdated)
                        if (count($filesAlreadyUploaded) === 0) {
                            $this->logger->info(
                                'Document ' .
                                $theFile['id'] .
                                ' (type: ' .
                                $theFile['type'] .
                                ') for Mirakl for shop ' .
                                $shopId .
                                ' is not uploaded or not up to date. Will upload',
                                array('miraklId' => $shopId, "action" => "Wallet creation")
                            );

                            $validityDate = null;

                            if (in_array(
                                $this->documentTypes[$theFile['type']],
                                array(
                                    HiPay::DOCUMENT_SOLE_MAN_BUS_IDENTITY,
                                    HiPay::DOCUMENT_INDIVIDUAL_IDENTITY,
                                    HiPay::DOCUMENT_LEGAL_IDENTITY_OF_REPRESENTATIVE
                                )
                            )) {
                                $validityDate = new DateTime('+1 year');
                            }

                            $tmpFile = $tmpFilePath . '/' . preg_replace("/[^A-Za-z0-9\.]/", '', $theFile['file_name']);

                            file_put_contents(
                                $tmpFile,
                                $this->mirakl->downloadDocuments(array($theFile['id']))
                            );

                            $back = $this->getFileBack(
                                $theFile['type'],
                                $shopBackFilesType,
                                $theFile,
                                $shopId,
                                $tmpFilePath
                            );

                            $this->hipay->uploadDocument(
                                $vendor->getHiPayUserSpaceId(),
                                $vendor->getHiPayId(),
                                $this->documentTypes[$theFile['type']],
                                $tmpFile,
                                $validityDate,
                                ($back !== null) ? $back['filePath'] : null
                            );

                            $newDocument = $this->documentManager->create(
                                $theFile['id'],
                                new \DateTime($theFile['date_uploaded']),
                                $theFile['type'],
                                $vendor
                            );
                            $this->documentManager->save($newDocument);

                            if ($back !== null) {
                                $newDocument = $this->documentManager->create(
                                    $back['fileObject']['id'],
                                    new \DateTime($back['fileObject']['date_uploaded']),
                                    $back['fileObject']['type'],
                                    $vendor
                                );
                                $this->documentManager->save($newDocument);
                            }

                            $this->logger->info(
                                'Upload done. Document saved with ID: ' . $newDocument->getId(),
                                array('miraklId' => $shopId, "action" => "Wallet creation")
                            );
                        } else {
                            $this->logger->info(
                                'Document ' .
                                $theFile['id'] .
                                ' (type: ' .
                                $theFile['type'] .
                                ') for Mirakl for shop ' .
                                $shopId .
                                ' is already uploaded with ID ' .
                                $filesAlreadyUploaded[0]->getId(),
                                array('miraklId' => $shopId, "action" => "Wallet creation")
                            );
                        }
                    } // If this upload fails, we log the error but we continue for other files
                    catch (Exception $e) {
                        try {
                            // log critical
                            $title = 'The document ' .
                                $theFile['type'] .
                                ' for Mirakl shop ' .
                                $shopId .
                                ' could not be uploaded to HiPay Wallet for the following reason:';
                            $infos = array(
                                'shopId' => $shopId,
                                'HipayId' => $vendor->getHiPayId(),
                                'Email' => $vendor->getEmail(),
                                'Type' => 'Critical'
                            );
                            $exceptionMsg = $e->getMessage();
                            $message = $this->formatNotification->formatMessage($title, $infos, $exceptionMsg);
                            $this->logger->critical(
                                $message,
                                array('miraklId' => $shopId, "action" => "Wallet creation")
                            );
                        } catch (\Exception $ex) {
                            throw $ex;
                        }
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
     * @param array[] $miraklDataCollection mirakl data
     * Expect one mirakl data for each vendor present in the vendorCollection
     */
    public function handleBankInfo($vendorCollection, $miraklDataCollection, $tmpFilePath)
    {
        //Index mirakl Data
        $miraklDataCollection = $this->indexMiraklData($miraklDataCollection);

        /** @var VendorInterface $vendor */
        foreach ($vendorCollection as $vendor) {
            $this->logger->debug(
                'Shop id : ' . $vendor->getMiraklId(),
                array('miraklId' => $vendor->getMiraklId(), "action" => "Wallet creation")
            );

            try {
                //Check if there is data associated to the current vendor
                if (!isset($miraklDataCollection[$vendor->getMiraklId()])) {
                    $this->logger->notice(
                        "The vendor {$vendor->getMiraklId()} in the mirakl collection",
                        array('miraklId' => $vendor->getMiraklId(), "action" => "Wallet creation")
                    );
                } else {
                    $bankInfoStatus = $this->getBankInfoStatus($vendor);

                    $miraklBankInfo = new BankInfo();


                    $this->logger->debug(
                        BankInfoStatus::getLabel($bankInfoStatus),
                        array('miraklId' => $vendor->getMiraklId(), "action" => "Wallet creation")
                    );

                    $miraklBankInfo->setMiraklData(
                        $miraklDataCollection[$vendor->getMiraklId()],
                        $this->getBankDocument($vendor->getMiraklId(), $tmpFilePath)
                    );

                    if (
                        $bankInfoStatus === BankInfoStatus::BLANK
                        || !$this->isBankInfosSynchronised($vendor, $miraklBankInfo)
                    ) {
                        if ($this->sendBankAccount($vendor, $miraklBankInfo)) {
                            $this->logger->info(
                                '[OK] Created bank account for : ' .
                                $vendor->getMiraklId(),
                                array('miraklId' => $vendor->getMiraklId(), "action" => "Wallet creation")
                            );
                        } else {
                            throw new BankAccountCreationFailedException($vendor, $miraklBankInfo);
                        }
                    } else {
                        $this->logger->info(
                            '[OK] Bank information are synchronized',
                            array('miraklId' => $vendor->getMiraklId(), "action" => "Wallet creation")
                        );
                    }
                }
            } catch (Exception $e) {
                // log critical
                $shopId = $vendor->getHiPayId();
                $title = 'Exception Warning Bank Information for Mirakl shop ' . $shopId . ':';
                $infos = array(
                    'shopId' => $vendor->getMiraklId(),
                    'HipayId' => $shopId,
                    'Email' => $vendor->getEmail(),
                    'Type' => 'Warning'
                );
                $exceptionMsg = $e->getMessage();
                $message = $this->formatNotification->formatMessage($title, $infos, $exceptionMsg);
                $this->logger->warning(
                    $message,
                    array('miraklId' => $vendor->getMiraklId(), "action" => "Wallet creation")
                );
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
    public function getBankInfoStatus(VendorInterface $vendor)
    {
        $result = $this->hipay->bankInfosStatus($vendor);
        return $result;
    }

    /**
     * Add bank account information to HiPay
     * Dispatch the event <b>before.bankAccount.add</b>.
     *
     * @param VendorInterface $vendor
     * @param BankInfo $bankInfo
     *
     * @return bool
     */
    protected function sendBankAccount(VendorInterface $vendor, BankInfo $bankInfo)
    {
        $event = new AddBankAccount($bankInfo);

        $this->dispatcher->dispatch('before.bankAccount.add', $event);

        return $this->hipay->bankInfosRegister($vendor, $event->getBankInfo());
    }

    /**
     * Check that the bank information is the same in the two services.
     *
     * @param VendorInterface $vendor
     * @param BankInfo $miraklBankInfo
     *
     * @return bool
     */
    protected function isBankInfosSynchronised(VendorInterface $vendor, BankInfo $miraklBankInfo)
    {
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
        $miraklData = current($this->mirakl->getVendors(null, false, array($miraklId)));
        $hipayInfo = $this->hipay->getWalletInfo($miraklData['contact_informations']['email']);
        $hipayInfo->setVatNumber($miraklData['pro_details']['VAT_number']);
        $vendor = $this->createVendor(
            $email,
            $hipayInfo->getUserAccountld(),
            $hipayInfo->getUserSpaceld(),
            $hipayInfo->getIdentified(),
            $miraklId,
            $miraklData
        );
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
        return $this->hipay->getMerchantsGroupAccounts($merchantGroupId, $pastDate);
    }

    /**
     * Control if Mirakl Setting is ok with HiPay prerequisites
     */
    public function getControlMiraklSettings($docTypes)
    {
        $this->mirakl->controlMiraklSettings($docTypes);
    }

    /**
     * get login from Mirakl Id
     *
     * @param $shopId
     * @return type
     * @throws Exception
     */
    public function getLogin($shopId)
    {
        return $this->generateLogin($this->getVendorByShopId($shopId));
    }

    /**
     * @param $shopId
     * @return mixed
     * @throws Exception
     */
    public function getVendorByShopId($shopId)
    {
        $shop = $this->mirakl->getVendors(null, null, array($shopId));

        if (empty($shop)) {
            throw new \Exception("No mirakl data for this shop ID ($shopId)");
        }

        return $shop[0];
    }

    /**
     * log vendor creation/update
     *
     * @param $miraklId
     * @param $hipayId
     * @param $login
     * @param $statusWalletAccount
     * @param $status
     * @param $message
     * @param int $nbDoc
     * @param bool $enabled
     */
    private function logVendor(
        $miraklId,
        $hipayId,
        $login,
        $statusWalletAccount,
        $status,
        $message,
        $nbDoc = 0,
        $enabled = true,
        $country = null,
        $paymentBlocked = false
    ) {
        $logVendor = $this->logVendorManager->findByMiraklId($miraklId);

        if ($logVendor !== null) {
            $logVendor->setStatusWalletAccount($statusWalletAccount);
            $logVendor->setStatus($status);
            $logVendor->setMessage($message);
            $logVendor->setEnabled($enabled);
            $logVendor->setCountry($country);
            $logVendor->setPaymentBlocked($paymentBlocked);
            $this->vendorsLogs[] = $logVendor;
        } else {
            $this->vendorsLogs[] = $this->logVendorManager->create(
                $miraklId,
                $hipayId,
                $login,
                $statusWalletAccount,
                $status,
                $message,
                $nbDoc,
                $country,
                $paymentBlocked
            );
        }
    }

    /**
     * Generate hipay login from mirakl data
     * @param type $miraklData
     * @return type
     */
    private function generateLogin($miraklData)
    {
        return 'mirakl_' . preg_replace("/[^A-Za-z0-9]/", '', $miraklData['shop_name']) . '_' . $miraklData['shop_id'];
    }

    /**
     * Retrieve ALL_PROOF_OF_BANK_ACCOUNT document for $shopId
     * @param type $shopId
     * @param type $tmpFilePath
     * @return string
     */
    private function getBankDocument($shopId, $tmpFilePath)
    {
        $allMiraklFiles = $this->mirakl->getFiles(array($shopId));
        // We only keep the file type we want
        $files = array_filter(
            $allMiraklFiles,
            function ($aFile) {
                return in_array($aFile['type'], array(Mirakl::DOCUMENT_ALL_PROOF_OF_BANK_ACCOUNT));
            }
        );

        if (!empty($files)) {
            $file = end($files);
            $tmpFile = $tmpFilePath . '/' . preg_replace("/[^A-Za-z0-9\.]/", '', $file['file_name']);
            file_put_contents($tmpFile, $this->mirakl->downloadDocuments(array($file['id'])));

            return $tmpFile;
        }

        return null;
    }


    /**
     * @param $fileName
     * @param $file
     * @param $shopId
     * @throws Exception
     */
    private function checkExtensionFile($fileName, $file, $shopId)
    {

        if (!preg_match('/^.*\.(jpg|jpeg|png|gif|pdf)$/i', $fileName)) {
            throw new Exception(
                'Document ' .
                $file['id'] .
                ' (type: ' .
                $file['type'] .
                ') for Mirakl for shop ' .
                $shopId .
                ' will not be uploaded because extension is wrong, should be jpg, jpeg, png, gif or pdf'
            );
        }
    }

    /**
     * get file corresponding to the back of a file type
     *
     * @param $fileType
     * @param $allMiraklFiles
     * @param $frontFile
     * @param $shopId
     * @return array|null
     * @throws Exception
     */
    private function getFileBack($fileType, $allMiraklFiles, $frontFile, $shopId, $tmpFilePath)
    {

        try {
            $backType = $this->getFileBackType($fileType);

            // this file type has no backs
            if (!$backType) {
                return null;
            }

            $files = array_filter(
                $allMiraklFiles,
                function ($aFile) use ($backType) {
                    return $aFile['type'] == $backType;
                }
            );

            // No back has been found in Mirakl files
            if (empty($files)) {
                return null;
            }

            $file = end($files);
            // check if extension of file is correct
            $this->checkExtensionFile($file['file_name'], $file, $shopId);
            // save file
            $tmpFile = $tmpFilePath . '/' . time() . preg_replace("/[^A-Za-z0-9\.]/", '', $file['file_name']);
            file_put_contents($tmpFile, $this->mirakl->downloadDocuments(array($file['id'])));

            return array('filePath' => $tmpFile, 'fileObject' => $file);

        } catch (Exception $e) {
            throw new Exception(
                'Document ' .
                $frontFile['id'] .
                ' (type: ' .
                $fileType .
                ') for Mirakl for shop ' .
                $shopId .
                ' will not be uploaded because file of type ' .
                $backType .
                ' not uploaded in Mirakl or uploaded with wrong extension'
            );
        }
    }

    /**
     * return back of a document type
     *
     * @param $type
     * @return bool|string
     */
    private function getFileBackType($type)
    {
        switch ($type) {
            case Mirakl::DOCUMENT_LEGAL_IDENTITY_OF_REPRESENTATIVE:
                return Mirakl::DOCUMENT_LEGAL_IDENTITY_OF_REP_REAR;
            case Mirakl::DOCUMENT_SOLE_MAN_BUS_IDENTITY:
                return Mirakl::DOCUMENT_SOLE_MAN_BUS_IDENTITY_REAR;
            default:
                return false;
        }
    }

    /**
     * Filter function for Vendors array from Mirakl
     *
     * @param $element
     * @return bool
     */
    private function filterVendors($element)
    {
        $additionnalField = array('code' => 'hipay-process', 'type' => 'BOOLEAN', 'value' => 'true');

        if (isset($element['shop_additional_fields']) &&
            in_array($additionnalField, $element['shop_additional_fields'])
        ) {
            return true;
        } else {
            $this->logger->info(
                'Shop ' .
                $element['shop_id'] .
                ' will not be processed beacause additionnal field hipay-process set to false',
                array('miraklId' => $element['shop_id'], "action" => "Wallet creation")
            );

            $this->disableVendor($element);
        }
    }

    /**
     * If Vendor exist and HIPAY_PROCESS = NO, disable it
     *
     * @param $vendorData
     */
    private function disableVendor($vendorData)
    {

        $vendor = $this->vendorManager->findByMiraklId($vendorData['shop_id']);
        $logVendor = $this->logVendorManager->findByMiraklId($vendorData['shop_id']);

        if ($vendor) {
            $this->logger->info(
                'Shop ' . $vendorData['shop_id'] . ' found in database',
                array('miraklId' => $vendorData['shop_id'], "action" => "Wallet creation")
            );
            if ($vendor->getEnabled() || $vendor->getEnabled() === null) {
                $vendor->setEnabled(false);
                $this->vendorManager->save($vendor);
                if ($logVendor !== null) {
                    $logVendor->setEnabled(false);
                    $this->logVendorManager->save($logVendor);
                }
                $this->logger->info(
                    'Shop ' . $vendorData['shop_id'] . ' disabled',
                    array('miraklId' => $vendorData['shop_id'], "action" => "Wallet creation")
                );
            } else {
                $this->logger->info(
                    'Shop ' . $vendorData['shop_id'] . ' already disabled',
                    array('miraklId' => $vendorData['shop_id'], "action" => "Wallet creation")
                );
            }
        }
    }
}
