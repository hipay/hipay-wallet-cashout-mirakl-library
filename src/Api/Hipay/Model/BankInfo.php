<?php
/**
 * File BankInfo.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Api\Hipay\Model;


use Hipay\MiraklConnector\Vendor\VendorInterface;

/**
 * Class BankInfo
 * Value object for bank data
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class BankInfo extends SoapModelAbstract
{
    protected $bankName;
    protected $bankAddress;
    protected $bankZipCode;
    protected $bankCity;
    protected $bankCountry;
    protected $iban;
    protected $swift;
    protected $acct_num;
    protected $aba_num;
    protected $transit_num;


    /**
     * Populate the fields with data
     *
     * @param VendorInterface $vendor
     * @param array $miraklShopData
     *
     * @return self
     */
    public function setData(VendorInterface $vendor, array $miraklShopData)
    {
        $this->bankName = $miraklShopData['paymentInfo']['bank_name'];
        $this->bankAddress = $miraklShopData['paymentInfo']['bank_street'];
        $this->bankZipCode = $miraklShopData['paymentInfo']['bank_zip'];
        $this->bankCity = $miraklShopData['paymentInfo']['bank_city'];
        $this->swift = $miraklShopData['paymentInfo']['bic'];
        $this->iban = $miraklShopData['paymentInfo']['iban'];

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBankName()
    {
        return $this->bankName;
    }

    /**
     * @return mixed
     */
    public function getBankAddress()
    {
        return $this->bankAddress;
    }

    /**
     * @return mixed
     */
    public function getBankZipCode()
    {
        return $this->bankZipCode;
    }

    /**
     * @return mixed
     */
    public function getBankCity()
    {
        return $this->bankCity;
    }

    /**
     * @return mixed
     */
    public function getBankCountry()
    {
        return $this->bankCountry;
    }

    /**
     * @return mixed
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * @return mixed
     */
    public function getSwift()
    {
        return $this->swift;
    }

    /**
     * @return mixed
     */
    public function getAcctNum()
    {
        return $this->acct_num;
    }

    /**
     * @return mixed
     */
    public function getAbaNum()
    {
        return $this->aba_num;
    }

    /**
     * @return mixed
     */
    public function getTransitNum()
    {
        return $this->transit_num;
    }
}