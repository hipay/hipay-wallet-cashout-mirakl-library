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
    /** @var string */
    protected $bankName;

    /** @var string */
    protected $bankAddress;

    /** @var string */
    protected $bankZipCode;

    /** @var string */
    protected $bankCity;

    /** @var string */
    protected $bankCountry;

    /** @var string */
    protected $iban;

    /** @var string */
    protected $swift;

    /** @var string */
    protected $acct_num;

    /** @var string */
    protected $aba_num;

    /** @var string */
    protected $transit_num;


    /**
     * Populate the fields with data
     *
     * @param VendorInterface $vendor
     * @param array $miraklData
     */
    public function __construct(VendorInterface $vendor, array $miraklData)
    {
        parent::__construct($vendor, $miraklData);
        $this->bankName = $miraklData['paymentInfo']['bank_name'];
        $this->bankAddress = $miraklData['paymentInfo']['bank_street'];
        $this->bankZipCode = $miraklData['paymentInfo']['bank_zip'];
        $this->bankCity = $miraklData['paymentInfo']['bank_city'];
        $this->swift = $miraklData['paymentInfo']['bic'];
        $this->iban = $miraklData['paymentInfo']['iban'];
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

    /**
     * @param mixed $bankName
     */
    public function setBankName($bankName)
    {
        $this->bankName = $bankName;
    }

    /**
     * @param mixed $bankAddress
     */
    public function setBankAddress($bankAddress)
    {
        $this->bankAddress = $bankAddress;
    }

    /**
     * @param mixed $bankZipCode
     */
    public function setBankZipCode($bankZipCode)
    {
        $this->bankZipCode = $bankZipCode;
    }

    /**
     * @param mixed $bankCity
     */
    public function setBankCity($bankCity)
    {
        $this->bankCity = $bankCity;
    }

    /**
     * @param mixed $bankCountry
     */
    public function setBankCountry($bankCountry)
    {
        $this->bankCountry = $bankCountry;
    }

    /**
     * @param mixed $iban
     */
    public function setIban($iban)
    {
        $this->iban = $iban;
    }

    /**
     * @param mixed $swift
     */
    public function setSwift($swift)
    {
        $this->swift = $swift;
    }

    /**
     * @param mixed $acct_num
     */
    public function setAcctNum($acct_num)
    {
        $this->acct_num = $acct_num;
    }

    /**
     * @param mixed $aba_num
     */
    public function setAbaNum($aba_num)
    {
        $this->aba_num = $aba_num;
    }

    /**
     * @param mixed $transit_num
     */
    public function setTransitNum($transit_num)
    {
        $this->transit_num = $transit_num;
    }
}