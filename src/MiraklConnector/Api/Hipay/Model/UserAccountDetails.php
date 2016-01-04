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
    protected $address;
    protected $zipCode;
    protected $city;
    protected $country;
    protected $timeZone;
    protected $contactEmail;
    protected $phoneNumber;
    protected $termsAgreed;

    /**
     * UserAccountDetails constructor.
     * @param VendorInterface $vendor
     * @param array $miraklShopData
     * @param string $timeZone
     *
     * @return $this|SoapModelAbstract
     */
    public function setData(
        VendorInterface $vendor,
        array $miraklShopData,
        $timeZone = 'Europe/Paris'
    )
    {
        $this->address = $miraklShopData['contact_informations']['street1'] .
            " " . $miraklShopData['contact_informations']['street2'];
        $this->zipCode = $miraklShopData['contact_informations']['zip_code'];
        $this->city = $miraklShopData['contact_informations']['city'];
        $this->country = $miraklShopData['contact_informations']['country'];
        $this->timeZone = $timeZone;
        $this->contactEmail = $miraklShopData['contact_informations']['email'];
        $this->phoneNumber = $miraklShopData['contact_informations']['phone'];
        $this->termsAgreed = 1;

        return $this;
    }
}