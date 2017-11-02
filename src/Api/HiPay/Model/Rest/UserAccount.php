<?php

namespace HiPay\Wallet\Mirakl\Api\HiPay\Model\Rest;

use HiPay\Wallet\Mirakl\Service\Country;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Value object for detailed account data.
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
class UserAccount extends ModelAbstract
{
    //Non mandatory properties
    /**
     * @var string
     *
     * @Assert\NotBlank
     * @Assert\Email
     */
    protected $email;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $controleType;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $credential;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $firstname;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $lastname;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @Assert\Currency
     */
    protected $currency;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @Assert\Locale
     */
    protected $locale;

    /** @var string */
    protected $login;

    /**
     * @var string
     *
     * @Assert\Range(
     *      min = 1,
     *      max = 3
     * )
     */
    protected $civility;

    /**
     * @var string
     *
     * @Assert\Ip
     */
    protected $ipAddress;

    /** @var string */
    protected $merchantGroupId;

    /** @var string */
    protected $entityCode;

    /** @var int
     * 0=>personal account,
     * 1=>business account
     *
     * @Assert\Choice(choices = {"0","1"})
     * @Assert\Type("integer")
     */
    protected $accountType;

    /** @var int
     * 1=>corporation,
     * 2=>person
     * 3=>association
     *
     * @Assert\Choice(choices = {"1","2","3"})
     * @Assert\Type("integer")
     */
    protected $proType;

    /** @var string Type of company (ltd..) */
    protected $alias;

    /** @var string */
    protected $companyName;

    /** @var string */
    protected $vatNumber;

    /** @var string */
    protected $address;

    /** @var string */
    protected $timezone;

    /**
     * @var string
     *
     * @Assert\Regex(
     *     pattern="#[0-9]{2}/[0-9]{2}/[0-9]{4}#",
     *      message="The date format must be [0-9]{2}/[0-9]{2}/[0-9]{4}"
     * )
     */
    protected $birthDate;

    /** @var string */
    protected $antiPhishingKey;

    /** @var int
     * 0=>personal account,
     * 1=>business account
     *
     * @Assert\Choice(choices = {"0","1"})
     * @Assert\Type("integer")
     */
    protected $hipayInformation;

    /** @var int
     * 0=>user not agrees,
     * 1=>user agrees
     *
     * @Assert\Choice(choices = {"0","1"})
     * @Assert\Type("integer")
     */
    protected $commercialInformation;

    /** @var string */
    protected $callbackUrl;

    /** @var string */
    protected $callbackSalt;

    /** @var string  */
    protected $cpf;

    /** @var int
     * 0=>activation by link,
     * 1=>Activation by code
     *
     * @Assert\Choice(choices = {"0","1"})
     * @Assert\Type("integer")
     */
    protected $activationType;

    /**
     * UserAccountDetails constructor.
     * Expects a mirakl based array.
     *
     * @param array $miraklData
     */
    public function __construct(
        array $miraklData
    ) {
        $this->email = $miraklData['contact_informations']['email'];
        $this->controleType = 'CREDENTIALS';
        $this->firstname = $miraklData['contact_informations']['firstname'];
        $this->lastname = $miraklData['contact_informations']['lastname'];
        $this->currency = $miraklData['currency_iso_code'];
        $this->login = 'mirakl_' .
            preg_replace("/[^A-Za-z0-9]/", '', $miraklData['shop_name']) .
            '_' .
            $miraklData['shop_id'];
        $this->civility = static::formatTitle(
            $miraklData['contact_informations']['civility']
        );
        $this->companyName = $miraklData['shop_name'];
        $this->vatNumber = $miraklData['pro_details']['VAT_number'];

        $address = $miraklData['contact_informations']['street1'] .' '. $miraklData['contact_informations']['street2'];
        $zipcode = $miraklData['contact_informations']['zip_code'];
        $city = $miraklData['contact_informations']['city'];
        $country = $this->formatCountryCode($miraklData['contact_informations']['country']);
        $phone = $miraklData['contact_informations']['phone'];
        $fax = $miraklData['contact_informations']['fax'];

        if (!empty($address)) {
            $this->address["address"] = $address;
        }
        if (!empty($zipcode)) {
            $this->address["zipcode"] = $zipcode;
        }
        if (!empty($city)) {
            $this->address["city"] = $city;
        }
        if (!empty($country)) {
            $this->address["country"] = $country;
        }
        if (!empty($phone)) {
            $this->address["phone_number"] = $phone;
        }
        if (!empty($fax)) {
            $this->address["fax_number"] = $fax;
        }

        $this->hipayInformation = 1;
        $this->commercialInformation = 1;
        $this->activationType = 0;
    }

