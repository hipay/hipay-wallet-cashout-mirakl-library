<?php
namespace Hipay\MiraklConnector\Vendor\Event;
use Hipay\MiraklConnector\Api\Hipay\Model\MerchantData;
use Hipay\MiraklConnector\Api\Hipay\Model\UserAccountBasic;
use Hipay\MiraklConnector\Api\Hipay\Model\UserAccountDetails;
use Symfony\Component\EventDispatcher\Event;

/**
 * File CreateWallet.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class CreateWalletEvent extends AbstractEvent
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
     * @param array $miraklData
     * @param UserAccountBasic $userAccountBasic
     * @param UserAccountDetails $userAccountDetails
     * @param MerchantData $merchantData
     */
    public function __construct(
        array $miraklData,
        UserAccountBasic $userAccountBasic,
        UserAccountDetails $userAccountDetails,
        MerchantData $merchantData
    )
    {
        parent::__construct($miraklData);
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
     * @return CreateWalletEvent
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
     * @return CreateWalletEvent
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
     * @return CreateWalletEvent
     */
    public function setMerchantData($merchantData)
    {
        $this->merchantData = $merchantData;
        return $this;
    }

}