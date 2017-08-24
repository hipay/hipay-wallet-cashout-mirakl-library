<?php

namespace HiPay\Wallet\Mirakl\Api;

use DateTime;
use Exception;
use Guzzle\Http\Message\PostFile;
use HiPay\Wallet\Mirakl\Api\HiPay\ApiInterface;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Rest\BankInfo;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Rest\UserAccount;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\Transfer;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\UserAccountBasic;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\UserAccountDetail;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\Identified;
use HiPay\Wallet\Mirakl\Api\HiPay\Wallet\AccountInfo;
use HiPay\Wallet\Mirakl\Api\Soap\SmileClient;
use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Guzzle\Service\Client;
use Guzzle\Service\Description\ServiceDescription;
use Guzzle\Http\Exception\ClientErrorResponseException;

/**
 * Make the SOAP & REST call to the HiPay API.
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
class HiPay implements ApiInterface
{
    /** @var  string the hipay webservice login */
    protected $login;

    /** @var  string the hipay webservice password */
    protected $password;

    /** @var  SmileClient the user account webservice client */
    protected $userAccountClient;

    /** @var  SmileClient the transaction webservice client */
    protected $transferClient;

    /** @var  SmileClient the withdrawal webservice client */
    protected $withdrawalClient;

    /** @var string the entity given to the merchant by HiPay */
    protected $entity;

    /** @var string the locale  */
    protected $locale;

    /** @var string the timezone */
    protected $timezone;

    /** @var Client guzzle client used for the request */
    protected $restClient;

    // For all types of businesses
    CONST DOCUMENT_ALL_PROOF_OF_BANK_ACCOUNT = 6;

    // For individual only
    CONST DOCUMENT_INDIVIDUAL_IDENTITY = 1;
    CONST DOCUMENT_INDIVIDUAL_PROOF_OF_ADDRESS = 2;

    // For legal entity businesses only
    CONST DOCUMENT_LEGAL_IDENTITY_OF_REPRESENTATIVE = 3;
    CONST DOCUMENT_LEGAL_PROOF_OF_REGISTRATION_NUMBER = 4;
    CONST DOCUMENT_LEGAL_ARTICLES_DISTR_OF_POWERS = 5;

    // For one man businesses only
    CONST DOCUMENT_SOLE_MAN_BUS_IDENTITY = 7;
    CONST DOCUMENT_SOLE_MAN_BUS_PROOF_OF_REG_NUMBER = 8;
    CONST DOCUMENT_SOLE_MAN_BUS_PROOF_OF_TAX_STATUS = 9;

    // For log separator for markdown
    CONST SEPARMKD = '_*';
    CONST LINEMKD  = "\r";

    /**
     * Constructor.
     *
     * @param string $baseSoapUrl
     * @param string $baseRestUrl
     * @param string $login
     * @param string $password
     * @param string $entity
     * @param string $locale
     * @param string $timeZone
     * @param array  $options
     */
    public function __construct(
        $baseSoapUrl,
        $baseRestUrl,
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
        /*$this->userAccountClient = new SmileClient(
            $baseSoapUrl.'/soap/user-account-v2?wsdl',
            $options
        );*/
        $this->transferClient = new SmileClient(
            $baseSoapUrl.'/soap/transfer?wsdl',
            $options
        );
        $this->withdrawalClient = new SmileClient(
            $baseSoapUrl.'/soap/withdrawal?wsdl',
            $options
        );

        $this->restClient = new Client();

        $description = ServiceDescription::factory(__DIR__.'../../../data/api/hipay.json');
        $description->setBaseUrl($baseRestUrl);
        $this->restClient->setDescription($description);
    }

    public function uploadDocument(
        $userSpaceId,
        $accountId,
        $documentType,
        $fileName,
        \DateTime $validityDate = null
    )
    {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-user',
            $this->login
        );

        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-pw',
            $this->password
        );

        if( !is_null($accountId)) {
            $this->restClient->getConfig()->setPath(
                'request.options/headers/php-auth-subaccount-id',
                $accountId
            );
        }

        $command = $this->restClient->getCommand(
            'UploadDocument',
            array(
                'userSpaceId' => $userSpaceId,
                'validityDate' => $validityDate,
                'type' => $documentType,
                'file' => new PostFile('file', $fileName)
            )
        );

        return $this->restClient->execute($command);
    }

    public function getDocuments(VendorInterface $vendor){

        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-user',
            $this->login
        );

        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-pw',
            $this->password
        );

        if( !empty($vendor->getHiPayId())) {
            $this->restClient->getConfig()->setPath(
                'request.options/headers/php-auth-subaccount-id',
                $vendor->getHiPayId()
            );
        }

        $command = $this->restClient->getCommand('GetDocuments',array());

        $result = $this->restClient->execute($command);

        return $result['documents'];
    }

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
    public function isAvailable($email, $entity = false)
    {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-user',
            $this->login
        );

        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-pw',
            $this->password
        );

        $parameters = array(
            'userEmail' => $email,
            'entity' => $entity ?: $this->entity
        );
        $command = $this->restClient->getCommand(
            'IsAvailable',
            $parameters
        );

        $result = $this->restClient->execute($command);

        return $result['is_available'];
    }

    /**
     * Create an new account on HiPay wallet
     * Enforce the entity to the one given on object construction
     * Enforce the locale to the one given on object construction if false
     * Enforce the timezone to the one given on object construction if false.
     *
     * @param UserAccountBasic      $accountBasic
     * @param UserAccountDetails    $accountDetails
     * @param MerchantDataRest      $merchantData
     *
     * @return AccountInfo The HiPay Wallet account information
     *
     * @throws Exception
     */
    public function createFullUseraccountV2(
        UserAccount $userAccount
    ) {

        if (!$userAccount->getLocale()) {
            $userAccount->setLocale($this->locale);
        }

        if (!$userAccount->getTimeZone()) {
            $userAccount->setTimeZone($this->timezone);
        }

        if (!$userAccount->getEntityCode()) {
            $userAccount->setEntityCode($this->entity);
        }

        if(!$userAccount->getCredential()) {
            $userAccount->setCredential(
                array(
                    'wslogin' => $this->login,
                    'wspassword' => $this->password,
                )
            );
        }

        $parameters = $userAccount->mergeIntoParameters();

        $command = $this->restClient->getCommand(
            'RegisterNewAccount',
            $parameters['userAccount']
        );

        $result = $this->restClient->execute($command);

        return new AccountInfo($result['account_id'], $result['user_space_id'], $result['status'] === Identified::YES, $result['callback_salt']);
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
        $bankInfo = new BankInfo();

        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-user',
            $this->login
        );

        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-pw',
            $this->password
        );

        if( !is_null($vendor->getHiPayId())) {
            $this->restClient->getConfig()->setPath(
                'request.options/headers/php-auth-subaccount-id',
                $vendor->getHiPayId()
            );
        }

        $parameters['locale'] = 'en_GB';

        $command = $this->restClient->getCommand(
            'getBankInfo',
            $parameters
        );
        $result = $this->restClient->execute($command);

        return $bankInfo->setHiPayData($result);
    }

    /**
     * Retrieve from HiPay the bank account status in english
     * To be checked against the constant defined in
     * HiPay\Wallet\Mirakl\Api\HiPay\Model\Status\BankInfo.
     *
     * @param UserAccount $userAccount
     *
     * @return string
     *
     * @throws Exception
     */
    public function bankInfosStatus(
        VendorInterface $vendor
    ) {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-user',
            $this->login
        );

        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-pw',
            $this->password
        );

        if( !empty($vendor->getHiPayId())) {
            $this->restClient->getConfig()->setPath(
                'request.options/headers/php-auth-subaccount-id',
                $vendor->getHiPayId()
            );
        }

        $parameters['locale'] = 'en_GB';

        $command = $this->restClient->getCommand(
            'getBankInfo',
            $parameters
        );
        $result = $this->restClient->execute($command);

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
    public function bankInfosRegister(
        VendorInterface $vendor,
        BankInfo $bankInfo
    ) {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-user',
            $this->login
        );

        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-pw',
            $this->password
        );

        if( !is_null($vendor->getHiPayId())) {
            $this->restClient->getConfig()->setPath(
                'request.options/headers/php-auth-subaccount-id',
                $vendor->getHiPayId()
            );
        }

        $parameters = $bankInfo->mergeIntoParameters();

        $command = $this->restClient->getCommand(
            'RegisterBankInfo',
            $parameters
        );
        $result = $this->restClient->execute($command);

        return $result;
    }

    /**
     * Return the hipay account id.
     *
     * @param VendorInterface $vendor
     *
     * @return array|bool if array is empty
     *
     * @throws Exception
     */
    public function getWalletId(
        VendorInterface $vendor
    )
    {
        $result = $this->getAccountInfos($vendor);

        return $result['user_account_id'];
    }

    /**
     * Return the hipay account information.
     *
     * @param VendorInterface $vendor
     *
     * @return AccountInfo HiPay Wallet account information
     *
     * @throws Exception
     */
    public function getWalletInfo(
        UserAccount $userAccount
    )
    {
        $result = $this->getAccountInfos($userAccount);

        return new AccountInfo($result['user_account_id'], $result['user_space_id'], $result['identified'] === 1, $result['callback_salt']);
    }

    /**
     * Return the identified status of the account.
     *
     * @param VendorInterface $vendor
     * @return bool
     * @throws Exception
     */
    public function isIdentified(
        VendorInterface $vendor
    )
    {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-user',
            $this->login
        );

        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-pw',
            $this->password
        );

        if( !empty($vendor->getHiPayId())) {
            $this->restClient->getConfig()->setPath(
                'request.options/headers/php-auth-subaccount-id',
                $vendor->getHiPayId()
            );
        }

        $command = $this->restClient->getCommand(
            'GetUserAccount',
            array()
        );

        $result = $this->restClient->execute($command);

        return $result['identified'] == 1 ? true : false;
    }

    /**
     * Return various information about a wallet
     *
     * @param VendorInterface $vendor
     *
     * @return array
     *
     * @throws Exception
     */
    public function getAccountInfos(
        UserAccount $userAccount
    )
    {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-user',
            $this->login
        );

        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-pw',
            $this->password
        );

        if( !empty($userAccount->getLogin())) {
            $this->restClient->getConfig()->setPath(
                'request.options/headers/php-auth-subaccount-login',
                $userAccount->getLogin()
            );
        }

        $command = $this->restClient->getCommand(
            'GetUserAccount',
            array()
        );

        try {
            $result = $this->restClient->execute($command);
        } catch (ClientErrorResponseException $e) {
            if ($e->getResponse()->getStatusCode() == '401') {
                /** retry with email in php-auth-subaccount-login */
                $this->restClient->getConfig()->setPath(
                    'request.options/headers/php-auth-subaccount-login',
                    strtolower($userAccount->getEmail())
                );

                $command = $this->restClient->getCommand(
                    'GetUserAccount',
                    array()
                );
                $result = $this->restClient->execute($command);
            }
        }
        return $result;
    }

    /**
     * Return various information about a wallet
     *
     * @param VendorInterface $vendor
     *
     * @return array
     *
     * @throws Exception
     */
    public function getAccountHiPay(
        $account_id
    )
    {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-user',
            $this->login
        );

        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-pw',
            $this->password
        );

        if( !empty($input['account_id'])) {
            $this->restClient->getConfig()->setPath(
                'request.options/headers/php-auth-subaccount-login',
                $account_id
            );
        }

        $command = $this->restClient->getCommand(
            'GetUserAccount',
            array()
        );

        $result = $this->restClient->execute($command);

        return $result;
    }

    /**
     * Return the wallet current balance
     *
     * @param VendorInterface $vendor
     *
     * @return int
     *
     * @throws Exception
     */
    public function getBalance(VendorInterface $vendor)
    {
        echo 'account_id' . $vendor->getHiPayId()."\r\n";

        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-user',
            $this->login
        );

        $this->restClient->getConfig()->setPath(
            'request.options/headers/php-auth-pw',
            $this->password
        );

        if (!is_null($vendor->getHiPayId())) {
            $this->restClient->getConfig()->setPath(
                'request.options/headers/php-auth-subaccount-id',
                $vendor->getHiPayId()
            );
        } else if (!is_null($vendor->getLogin())) {
            $this->restClient->getConfig()->setPath(
                'request.options/headers/php-auth-subaccount-login',
                $vendor->getLogin()
            );
        }

        $command = $this->restClient->getCommand(
            'GetBalance',
            array()
        );

        try {
            $result = $this->restClient->execute($command);
        } catch (ClientErrorResponseException $e) {
            /** retro compatible if old account */
            if ($e->getResponse()->getStatusCode() == '401') {
                /** retry with email in php-auth-subaccount-login */
                $this->restClient->getConfig()->setPath(
                    'request.options/headers/php-auth-subaccount-login',
                    $vendor->getEmail()
                );
                $command = $this->restClient->getCommand(
                    'GetBalance',
                    array()
                );
                $result = $this->restClient->execute($command);
            }
        }
        return $result['balances'][0]['balance'];
    }
    /**
     * Make a transfer.
     *
     * @param Transfer        $transfer
     * @param VendorInterface $vendor
     *
     * @return int
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
     * @return int
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
    public function getMerchantsGroupAccounts($merchantGroupId, DateTime $pastDate)
    {
        $parameters = array('merchantGroupId' => $merchantGroupId, 'pastDate' => $pastDate->format('Y-m-d'));
        $data = $this->callSoap('getMerchantsGroupAccounts', $parameters);
        $result = array();
        foreach ($data['dataMerchantsGroupAccounts']->item as $index => $item) {
            $result[] = (array) $item;
        }
        return $result;
    }

    /**
     * Add the api REST login parameters to the parameters.
     *
     * @param array $parameters the call parameters
     *
     * @return array
     */
    protected function mergeLoginParameters(array $parameters = array())
    {
        $parameters = $parameters + array(
                'credential' => array(
                    'wslogin' => $this->login,
                    'wspassword' => $this->password,
                )
            );

        return $parameters;
    }
    /**
     * Add the api SOAP login parameters to the parameters.
     *
     * @param array $parameters the call parameters
     *
     * @return array
     */
    protected function mergeLoginParametersSoap(array $parameters = array())
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
        $vendor,
        $parameters = array()
    ) {
        $parameters += array(
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
                return true;//$this->userAccountClient;
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
    protected function callSoap($name, array $parameters)
    {
        $parameters = $this->mergeLoginParametersSoap($parameters);
        try{
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
        }catch(Exception $e){
            echo $e->getMessage();
        }

        return $response ?: true;
    }
}
