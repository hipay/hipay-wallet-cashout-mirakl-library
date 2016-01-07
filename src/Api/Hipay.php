<?php
namespace Hipay\MiraklConnector\Api;

use Hipay\MiraklConnector\Api\Hipay\Model\BankInfo;
use Hipay\MiraklConnector\Api\Hipay\Model\MerchantData;
use Hipay\MiraklConnector\Api\Hipay\Model\UserAccountBasic;
use Hipay\MiraklConnector\Api\Hipay\Model\UserAccountDetails;
use Hipay\MiraklConnector\Api\Soap\SmileClient;
use Hipay\MiraklConnector\Vendor\VendorInterface;

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
    protected $transactionClient;

    /** @var string the entity given to the merchant by Hipay */
    protected $entity;
    /**
     * Constructor
     *
     * @param string $baseUrl
     * @param string $login
     * @param string $password
     * @param string $entity
     *
     * @param array $options
     */
    public function __construct($baseUrl, $login, $password, $entity, $options)
    {
        $this->login = $login;
        $this->password = $password;
        $this->entity = $entity;
        $this->userAccountClient = new SmileClient(
            $baseUrl . 'soap/user-account-v2?wsdl', $options
        );
        $this->transactionClient = new SmileClient(
            $baseUrl . 'soap/transaction?wsdl', $options
        );
    }

    /**
     * Check if given email can be used to create an Hipay wallet
     * Enforce the entity to the one configured with the one given to the constructor
     *
     * @param string $email
     *
     * Will be replaced by the entity given on construction if false
     *
     * @return array|bool if array is empty
     *
     * @throws \Exception
     */
    public function isAvailable($email)
    {
        $parameters = array('email' => $email, 'entity' => $this->entity);
        return $this->callSoap("isAvailable", $parameters);
    }

    /**
     * Create an new account on Hipay wallet
     * Enforce the entity to the one configured with the one given to the constructor
     *
     * @param UserAccountBasic $accountBasic
     * @param UserAccountDetails $accountDetails
     * @param MerchantData $merchantData
     *
     * @return array|bool if array is empty
     *
     * @throws \Exception
     */
    public function createFullUseraccount(
        UserAccountBasic $accountBasic,
        UserAccountDetails $accountDetails,
        MerchantData $merchantData
    )
    {
        $accountBasic->setEntity($this->entity);

        $parameters = $accountBasic->mergeIntoParameters();
        $parameters = $accountDetails->mergeIntoParameters($parameters);
        $parameters = $merchantData->mergeIntoParameters($parameters);
        return $this->callSoap("createFullUseraccount", $parameters);
    }

    /**
     * Retrieve from Hipay the bank information
     *
     * @param VendorInterface $vendor
     *
     * @return array|bool if array is empty
     *
     * @throws \Exception
     */
    public function bankInfosCheck(VendorInterface $vendor)
    {
        $parameters = $this->mergeSubAccountParameters($vendor);
        return $this->callSoap("bankInfosCheck", $parameters);
    }

    /**
     * Retrieve from Hipay the bank account status
     *
     * @param VendorInterface $vendor
     * @param $locale
     *
     * @return array|bool if array is empty
     *
     * @throws \Exception
     */
    public function bankInfosStatus(VendorInterface $vendor, $locale)
    {
        $parameters = $this->mergeSubAccountParameters($vendor);
        $parameters['locale'] = $locale;
        return $this->callSoap("bankInfosStatus", $parameters);
    }

    /**
     * Create a bank account in Hipay
     *
     * @param VendorInterface $vendor
     * @param BankInfo $bankInfo
     *
     * @return array|bool if array is empty
     *
     * @throws \Exception
     */
    public function bankInfoRegister(
        VendorInterface $vendor,
        BankInfo $bankInfo
    )
    {
        $parameters = $this->mergeSubAccountParameters($vendor);
        $parameters = $bankInfo->mergeIntoParameters($parameters);
        return $this->callSoap("bankInfoRegister", $parameters);
    }

    /**
     * Return the account information
     *
     * @param VendorInterface $vendor
     *
     * @return array|bool if array is empty
     *
     * @throws \Exception
     */
    public function getAccountInfos(VendorInterface $vendor)
    {
        $parameters = $this->mergeSubAccountParameters($vendor);
        return $this->callSoap("getAccountInfos", $parameters);
    }

    /**
     * Return the account information
     *
     * @param VendorInterface $vendor
     *
     * @return array|bool if array is empty
     *
     * @throws \Exception
     */
    public function getBalance(VendorInterface $vendor)
    {
        $parameters = $this->mergeSubAccountParameters($vendor);
        return $this->callSoap("getBalance", $parameters);
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
            case 'transfert':
                return $this->transactionClient;
            default:
                return $this->userAccountClient;
        }
    }

    /**
     * @param string $name
     * @param array $parameters
     * @return array
     * @throws \Exception
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
            throw new \Exception(
                "There was an error with the soap call $name\n" .
                $response['description'] . "\n" .
                "Parameters : \n" .
                print_r($parameters, true)
            );
        } else {
            unset($response['code']);
            unset($response['description']);
        }

        return $response ?: true;
    }
}