<?php

namespace HiPay\Wallet\Mirakl\Api;

use DateTime;
use Exception;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\MerchantData;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\BankInfo;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\Transfer;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\UserAccountBasic;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\UserAccountDetails;
use HiPay\Wallet\Mirakl\Api\HiPay\ConfigurationInterface
    as HiPayConfigurationInterface;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\Identified;
use HiPay\Wallet\Mirakl\Api\Soap\SmileClient;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;

/**
 * Class HiPay
 * Make the SOAP call to the HiPay API.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class HiPay
{
    /** @var  string the hipay webservice login */
    protected $login;

    /** @var  string the hipay webservice password */
    protected $password;

    /** @var  SmileClient the user account webservice client */
    protected $userAccountClient;

    /** @var  SmileClient the transaction webservice client */
    protected $transferClient;

    /** @var string the entity given to the merchant by HiPay */
    protected $entity;

    /** @var string the locale  */
    protected $locale;

    /** @var string the timezone */
    protected $timezone;

    /**
     * Constructor.
     *
     * @param string $baseUrl
     * @param string $login
     * @param string $password
     * @param string $entity
     * @param string $locale
     * @param string $timeZone
     * @param array  $options
     */
    public function __construct(
        $baseUrl,
        $login,
        $password,
        $entity,
        $locale = 'fr_FR',
        $timeZone = 'Europe/Paris',
        $options = array()
    ) {
        $this->login = $login;
        $this->password = $password;
        $this->entity = $entity;
        $this->timezone = $timeZone;
        $this->locale = $locale;
        $defaults = array(
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'soap_version' => SOAP_1_1,
            'encoding' => 'UTF-8',
            'trace' => true
        );
        $options = array_merge($defaults, $options);
        $this->userAccountClient = new SmileClient(
            $baseUrl.'soap/user-account-v2?wsdl',
            $options
        );
        $this->transferClient = new SmileClient(
            $baseUrl.'soap/transaction?wsdl',
            $options
        );
        $this->withdrawalClient = new SmileClient(
            $baseUrl.'soap/withdrawal?wsdl',
            $options
        );
    }

    /**
     * @param HiPayConfigurationInterface $configuration
     *
     * @return HiPay
     */
    public static function factory(HiPayConfigurationInterface $configuration)
    {
        return new self(
            $configuration->getBaseUrl(),
            $configuration->getWebServiceLogin(),
            $configuration->getWebServicePassword(),
            $configuration->getEntity(),
            $configuration->getLocale(),
            $configuration->getTimezone(),
            $configuration->getOptions()
        );
    }

    /**
     * Check if given email can be used to create an HiPay wallet
     * Enforce the entity to the one given on object construction if false.
     *
     * @param string $email
     *
     * @return bool if array is empty
     *
     * @throws Exception
     */
    public function isAvailable($email)
    {
        $parameters = array('email' => $email, 'entity' => $this->entity);
        $result = $this->callSoap('isAvailable', $parameters);

        return $result['isAvailable'];
    }

    /**
     * Create an new account on HiPay wallet
     * Enforce the entity to the one given on object construction
     * Enforce the locale to the one given on object construction if false
     * Enforce the timezone to the one given on object construction if false.
     *
     * @param UserAccountBasic   $accountBasic
     * @param UserAccountDetails $accountDetails
     * @param MerchantData       $merchantData
     *
     * @return int the user account id
     *
     * @throws Exception
     */
    public function createFullUseraccount(
        UserAccountBasic $accountBasic,
        UserAccountDetails $accountDetails,
        MerchantData $merchantData
    ) {
        $accountBasic->setEntity($this->entity);

        if (!$accountBasic->getLocale()) {
            $accountBasic->setLocale($this->locale);
        }

        if (!$accountDetails->getTimeZone()) {
            $accountDetails->setTimeZone($this->timezone);
        }

        $parameters = $accountBasic->mergeIntoParameters();
        $parameters = $accountDetails->mergeIntoParameters($parameters);
        $parameters = $merchantData->mergeIntoParameters($parameters);
        $result = $this->callSoap('createFullUseraccount', $parameters);

        return $result['userAccountId'];
    }

    /**
     * Retrieve from HiPay the bank information.
     *
     * @param VendorInterface $vendor
     *
     * @return BankInfo if array is empty
     *
     * @throws Exception
     */
    public function bankInfosCheck(VendorInterface $vendor)
    {
        $parameters = $this->mergeSubAccountParameters($vendor);
        $bankInfo = new BankInfo();

        return $bankInfo->setHiPayData(
            $this->callSoap('bankInfosCheck', $parameters)
        );
    }

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
    public function bankInfosStatus(VendorInterface $vendor)
    {
        $parameters = $this->mergeSubAccountParameters($vendor);
        $parameters['locale'] = 'en_GB';
        $result = $this->callSoap('bankInfosStatus', $parameters);

        return $result['status'];
    }

    /**
     * Create a bank account in HiPay.
     *
     * @param VendorInterface $vendor
     * @param BankInfo        $bankInfo
     *
     * @return array|bool if array is empty
     *
     * @throws Exception
     */
    public function bankInfoRegister(
        VendorInterface $vendor,
        BankInfo $bankInfo
    ) {
        $parameters = $this->mergeSubAccountParameters($vendor);
        $parameters = $bankInfo->mergeIntoParameters($parameters);

        return $this->callSoap('bankInfosRegister', $parameters);
    }

    /**
     * Return the hipay account id.
     *
     * @param string $email
     *
     * @return array|bool if array is empty
     *
     * @throws Exception
     */
    public function getWalletId($email)
    {
        $parameters = array('accountLogin' => $email);
        $result = $this->callSoap('getAccountInfos', $parameters);

        return $result['userAccountId'];
    }

    /**
     * Return the identified status of the account.
     *
     * @param VendorInterface $vendor
     *
     * @return bool
     */
    public function isIdentified(VendorInterface $vendor)
    {
        $parameters = array('accountId' => $vendor->getHiPayId());
        $result = $this->callSoap('getAccountInfos', $parameters);

        return $result['identified'] == Identified::YES ? true : false;
    }

    /**
     * Return the account information.
     *
     * @param VendorInterface $vendor
     *
     * @return int
     *
     * @throws Exception
     */
    public function getBalance(VendorInterface $vendor)
    {
        $parameters = $this->mergeSubAccountParameters($vendor);
        $result = $this->callSoap('getBalance', $parameters);

        return $result['balances']->item[0]->balance;
    }

    /**
     * Make a transfer.
     *
     * @param Transfer        $transfer
     * @param VendorInterface $vendor
     *
     * @return array
     *
     * @throws Exception
     */
    public function transfer(Transfer $transfer, VendorInterface $vendor = null)
    {
        if (!$transfer->getEntity()) {
            $transfer->setEntity($this->entity);
        }
        $parameters = $transfer->mergeIntoParameters();
        if ($vendor) {
            $parameters = $this->mergeSubAccountParameters($vendor);
        }
        $result = $this->callSoap('direct', $parameters);

        return $result['transactionId'];
    }

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
    public function withdraw(VendorInterface $vendor, $amount, $label)
    {
        $parameters = array('amount' => $amount, 'label' => $label);
        $parameters = $this->mergeSubAccountParameters($vendor, $parameters);
        $result = $this->callSoap('create', $parameters);

        return $result['transactionPublicId'];
    }

    /**
     * Return the mandatory fields bank info fields for a specific vendor.
     *
     * @param $country
     */
    public function getBankInfoFields($country = 'FR')
    {
        $parameters = array('locale' => 'en_GB', 'country' => $country);
        $result = $this->callSoap('bankInfosFields', $parameters);

        return $result['fields'];
    }

    /**
     * @param int $merchantGroupId the id given to HiPay corresponding to the entity
     * @param DateTime $pastDate the maximum wallet creation date
     *
     * @return array
     */
    public function getMerchantGroupAccounts($merchantGroupId, DateTime $pastDate)
    {
        $parameters = array('merchantGroupId' => $merchantGroupId, 'pastDate' => $pastDate->format('Y-m-d'));
        $data = $this->callSoap('getMerchantsGroupAccounts', $parameters);
        $result = array();
        foreach ($data['dataMerchantsGroupAccounts'] as $item) {
            $result = (array) $item;
        }
        return $result;
    }

    /**
     * Add the api login parameters to the parameters.
     *
     * @param array $parameters the call parameters
     *
     * @return array
     */
    protected function mergeLoginParameters(array $parameters = array())
    {
        $parameters = $parameters + array(
                'wsLogin' => $this->login,
                'wsPassword' => $this->password,
            );

        return $parameters;
    }

    /**
     * Add sub account informations.
     *
     * @param array           $parameters the parameters array to add the info to
     * @param VendorInterface $vendor     the vendor from which subAccount info is fetched from
     *
     * @return array
     */
    protected function mergeSubAccountParameters(
        VendorInterface $vendor,
        $parameters = array()
    ) {
        $parameters += array(
            'wsSubAccountLogin' => $vendor->getEmail(),
            'wsSubAccountId' => $vendor->getHiPayId(),
        );

        return $parameters;
    }

    /**
     * Return the correct soap client to use.
     *
     * @param string $name the called method name
     *
     * @return SmileClient
     */
    protected function getClient($name)
    {
        switch ($name) {
            case 'direct':
                return $this->transferClient;
            case 'create':
                return $this->withdrawalClient;
            default:
                return $this->userAccountClient;
        }
    }

    /**
     * @param string $name
     * @param array  $parameters
     *
     * @return array
     *
     * @throws Exception
     * @throws \SoapFault
     */
    private function callSoap($name, array $parameters)
    {
        $parameters = $this->mergeLoginParameters($parameters);

        //Make the call
        $response = $this->getClient($name)->$name(
            array('parameters' => $parameters)
        );

        //Parse the response
        $response = (array) $response;
        $response = (array) current($response);
        if ($response['code'] > 0) {
            throw new Exception(
                "There was an error with the soap call $name".PHP_EOL.
                $response['code'].' : '.$response['description'].PHP_EOL.
                'Date : ' . date('Y-m-d H:i:s') . PHP_EOL .
                'Parameters :'. PHP_EOL .
                print_r($parameters, true),
                $response['code']
            );
        } else {
            unset($response['code']);
            unset($response['description']);
        }

        return $response ?: true;
    }
}
