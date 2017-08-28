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
    private $callbackSalt;
    private $requestMessage;

    /**
     * AccountInfo constructor.
     *
     * @param $userAccountld User account ID
     * @param $userSpaceld User space ID
     * @param $identified Whether the account is identified or not
     * @param $vatNumber User VAT Number
     * @param $callbackSalt User Security key
     */
    public function __construct($userAccountld, $userSpaceld, $identified, $callbackSalt, $requestMessage)
    {
        $this->userAccountld = $userAccountld;
        $this->userSpaceld = $userSpaceld;
        $this->identified = $identified;
        $this->callbackSalt = $callbackSalt;
        $this->requestMessage = $requestMessage;
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
     * @return varchar User security key
     */
    public function getCallbackSalt()
    {
        return $this->callbackSalt;
    }

    /**
     * @param mixed $callbackSalt User security key
     */
    public function setCallbackSalt($callbackSalt)
    {
        $this->callbackSalt = $callbackSalt;
    }

    /**
     * @return varchar
     */
    public function getRequestMessage()
    {
        return $this->requestMessage;
    }

    /**
     * @param mixed $requestMessage
     */
    public function setRequestMessage($requestMessage)
    {
        $this->requestMessage = $requestMessage;
    }

}