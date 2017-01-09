<?php

namespace HiPay\Wallet\Mirakl\Vendor\Event;

use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\MerchantData;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Rest\MerchantDataRest;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Rest\UserAccount;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\UserAccountBasic;
use HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap\UserAccountDetails;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event object used when the event 'before.wallet.creation'
 * is dispatched from the processor.
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
class CreateWallet extends Event
{
    /** @var  UserAccount */
    protected $userAccount;

    /** @var  UserAccountBasic */
    protected $userAccountBasic;

    /** @var  UserAccountDetails */
    protected $userAccountDetails;

    /** @var  MerchantData */
    protected $merchantData;

    /**
     * CreateWalletEvent constructor.
     *
     * @param UserAccount        $userAccount
     * @param MerchantData       $merchantData
     */
    public function __construct(
        UserAccount $userAccount,
        MerchantDataRest $merchantData
    ) {
        $this->userAccount = $userAccount;
        $this->merchantData = $merchantData;
    }

    /**
     * @return UserAccount
     */
    public function getUserAccount()
    {
        return $this->userAccount;
    }

    /**
     * @param UserAccount $userAccount
     *
     * @return CreateWallet
     */
    public function setUserAccount($userAccount)
    {
        $this->userAccount = $userAccount;

        return $this;
    }

    /**
     * @return UserAccountBasic
     */
    public function getUserAccountBasic()
    {
        return $this->userAccountBasic;
    }

    /**
     * @param UserAccountBasic $userAccountBasic
     *
     * @return CreateWallet
     */
    public function setUserAccountBasic($userAccountBasic)
    {
        $this->userAccountBasic = $userAccountBasic;

        return $this;
    }

    /**
     * @return UserAccountDetails
     */
    public function getUserAccountDetails()
    {
        return $this->userAccountDetails;
    }

    /**
     * @param UserAccountDetails $userAccountDetails
     *
     * @return CreateWallet
     */
    public function setUserAccountDetails($userAccountDetails)
    {
        $this->userAccountDetails = $userAccountDetails;

        return $this;
    }

    /**
     * @return MerchantData
     */
    public function getMerchantData()
    {
        return $this->merchantData;
    }

    /**
     * @param MerchantData $merchantData
     *
     * @return CreateWallet
     */
    public function setMerchantData($merchantData)
    {
        $this->merchantData = $merchantData;

        return $this;
    }
}
