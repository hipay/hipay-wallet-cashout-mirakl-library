<?php

namespace HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Value object for basic account data.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class UserAccountBasic extends ModelAbstract
{
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
     * @Assert\Range(
     *      min = 1,
     *      max = 3
     * )
     */
    protected $title;

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

    /**
     * @var string
     *
     * @Assert\Ip
     */
    protected $ipAddress;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $entity;

    /**
     * UserAccountBasic constructor.
     * Expects a mirakl based array.
     *
     * @param array $miraklData
     */
    public function __construct(
        array $miraklData
    ) {
        $this->email = $miraklData['contact_informations']['email'];
        $this->title = static::formatTitle(
            $miraklData['contact_informations']['civility']
        );
        $this->firstname = $miraklData['contact_informations']['firstname'];
        $this->lastname = $miraklData['contact_informations']['lastname'];
        $this->currency = $miraklData['currency_iso_code'];
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
     * Add the class data to the parameters under a key
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
            $this->getSoapParameterKey() => $this->getData(),
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
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
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
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param mixed $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }
}
