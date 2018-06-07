<?php
/**
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace HiPay\Wallet\Mirakl\Test\Stub\Entity;


use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;

/**
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Vendor implements VendorInterface
{

    protected $miraklId;
    protected $hipayId;
    protected $email;
    protected $hipayUserSpaceId;
    protected $hipayIdentified;
    protected $vatNumber;
    protected $callbackSalt;
    protected $enabled;
    protected $country;
    protected $paymentBlocked;

    /**
     * Vendor constructor.
     * @param $miraklId
     * @param $hipayId
     * @param $email
     * @param $hipayUserSpaceId
     * @param $hipayIdentified
     * @param $vatNumber
     * @param $callbackSalt
     */
    public function __construct(
        $email = null,
        $hipayId = null,
        $miraklId = null,
        $hipayUserSpaceId = null,
        $hipayIdentified = true,
        $vatNumber = null,
        $callbackSalt = null,
        $enabled = true,
        $country = null,
        $paymentBlocked = false
    ) {
        $this->miraklId = $miraklId;
        $this->hipayId = $hipayId;
        $this->email = $email;
        $this->hipayUserSpaceId = $hipayUserSpaceId;
        $this->hipayIdentified = $hipayIdentified;
        $this->vatNumber = $vatNumber;
        $this->callbackSalt = $callbackSalt;
        $this->enabled = $enabled;
        $this->country = $country;
        $this->paymentBlocked = $paymentBlocked;
    }

    /**
     * @return mixed
     */
    public function getMiraklId()
    {
        return $this->miraklId;
    }

    /**
     * @param mixed $miraklId
     */
    public function setMiraklId($miraklId)
    {
        $this->miraklId = $miraklId;
    }

    /**
     * @return mixed
     */
    public function getHipayId()
    {
        return $this->hipayId;
    }

    /**
     * @param mixed $hipayId
     */
    public function setHipayId($hipayId)
    {
        $this->hipayId = $hipayId;
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
    public function getHipayUserSpaceId()
    {
        return $this->hipayUserSpaceId;
    }

    /**
     * @param mixed $hipayUserSpaceId
     */
    public function setHipayUserSpaceId($hipayUserSpaceId)
    {
        $this->hipayUserSpaceId = $hipayUserSpaceId;
    }

    /**
     * @return mixed
     */
    public function getHipayIdentified()
    {
        return $this->hipayIdentified;
    }

    /**
     * @param mixed $hipayIdentified
     */
    public function setHipayIdentified($hipayIdentified)
    {
        $this->hipayIdentified = $hipayIdentified;
    }

    /**
     * @return mixed
     */
    public function getVatNumber()
    {
        return $this->vatNumber;
    }

    /**
     * @param mixed $vatNumber
     */
    public function setVatNumber($vatNumber)
    {
        $this->vatNumber = $vatNumber;
    }

    /**
     * @return mixed
     */
    public function getCallbackSalt()
    {
        return $this->callbackSalt;
    }

    /**
     * @param mixed $callbackSalt
     */
    public function setCallbackSalt($callbackSalt)
    {
        $this->callbackSalt = $callbackSalt;
    }

    /**
     * @return Vendor
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param Vendor $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return Vendor
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param Vendor $enabled
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return Boolean
     */
    public function isPaymentBlocked()
    {
        return $this->paymentBlocked;
    }

    /**
     * @param Boolean $paymentBlocked
     */
    public function setPaymentBlocked($paymentBlocked)
    {
        $this->paymentBlocked = $paymentBlocked;
    }
}
