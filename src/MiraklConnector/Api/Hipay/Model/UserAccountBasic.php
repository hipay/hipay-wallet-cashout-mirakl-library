<?php
namespace Hipay\MiraklConnector\Api\Hipay\Model;
use Hipay\MiraklConnector\Vendor\VendorInterface;

/**
 * File AccountBasic.php
 * Value object for basic account data
 *
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
     * @param array $miraklShopData
     * @param string $locale
     *
     * @return UserAccountBasic
     */
    public function setData(
        VendorInterface $vendor,
        array $miraklShopData,
        $locale = 'fr_FR'
    )
    {
        $this->email = $vendor->getEmail();
        $this->title = self::formatTitle(
            $miraklShopData['contact_informations']['civility']
        );
        $this->firstname = $miraklShopData['contact_informations']['civility'];
        $this->lastname = $miraklShopData['contact_informations']['civility'];
        $this->currency = $miraklShopData['currency_iso_code'];
        $this->locale = $locale;
        $this->ipAddress = $_SERVER['SERVER_ADDR'];
        $this->entity = $vendor->getMiraklShopId();

        return $this;
    }

    /**
     * Format the title (civility) for Hipay
     *
     * @param $civility
     *
     * @return mixed
     */
    private static function formatTitle($civility)
    {
        return $civility;
    }
}