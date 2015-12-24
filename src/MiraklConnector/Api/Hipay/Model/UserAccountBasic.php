<?php
namespace Hipay\MiraklConnector\Api\Hipay\Model;
use Hipay\MiraklConnector\Vendor\VendorInterface;

/**
 * File AccountBasic.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class UserAccountBasic extends SoapModelAbstract
{
    protected $email;
    protected $title;
    protected $firstname;
    protected $lastname;
    protected $currency;
    protected $locale;
    protected $ipAddress;
    protected $entity;

    /**
     * UserAccountBasic constructor.
     *
     * @param VendorInterface $vendor
     * @param array $shopData
     */
    public function __construct(VendorInterface $vendor, array $shopData)
    {
        parent::__construct($vendor, $shopData);
        $this->email = $vendor->getEmail();
        $this->title = $this->setTitle($shopData['contact_informations']['civility']);
        $this->firstname = $shopData['contact_informations']['civility'];
        $this->lastname = $shopData['contact_informations']['civility'];
        $this->currency = $shopData['currency_iso_code'];
        $this->locale = 'fr_FR';
        $this->ipAddress = $_SERVER['SERVER_ADDR'];
        $this->entity = $vendor->getMiraklShopId();
    }


    /**
     * @param string $email
     * @return UserAccountBasic
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param mixed $title
     * @return UserAccountBasic
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param mixed $firstname
     * @return UserAccountBasic
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * @param mixed $lastname
     * @return UserAccountBasic
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * @param mixed $currency
     * @return UserAccountBasic
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @param mixed $locale
     * @return UserAccountBasic
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @param mixed $ipAddress
     * @return UserAccountBasic
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    /**
     * @param mixed $entity
     * @return UserAccountBasic
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }

}