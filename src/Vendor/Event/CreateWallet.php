<?php

namespace Hipay\MiraklConnector\Vendor\Event;

use Hipay\MiraklConnector\Api\Hipay\Model\Soap\MerchantData;
use Hipay\MiraklConnector\Api\Hipay\Model\Soap\UserAccountBasic;
use Hipay\MiraklConnector\Api\Hipay\Model\Soap\UserAccountDetails;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CreateWallet
 * Event object used when the event 'before.wallet.creation'
 * is dispatched from the processor.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class CreateWallet extends Event
{
    /** @var  UserAccountBasic */
    protected $userAccountBasic;

    /** @var  UserAccountDetails */
    protected $userAccountDetails;

    /** @var  MerchantData */
    protected $merchantData;

    /**
     * CreateWalletEvent constructor.
     *
     * @param UserAccountBasic   $userAccountBasic
     * @param UserAccountDetails $userAccountDetails
     * @param MerchantData       $merchantData
     */
    public function __construct(
        UserAccountBasic $userAccountBasic,
        UserAccountDetails $userAccountDetails,
        MerchantData $merchantData
    ) {
        $this->userAccountBasic = $userAccountBasic;
        $this->userAccountDetails = $userAccountDetails;
        $this->merchantData = $merchantData;
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
