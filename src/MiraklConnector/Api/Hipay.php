<?php
namespace Hipay\MiraklConnector\Api;

use Hipay\MiraklConnector\Api\Hipay\Model\BankInfo;
use Hipay\MiraklConnector\Api\Hipay\Model\MerchantData;
use Hipay\MiraklConnector\Api\Hipay\Model\UserAccountBasic;
use Hipay\MiraklConnector\Api\Hipay\Model\UserAccountDetails;
use Hipay\MiraklConnector\Common\Smile_Soap_Client as SoapClient;
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
    /** @var  string the base url to make call from and get wsdl from */
    protected $baseUrl;

    /** @var  string the hipay webservice login */
    protected $login;

    /** @var  string the hipay webservice password */
    protected $password;

    /** @var  array the soapClient options */
    protected $options;

    /** @var  SoapClient the user account client */
    protected $userAccountClient;

    /**
     * Constructor
     *
     * @param string $baseUrl
     * @param string $login
     * @param string $password
     * @param array $options
     */
    public function __construct($baseUrl, $login, $password, $options)
    {
        $this->baseUrl = $baseUrl;
        $this->login = $login;
        $this->password = $password;
        $this->options = $options;
        $this->userAccountClient = new SoapClient(
            $this->baseUrl . 'soap/user-account-v2?wsdl', $this->options
        );


    }

    /**
     * Check if given email can be used to create an Hipay wallet
     *
     * @param string $email
     * @param string $entity
     *
     * @return bool
     */
    public function isAvailable($email, $entity)
    {
        $parameters = $this->mergeLoginParameters(
            array('email' => $email,'entity' => $entity)
        );
        $response = $this->userAccountClient->isAvailable($parameters);
        return !$this->hasError($response) ? $response['isAvailable'] : false;
    }

    /**
     * Create an new account on Hipay wallet
     *
     * @param UserAccountBasic $accountBasic
     * @param UserAccountDetails $accountDetails
     * @param MerchantData $merchantData
     *
     * @return bool
     * @throws \Exception
     */
    public function createFullUserAccount(
        UserAccountBasic $accountBasic,
        UserAccountDetails $accountDetails,
        MerchantData $merchantData
    )
    {
        $parameters = array();
        $parameters = $accountBasic->addToParameters($parameters);
        $parameters = $accountDetails->addToParameters($parameters);
        $parameters = $merchantData->addToParameters($parameters);
        $parameters = $this->mergeLoginParameters($parameters);
        $response = $this->userAccountClient->createFullUserAccount($parameters);
        return !$this->hasError($response) ? $response : false;
    }

    /**
     * Retrieve from Hipay the bank information
     *
     * @param VendorInterface $vendor
     *
     * @return BankInfo
     */
    public function bankInfosCheck(VendorInterface $vendor)
    {
        $parameters = array();
        $parameters = $this->mergeLoginParameters($parameters);
        $parameters = $this->mergeSubAccountParameters($parameters, $vendor);
        $response = $this->userAccountClient->bankInfosCheck($parameters);
        return new BankInfo($response);
    }

    /**
     * Retrieve from Hipay the bank account status
     *
     * @param VendorInterface $vendor
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function bankInfosStatus(VendorInterface $vendor)
    {
        $parameters = array();
        $parameters = $this->mergeLoginParameters($parameters);
        $parameters = $this->mergeSubAccountParameters($parameters, $vendor);
        $response = $this->userAccountClient->bankInfosStatus($parameters);
        return !$this->hasError($response) ? $response['status'] : false;
    }

    /**
     * Create a bank account in Hipay
     *
     * @param VendorInterface $vendor
     * @param BankInfo $bankInfo
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function bankInfoRegister(
        VendorInterface $vendor,
        BankInfo $bankInfo
    )
    {
        $parameters = array();
        $parameters = $this->mergeLoginParameters($parameters);
        $parameters = $this->mergeSubAccountParameters($parameters, $vendor);
        $parameters = $bankInfo->mergeIntoParameters($parameters);
        $response = $this->userAccountClient->bankInfoRegister($parameters);
        return !$this->hasError($response);
    }

    public function getAccountInfos()
    {
        $parameters = array();
        $response = $this->userAccountClient->getAccountInfos($parameters);
    }

    public function getBalance()
    {

    }

    /**
     * Return false if there wasn't an error, throw an exception otherwise
     *
     * @param array $response the response from Hipay
     *
     * @return false
     *
     * @throws \Exception
     */
    protected function hasError (array $response)
    {
        if ($response['code'] > 0) {
            throw new \Exception(
                "There was an error with the soap call\n" . $response['description']
            );
        }
        return false;
    }

    /**
     * Add the api login parameters to the parameters
     *
     * @param array $parameters the call parameters
     *
     * @return array
     */
    protected function mergeLoginParameters(array $parameters)
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
     * @param VendorInterface $vendor the vendor from which subaccount info is fetched from
     *
     * @return array
     */
    protected function mergeSubAccountParameters(
        array $parameters,
        VendorInterface $vendor
    )
    {
        $parameters += array(
            'wsSubAccountLogin' => $vendor->getEmail(),
            'wsSubAccountId' => $vendor->getHipayAccountId()
        );
        return $parameters;
    }
}