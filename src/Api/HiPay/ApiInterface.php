<?php
/**
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace HiPay\Wallet\Mirakl\Api\HiPay;

use DateTime;
use Exception;
use HiPay\Wallet\Mirakl\Api\HiPay;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\MerchantData;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\Transfer;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\UserAccountBasic;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\UserAccountDetails;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Rest\BankInfo;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Rest\UserAccount;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use HiPay\Wallet\Mirakl\Api\HiPay\Wallet\AccountInfo;

/**
 * Make the SOAP & REST call to the HiPay API.
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
interface ApiInterface
{
    /**
     * Check if given email can be used to create an HiPay wallet
     * Enforce the entity to the one given on object construction if false.
     *
     * @param string $vendorData
     * @param bool $entity
     *
     * @return bool if array is empty
     *
     * @throws Exception
     */
    public function isAvailable($vendorData, $entity = false);

    /**
     * Create an new account on HiPay wallet
     * Enforce the entity to the one given on object construction
     * Enforce the locale to the one given on object construction if false
     * Enforce the timezone to the one given on object construction if false.
     *
     * @param UserAccount $userAccount
     * @param MerchantData $merchantData
     *
     * @return int the user account id
     *
     * @throws Exception
     */
    public function createFullUseraccountV2(
        UserAccount $userAccount
    );

    /**
     * Retrieve from HiPay the bank information.
     *
     * @param VendorInterface $vendor
     *
     * @return BankInfo if array is empty
     *
     * @throws Exception
     */
    public function bankInfosCheck(VendorInterface $vendor);

    /**
     * Retrieve from HiPay the bank account status in english
     * To be checked against the constant defined in
     * HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\BankInfo.
     *
     * @param VendorInterface $vendor
     * @param UserAccount $userAccount
     *
     * @return string
     *
     * @throws Exception
     */
    public function bankInfosStatus(VendorInterface $vendor);

    /**
     * Create a bank account in HiPay.
     *
     * @param BankInfo $bankInfo
     *
     * @return array|bool if array is empty
     *
     * @throws Exception
     */
    public function bankInfosRegister(VendorInterface $vendor, BankInfo $bankInfo);

    /**
     * Return the hipay account id.
     *
     * @param VendorInterface $vendor
     *
     * @return array|bool if array is empty
     *
     * @throws Exception
     */
    public function getWalletId(VendorInterface $vendor);

    /**
     * Return the hipay account information.
     *
     * @param UserAccount $userAccount
     *
     * @return AccountInfo HiPay Wallet account information
     *
     * @throws Exception
     */
    public function getWalletInfo(UserAccount $userAccount, $vendor);

    /**
     * Return the identified status of the account.
     *
     * @param VendorInterface $vendor
     *
     * @return bool
     */
    public function isIdentified(VendorInterface $vendor);

    /**
     * Return the identified status of the account.
     *
     * @param VendorInterface $vendor
     *
     * @return array
     */
    public function getAccountInfos(UserAccount $userAccount);

    /**
     * Return the identified status of the account.
     *
     * @param $account_id
     *
     * @return array
     */
    public function getAccountHiPay($account_id);

    /**
     * Return the account information.
     *
     * @param VendorInterface $vendor
     *
     * @return int
     *
     * @throws Exception
     */
    public function getBalance(VendorInterface $vendor);

    /**
     * Get transaction data
     *
     * @param $transactionId
     * @param null $accountId
     * @return mixed
     *
     * @throws Exception
     */
    public function getTransaction($transactionId, $accountId);

    /**
     * Make a transfer.
     *
     * @param Transfer $transfer
     * @param VendorInterface $vendor
     *
     * @return int
     *
     * @throws Exception
     */
    public function transfer(Transfer $transfer, VendorInterface $vendor = null);

    /**
     * Make a withdrawal.
     *
     * @param VendorInterface $vendor
     * @param $amount
     * @param $label
     * @param $merchantUniqueId
     *
     * @return int
     *
     * @throws Exception
     */
    public function withdraw(VendorInterface $vendor, $amount, $label, $merchantUniqueId = null);

    /**
     * Return the mandatory fields bank info fields for a specific vendor.
     *
     * @param $country
     */
    public function getBankInfoFields($country = 'FR');

    /**
     * @param int $merchantGroupId the id given to HiPay corresponding to the entity
     * @param DateTime $pastDate the maximum wallet creation date
     *
     * @return array
     */
    public function getMerchantsGroupAccounts($merchantGroupId, DateTime $pastDate);

    /**
     *
     * @param type $userSpaceId
     * @param type $accountId
     * @param type $documentType
     * @param type $fileName
     * @param DateTime $validityDate
     */
    public function uploadDocument($userSpaceId, $accountId, $documentType, $fileName, \DateTime $validityDate = null);

    /**
     * @param $hipayId
     * @return mixed
     */
    public function isWalletExist($hipayId);

    /**
     * @param $email
     * @param VendorInterface $vendor
     * @return mixed
     */
    public function updateEmail($email, VendorInterface $vendor);
}
