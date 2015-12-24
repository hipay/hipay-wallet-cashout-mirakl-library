<?php
namespace Hipay\MiraklConnector\Api;

use Hipay\MiraklConnector\Api\Hipay\Model\MerchantData;
use Hipay\MiraklConnector\Api\Hipay\Model\UserAccountBasic;
use Hipay\MiraklConnector\Api\Hipay\Model\UserAccountDetails;
use Hipay\MiraklConnector\Common\Smile_Soap_Client as SoapClient;
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
        $client = new SoapClient(
            $this->baseUrl . 'soap/user-account-v2?wsdl', $this->options
        );
        $parameters = $this->mergeLoginParameters(
            array('email' => $email,'entity' => $entity)
        );
        $response = $client->isAvailable($parameters);
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
        $client = new SoapClient(
            $this->baseUrl . 'soap/user-account-v2?wsdl', $this->options
        );
        ;
        $parameters = $this->mergeLoginParameters(
            array(
                $accountBasic->getSoapParameterKey() => $accountBasic->getSoapParameterData(),
                $accountDetails->getSoapParameterKey() => $accountDetails->getSoapParameterData(),
                $merchantData->getSoapParameterKey() => $merchantData->getSoapParameterData(),
            )
        );
        $response = $client->createFullUserAccount($parameters);
        return !$this->hasError($response) ? $response : false;
    }

    public function bankInfosCheck()
    {

    }

    public function bankInfosStatus()
    {

    }

    public function bankInfoRegister()
    {

    }

    public function getAccountInfos()
    {

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
                "There was an error with the soap call\n" . $response['message']
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
}