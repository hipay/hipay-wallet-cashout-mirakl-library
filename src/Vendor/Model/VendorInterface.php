<?php

namespace HiPay\Wallet\Mirakl\Vendor\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Vendor processor handling the wallet creation
 * and the bank info registration and verification.
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
interface VendorInterface
{
    /**
     * @return int|null if operator
     *
     * @Assert\NotBlank(groups={"Default"})
     * @Assert\Type(type="integer", groups={"Default"})
     * @Assert\GreaterThan(value = 0, groups={"Default"})
     * @Assert\IsNull(groups={"Operator"})
     */
    public function getMiraklId();

    /**
     * @param int $id|null if operator
     *
     * @return void
     */
    public function setMiraklId($id);

    /**
     * @return string
     *
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     * @Assert\Email()
     */
    public function getEmail();

    /**
     * @param string $email
     *
     * @return void
     */
    public function setEmail($email);

    /**
     * @return int
     */
    public function getHiPayId();

    /**
     * @param int $id
     *
     * @return void
     */
    public function setHiPayId($id);

    /**
     * @return int
     */
    public function getHiPayUserSpaceId();

    /**
     * @param int $id
     *
     * @return void
     */
    public function setHiPayUserSpaceId($id);

    /**
     * @return int
     */
    public function getHiPayIdentified();

    /**
     * @param int $id
     *
     * @return void
     */
    public function setHiPayIdentified($id);

    /**
     * @return string
     */
    public function getVatNumber();

    /**
     * @param vatNumber $string
     *
     * @return void
     */
    public function setVatNumber($string);

    /**
     * @return string
     */
    public function getCallbackSalt();

    /**
     * @param callbackSalt $string
     *
     * @return void
     */
    public function setCallbackSalt($string);

    /**
     * @return Vendor
     */
    public function getEnabled();

    /**
     * @param Vendor $enabled
     */
    public function setEnabled($enabled);

    /**
     * @return string
     */
    public function getCountry();

    /**
     * @param string $country
     */
    public function setCountry($country);

    /**
     * @return Boolean
     */
    public function isPaymentBlocked();

    /**
     * @param Boolean $paymentBlocked
     */
    public function setPaymentBlocked($paymentBlocked);
}
