<?php
namespace Hipay\MiraklConnector\Api\Hipay\Model;
use Hipay\MiraklConnector\Vendor\VendorInterface;

/**
 * File AccountDetails.php
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
     *
     * @param VendorInterface $vendor
     * @param array $shopData
     */
    public function __construct(VendorInterface $vendor, array $shopData)
    {
        parent::__construct($vendor, $shopData);
    }
}