<?php
namespace Hipay\MiraklConnector\Api\Hipay\Model;
use Hipay\MiraklConnector\Vendor\VendorInterface;

/**
 * File AccountDetail.php
 * Value object for detailled account data
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class UserAccountDetails extends SoapModelAbstract
{
    //Non mandatory properties

    /** @var int
     * 0=>personal account,
     * 1=>business account
     *
     * @Assert\Range(
     *      min = 1,
     *      max = 3
     * )
     */
    protected $legalStatus;

    /** @var string Type of company (ltd..) */
    protected $structure;

    /** @var string Like CEO for example.  */
    protected $directorRole;

    /** @var “Cadastro de Pessoas Físicas” for Brazilian accounts.  */
    protected $cpf;

    /** @var string “rg” or “rne” for Brazilian accounts. */
    protected $identificationNumberType ;

    /** @var string For Brazilian accounts. */
    protected $identificationNumber;

    /** @var string */
    protected $state;

    /** @var string */
    protected $birthDate;

    /** @var string */
    protected $mobilePhoneNumber;

    /** @var string */
    protected $faxNumber;

    /** @var string */
    protected $europeanVATNumber;

    /** @var string */
    protected $businessId;

    /** @var string Antiphishing string. */
    protected $antiPhishingKey;

    /**
     * @var bool false or true if user agrees.
     *
     * @Assert\Type(type="bool")
     */
    protected $receiveHipayInformation;

    /**
     * @var bool false or true if user agrees.
     *
     * @Assert\Type(type="bool")
     */
    protected $receiveCommercialInformation;

    /**
     * @var string URL where the notifications concerning this account will be sent
     *
     * @Assert\Url()
     */
    protected $callbackUrl;

    //Mandatory properties
    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $address;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $zipCode;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $city;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $country;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $timeZone;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $contactEmail;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $phoneNumber;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @Assert\IsTrue
     */
    protected $termsAgreed;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $companyName;

    /**
     * UserAccountDetails constructor.
     * @param VendorInterface $vendor
     * @param array $miraklData
     * @param string $timeZone
     */
    public function __construct(
        VendorInterface $vendor,
        array $miraklData,
        $timeZone = 'Europe/Paris'
    )
    {
        parent::__construct($vendor, $miraklData);
        $this->address = $miraklData['contact_informations']['street1'] .
            " " . $miraklData['contact_informations']['street2'];
        $this->zipCode = $miraklData['contact_informations']['zip_code'];
        $this->city = $miraklData['contact_informations']['city'];
        $this->country = $miraklData['contact_informations']['country'];
        $this->timeZone = $timeZone;
        $this->contactEmail = $miraklData['contact_informations']['email'];
        $this->phoneNumber = $miraklData['contact_informations']['phone'];
        $this->companyName = $miraklData['shop_name'];
        $this->termsAgreed = true;
        $this->receiveCommercialInformation = false;
        $this->receiveHipayInformation = false;
        $this->legalStatus = 1;
    }

    /**
     * Add the class data to the parameters
     * based on the class name
     *
     * @param array $parameters
     *
     * @return array
     */
    public function mergeIntoParameters(array $parameters = array())
    {
        $this->validate();
        return $parameters + array(
            $this->getSoapParameterKey() => $this->getSoapParameterData()
        );
    }

    /**
     * @return int
     */
    public function getLegalStatus()
    {
        return $this->legalStatus;
    }

    /**
     * @param int $legalStatus
     */
    public function setLegalStatus($legalStatus)
    {
        $this->legalStatus = $legalStatus;
    }

    /**
     * @return string
     */
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * @param string $structure
     */
    public function setStructure($structure)
    {
        $this->structure = $structure;
    }

    /**
     * @return string
     */
    public function getDirectorRole()
    {
        return $this->directorRole;
    }

    /**
     * @param string $directorRole
     */
    public function setDirectorRole($directorRole)
    {
        $this->directorRole = $directorRole;
    }

    /**
     * @return mixed
     */
    public function getCpf()
    {
        return $this->cpf;
    }

    /**
     * @param mixed $cpf
     */
    public function setCpf($cpf)
    {
        $this->cpf = $cpf;
    }

    /**
     * @return string
     */
    public function getIdentificationNumberType()
    {
        return $this->identificationNumberType;
    }

    /**
     * @param string $identificationNumberType
     */
    public function setIdentificationNumberType($identificationNumberType)
    {
        $this->identificationNumberType = $identificationNumberType;
    }

    /**
     * @return string
     */
    public function getIdentificationNumber()
    {
        return $this->identificationNumber;
    }

    /**
     * @param string $identificationNumber
     */
    public function setIdentificationNumber($identificationNumber)
    {
        $this->identificationNumber = $identificationNumber;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return string
     */
    public function getBirthDate()
    {
        return $this->birthDate;
    }

    /**
     * @param string $birthDate
     */
    public function setBirthDate($birthDate)
    {
        $this->birthDate = $birthDate;
    }

    /**
     * @return string
     */
    public function getMobilePhoneNumber()
    {
        return $this->mobilePhoneNumber;
    }

    /**
     * @param string $mobilePhoneNumber
     */
    public function setMobilePhoneNumber($mobilePhoneNumber)
    {
        $this->mobilePhoneNumber = $mobilePhoneNumber;
    }

    /**
     * @return string
     */
    public function getFaxNumber()
    {
        return $this->faxNumber;
    }

    /**
     * @param string $faxNumber
     */
    public function setFaxNumber($faxNumber)
    {
        $this->faxNumber = $faxNumber;
    }

    /**
     * @return string
     */
    public function getEuropeanVATNumber()
    {
        return $this->europeanVATNumber;
    }

    /**
     * @param string $europeanVATNumber
     */
    public function setEuropeanVATNumber($europeanVATNumber)
    {
        $this->europeanVATNumber = $europeanVATNumber;
    }

    /**
     * @return string
     */
    public function getBusinessId()
    {
        return $this->businessId;
    }

    /**
     * @param string $businessId
     */
    public function setBusinessId($businessId)
    {
        $this->businessId = $businessId;
    }

    /**
     * @return string
     */
    public function getAntiPhishingKey()
    {
        return $this->antiPhishingKey;
    }

    /**
     * @param string $antiPhishingKey
     */
    public function setAntiPhishingKey($antiPhishingKey)
    {
        $this->antiPhishingKey = $antiPhishingKey;
    }

    /**
     * @return boolean
     */
    public function isReceiveHipayInformation()
    {
        return $this->receiveHipayInformation;
    }

    /**
     * @param boolean $receiveHipayInformation
     */
    public function setReceiveHipayInformation($receiveHipayInformation)
    {
        $this->receiveHipayInformation = $receiveHipayInformation;
    }

    /**
     * @return boolean
     */
    public function isReceiveCommercialInformation()
    {
        return $this->receiveCommercialInformation;
    }

    /**
     * @param boolean $receiveCommercialInformation
     */
    public function setReceiveCommercialInformation($receiveCommercialInformation)
    {
        $this->receiveCommercialInformation = $receiveCommercialInformation;
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    /**
     * @param string $callbackUrl
     */
    public function setCallbackUrl($callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * @param string $zipCode
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function getTimeZone()
    {
        return $this->timeZone;
    }

    /**
     * @param string $timeZone
     */
    public function setTimeZone($timeZone)
    {
        $this->timeZone = $timeZone;
    }

    /**
     * @return string
     */
    public function getContactEmail()
    {
        return $this->contactEmail;
    }

    /**
     * @param string $contactEmail
     */
    public function setContactEmail($contactEmail)
    {
        $this->contactEmail = $contactEmail;
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @param string $phoneNumber
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return boolean
     */
    public function isTermsAgreed()
    {
        return $this->termsAgreed;
    }

    /**
     * @param boolean $termsAgreed
     */
    public function setTermsAgreed($termsAgreed)
    {
        $this->termsAgreed = $termsAgreed;
    }

    /**
     * @return string
     */
    public function getCompanyName()
    {
        return $this->companyName;
    }

    /**
     * @param string $companyName
     */
    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;
    }
}