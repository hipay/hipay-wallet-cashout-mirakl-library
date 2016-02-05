<?php
/**
 * File ApiInterface.php
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
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\BankInfo;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\MerchantData;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\Transfer;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\UserAccountBasic;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\UserAccountDetails;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;

/**
 * Class HiPay
 * Make the SOAP call to the HiPay API.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
interface ApiInterface
{
    /**
     * Check if given email can be used to create an HiPay wallet
     * Enforce the entity to the one given on object construction if false.
     *
     * @param string $email
     * @param bool $entity
     *
     * @return bool if array is empty
     *
     * @throws Exception
     */
    public function isAvailable($email, $entity = false);

    /**
     * Create an new account on HiPay wallet
     * Enforce the entity to the one given on object construction
     * Enforce the locale to the one given on object construction if false
     * Enforce the timezone to the one given on object construction if false.
     *
     * @param UserAccountBasic $accountBasic
     * @param UserAccountDetails $accountDetails
     * @param MerchantData $merchantData
     *
     * @return int the user account id
     *
     * @throws Exception
     */
    public function createFullUseraccount(
        UserAccountBasic $accountBasic,
        UserAccountDetails $accountDetails,
        MerchantData $merchantData
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
     *
     * @return string
     *
     * @throws Exception
     */
    public function bankInfosStatus(VendorInterface $vendor);

    /**
     * Create a bank account in HiPay.
     *
     * @param VendorInterface $vendor
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
     * @param string $email
     *
     * @return array|bool if array is empty
     *
     * @throws Exception
     */
    public function getWalletId($email);

    /**
     * Return the identified status of the account.
     *
     * @param VendorInterface $vendor
     *
     * @return bool
     */
    public function isIdentified(VendorInterface $vendor);

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
     * Make a transfer.
     *
     * @param Transfer $transfer
     * @param VendorInterface $vendor
     *
     * @return array
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
     *
     * @return array
     *
     * @throws Exception
     */
    public function withdraw(VendorInterface $vendor, $amount, $label);

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
    public function getMerchantGroupAccounts($merchantGroupId, DateTime $pastDate);
}