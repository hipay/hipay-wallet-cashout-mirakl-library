<?php
namespace Hipay\MiraklConnector\Api;

use Exception;
use Hipay\MiraklConnector\Api\Hipay\Model\Soap\MerchantData;
use Hipay\MiraklConnector\Api\Hipay\Model\Soap\BankInfo;
use Hipay\MiraklConnector\Api\Hipay\Model\Soap\Transfer;
use Hipay\MiraklConnector\Api\Hipay\Model\Soap\UserAccountBasic;
use Hipay\MiraklConnector\Api\Hipay\Model\Soap\UserAccountDetails;
use Hipay\MiraklConnector\Api\Hipay\ConfigurationInterface
    as HipayConfigurationInterface;
use Hipay\MiraklConnector\Api\Soap\SmileClient;
use Hipay\MiraklConnector\Vendor\Model\VendorInterface;

/**
 * Class Hipay
 * Make the SOAP call to the Hipay API
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Hipay
{
    /** @var  string the hipay webservice login */
    protected $login;

    /** @var  string the hipay webservice password */
    protected $password;

    /** @var  SmileClient the user account webservice client */
    protected $userAccountClient;

    /** @var  SmileClient the transaction webservice client */
    protected $transferClient;

    /** @var string the entity given to the merchant by Hipay */
    protected $entity;

    /** @var string the locale  */
    protected $locale;

    /** @var string the timezone */
    protected $timezone;

    /**
     * Constructor
     *
     * @param string $baseUrl
     * @param string $login
     * @param string $password
     * @param string $entity
     *
     * @param string $locale
     * @param string $timeZone
     * @param array $options
     */
    public function __construct(
        $baseUrl,
        $login,
        $password,
        $entity,
        $locale = "fr_FR",
        $timeZone = "Europe/Paris",
        $options = array()
    )
    {
        $this->login = $login;
        $this->password = $password;
        $this->entity = $entity;
        $this->timezone = $timeZone;
        $this->locale = $locale;
        $this->userAccountClient = new SmileClient(
            $baseUrl . 'soap/user-account-v2?wsdl', $options
        );
        $this->transferClient = new SmileClient(
            $baseUrl . 'soap/transaction?wsdl', $options
        );
        $this->withdrawalClient = new SmileClient(
            $baseUrl . 'soap/withdrawal?wsdl', $options
        );
    }

    /**
     * @param HipayConfigurationInterface $configuration
     *
     * @return Hipay
     */
    public static function factory (HipayConfigurationInterface $configuration)
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
     * Check if given email can be used to create an Hipay wallet
     * Enforce the entity to the one given on object construction if false
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
        $result =  $this->callSoap("isAvailable", $parameters);
        return $result['isAvailable'];
    }

    /**
     * Create an new account on Hipay wallet
     * Enforce the entity to the one given on object construction
     * Enforce the locale to the one given on object construction if false
     * Enforce the timezone to the one given on object construction if false
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
    )
    {
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
        $result = $this->callSoap("createFullUseraccount", $parameters);
        return $result['userAccountld'];
    }

    /**
     * Retrieve from Hipay the bank information
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
        return $bankInfo->setHipayData(
            $this->callSoap("bankInfosCheck", $parameters)
        );
    }

    /**
     * Retrieve from Hipay the bank account status in english
     * To be checked against the constant defined in
     * Hipay\MiraklConnector\Api\Hipay\Status\BankInfo
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
        $result = $this->callSoap("bankInfosStatus", $parameters);
        return $result['status'];
    }

    /**
     * Create a bank account in Hipay
     *
     * @param VendorInterface $vendor
     * @param BankInfo $bankInfo
     *
     * @return array|bool if array is empty
     *
     * @throws Exception
     */
    public function bankInfoRegister(
        VendorInterface $vendor,
        BankInfo $bankInfo
    )
    {
        $parameters = $this->mergeSubAccountParameters($vendor);
        $parameters = $bankInfo->mergeIntoParameters($parameters);
        return $this->callSoap("bankInfosRegister", $parameters);
    }

    /**
     * Return the hipay account id
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
        $result = $this->callSoap("getAccountInfos", $parameters);
        return $result['accountId'];
    }

    /**
     * Return the identified status of the account
     *
     * @param $hipayId
     *
     * @return boolean
     */
    public function isIdentified($hipayId)
    {
        $parameters = array('accountId' => $hipayId);
        $result = $this->callSoap("getAccountInfos", $parameters);
        return $result['identified'] == 'yes' ? true : false;
    }

    /**
     * Return the account information
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
        $result = $this->callSoap("getBalance", $parameters);
        return $result['balance'];
    }

    /**
     * Make a transfer
     *
     * @param Transfer $transfer
     * @param VendorInterface $vendor
     *
     * @return array
     * @throws Exception
     */
    public function transfer(Transfer $transfer, VendorInterface $vendor = null)
    {
        $parameters = $transfer->mergeIntoParameters();
        if ($vendor) {
            $parameters = $this->mergeSubAccountParameters($vendor);
        }
        $result = $this->callSoap("direct", $parameters);
        return $result['transactionId'];
    }

    /**
     * Make a withdrawal
     *
     * @param VendorInterface $vendor
     * @param $amount
     * @param $label
     * @return array
     * @throws Exception
     */
    public function withdraw(VendorInterface $vendor, $amount, $label)
    {
        $parameters = array('amount' => $amount, 'label' => $label);
        $parameters = $this->mergeSubAccountParameters($vendor, $parameters);
        $result = $this->callSoap("create", $parameters);
        return $result['transactionPublicId'];
    }
    /**
     * Add the api login parameters to the parameters
     *
     * @param array $parameters the call parameters
     *
     * @return array
     */
    protected function mergeLoginParameters(array $parameters = array())
    {
        $parameters = $parameters + array(
                'wsLogin' => $this->login,
                'wsPassword' => $this->password
            );
        return $parameters;
    }

    /**
     * Add sub account informations
     *
     * @param array $parameters the parameters array to add the info to
     * @param VendorInterface $vendor the vendor
     * from which subaccount info is fetched from
     *
     * @return array
     */
    protected function mergeSubAccountParameters(
        VendorInterface $vendor,
        $parameters = array()
    )
    {
        $parameters += array(
            'wsSubAccountLogin' => $vendor->getEmail(),
            'wsSubAccountId' => $vendor->getHipayId()
        );
        return $parameters;
    }

    /**
     * Return the correct soap client to use
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
     * @param array $parameters
     * @return array
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
                "There was an error with the soap call $name\n" .
                $response['code'] . ":" . $response['description'] . "\n" .
                "Parameters : \n" .
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