    /**
     * Format the title (civility) for HiPay
     * Mr => 1
     * Mrs => 2
     * Miss => 3.
     *
     * @param $civility
     *
     * @return int
     */
    private static function formatTitle($civility)
    {
        switch ($civility) {
            case 'Mr':
                return 1;
            case 'Mrs':
                return 2;
            case 'Miss':
                return 3;
            default:
                return $civility;
        }
    }

    /**
     * Add the class data to the parameters
     * based on the class name.
     *
     * @param array $parameters
     *
     * @return array
     */
    public function mergeIntoParameters(array $parameters = array())
    {
        $this->validate();

        return $parameters + array(
            $this->getRestParameterKey() => $this->getData(),
        );
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getControleType()
    {
        return $this->controleType;
    }

    /**
     * @param string $controleType
     */
    public function setControleType($controleType)
    {
        $this->controleType = $controleType;
    }

    /**
     * @return array
     */
    public function getCredential()
    {
        return $this->credential;
    }

    /**
     * @param array $credential
     */
    public function setCredential($credential)
    {
        $this->credential = $credential;
    }

    /**
     * @return mixed
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param mixed $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * @return mixed
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param mixed $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param mixed $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return mixed
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param mixed $login
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }

    /**
     * @return mixed
     */
    public function getCivility()
    {
        return $this->civility;
    }

    /**
     * @param mixed $civility
     */
    public function setCivility($civility)
    {
        $this->civility = $civility;
    }

    /**
     * @return mixed
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param mixed $ipAddress
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * @return int
     */
    public function getMerchantGroupId()
    {
        return $this->merchantGroupId;
    }

    /**
     * @param int $merchantGroupId
     */
    public function setMerchantGroupId($merchantGroupId)
    {
        $this->merchantGroupId = $merchantGroupId;
    }

    /**
     * @return string
     */
    public function getEntityCode()
    {
        return $this->entityCode;
    }

    /**
     * @param string $entityCode
     */
    public function setEntityCode($entityCode)
    {
        $this->entityCode = $entityCode;
    }

    /**
     * @return string
     */
    public function getAccountType()
    {
        return $this->accountType;
    }

    /**
     * @param string $accountType
     */
    public function setAccountType($accountType)
    {
        $this->accountType = $accountType;
    }

    /**
     * @return int
     */
    public function getProType()
    {
        return $this->proType;
    }

    /**
     * @param int $proType
     */
    public function setProType($proType)
    {
        $this->proType = $proType;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
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

    /**
     * @return string
     */
    public function getVatNumber()
    {
        return $this->vatNumber;
    }

    /**
     * @param string $vatNumber
     */
    public function setVatNumber($vatNumber)
    {
        $this->vatNumber = $vatNumber;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return mixed
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param mixed $timezone
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
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
     * @return int
     */
    public function getHipayInformation()
    {
        return $this->hipayInformation;
    }

    /**
     * @param int $hipayInformation
     */
    public function setHipayInformation($hipayInformation)
    {
        $this->hipayInformation = $hipayInformation;
    }

    /**
     * @return int
     */
    public function getCommercialInformation()
    {
        return $this->commercialInformation;
    }

    /**
     * @param int $commercialInformation
     */
    public function setCommercialInformation($commercialInformation)
    {
        $this->commercialInformation = $commercialInformation;
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
    public function getCallbackSalt()
    {
        return $this->callbackSalt;
    }

    /**
     * @param string $callbackSalt
     */
    public function setCallbackSalt($callbackSalt)
    {
        $this->callbackSalt = $callbackSalt;
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
     * @return int
     */
    public function getActivationType()
    {
        return $this->activationType;
    }

    /**
     * @param int $activationType
     */
    public function setActivationType($activationType)
    {
        $this->activationType = $activationType;
    }

    /**
     * Format the country code.
     * @param $countryCode
     * @return false|string
     */
    public function formatCountryCode($countryCode)
    {
        return Country::toISO1366Alpha2($countryCode);
    }
}
