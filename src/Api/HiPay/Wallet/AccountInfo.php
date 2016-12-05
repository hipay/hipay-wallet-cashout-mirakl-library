<?php

namespace HiPay\Wallet\Mirakl\Api\HiPay\Wallet;

/**
 * Contains information about a HiPay Wallet account.
 * Information should be retrieved from the the platform.
 *
 * @package HiPay\Wallet\Mirakl\Vendor\Mirakl
 */
class AccountInfo
{
    private $userAccountld;
    private $userSpaceld;
    private $identified;
    private $vatNumber;

    /**
     * AccountInfo constructor.
     *
     * @param $userAccountld User account ID
     * @param $userSpaceld User space ID
     * @param $identified Whether the account is identified or not
     * @param $vatNumber User VAT Number
     */
    public function __construct($userAccountld, $userSpaceld, $identified, $vatNumber)
    {
        $this->userAccountld = $userAccountld;
        $this->userSpaceld = $userSpaceld;
        $this->identified = $identified;
        $this->vatNumber = $vatNumber;
    }

    /**
     * @return int User account ID
     */
    public function getUserAccountld()
    {
        return $this->userAccountld;
    }

    /**
     * @param mixed $userAccountld User account ID
     */
    public function setUserAccountld($userAccountld)
    {
        $this->userAccountld = $userAccountld;
    }

    /**
     * @return int User space ID
     */
    public function getUserSpaceld()
    {
        return $this->userSpaceld;
    }

    /**
     * @param mixed $userSpaceld User space ID
     */
    public function setUserSpaceld($userSpaceld = null)
    {
        $this->userSpaceld = $userSpaceld;
    }

    /**
     * @return boolean Whether the account is identified or not
     */
    public function getIdentified()
    {
        return $this->identified;
    }

    /**
     * @param mixed $identified Whether the account is identified or not
     */
    public function setIdentified($identified)
    {
        $this->identified = $identified;
    }

    /**
     * @return string $vatNumber User VAT Number
     */
    public function getVatNumber()
    {
        return $this->vatNumber;
    }

    /**
     * @param string $vatNumber User VAT Number
     */
    public function setVatNumber($vatNumber)
    {
        $this->vatNumber = $vatNumber;
    }
}