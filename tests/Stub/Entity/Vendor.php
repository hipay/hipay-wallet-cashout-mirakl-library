<?php
/**
 * File Vendor.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace HiPay\Wallet\Mirakl\Test\Stub\Entity;


use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;

/**
 * Class Vendor
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Vendor implements VendorInterface
{

    protected $miraklId;
    protected $hipayId;
    protected $email;

    /**
     * Vendor constructor.
     * @param $miraklId
     * @param $hipayId
     * @param $email
     */
    public function __construct($email, $hipayId, $miraklId = null)
    {
        $this->miraklId = $miraklId;
        $this->hipayId = $hipayId;
        $this->email = $email;
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
}