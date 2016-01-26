<?php

namespace Hipay\MiraklConnector\Api\Hipay\Model\Soap;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class BankInfo
 * Value object for bank data.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class BankInfo extends ModelAbstract
{
    /**
     * @var string
     *
     * @Assert\NotBlank
     * @Assert\Regex(
     *     pattern="/^[a-zA-Z0-9]+$/",
     *      message="Only alphanumeric characters allowed"
     * )
     */
    protected $bankName;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $bankAddress;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $bankZipCode;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @Assert\Regex(
     *     pattern="/^[a-zA-Z0-9]+$/",
     *      message="Only alphanumeric characters allowed"
     * )
     */
    protected $bankCity;

    /**
     * @var string
     *
     * @Assert\Country
     */
    protected $bankCountry;

    /**
     * @var string
     *
     * @Assert\Iban
     */
    protected $iban;

    /**
     * @var string
     *
     * @Assert\Bic
     */
    protected $swift;

    /** @var string */
    protected $acct_num;

    /** @var string */
    protected $aba_num;

    /** @var string */
    protected $transit_num;

    /**
     * @param array $miraklData
     *
     * @return self $this
     */
    public function setMiraklData(array $miraklData)
    {
        $paymentData = array_key_exists('payment_info', $miraklData) ?
            $miraklData['payment_info'] : $miraklData['billing_info'];
        $this->bankName = isset($paymentData['bank_name']) ?
            $paymentData['bank_name'] : '';
        $this->bankAddress = isset($paymentData['bank_street']) ?
            $paymentData['bank_street'] : '';
        $this->bankZipCode = (isset($paymentData['zip_code']) ?
            $paymentData['zip_code'] :
            (isset($paymentData['bank_zip']))
                ? $paymentData['bank_zip'] : '');
        $this->bankCity = isset($paymentData['bank_city']) ? $paymentData['bank_city'] : '';
        $this->swift = isset($paymentData['bic']) ? $paymentData['bic'] : '';
        $this->iban = isset($paymentData['iban']) ? $paymentData['iban'] : '';
        // Take the first to characters to fill the country
        $this->bankCountry = substr($this->iban, 0, 2);

        return $this;
    }

    /**
     * @param array $hipayData
     *
     * @return self $this
     */
    public function setHipayData(array $hipayData)
    {
        $this->bankName = $hipayData['bankName'];
        $this->bankAddress = $hipayData['bankAddress'];
        $this->bankZipCode = $hipayData['bankZipCode'];
        $this->bankCity = $hipayData['bankCity'];
        $this->swift = $hipayData['swift'];
        $this->iban = $hipayData['iban'];
        $this->bankCountry = $hipayData['bankCountry'];
        $this->aba_num = $hipayData['aba_num'];
        $this->transit_num = $hipayData['transit_num'];
        $this->acct_num = $hipayData['acct_num'];

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
