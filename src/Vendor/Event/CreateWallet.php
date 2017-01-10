<?php

namespace HiPay\Wallet\Mirakl\Vendor\Event;

use HiPay\Wallet\Mirakl\Api\HiPay\Model\Rest\UserAccount;
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

    /**
     * CreateWalletEvent constructor.
     *
     * @param UserAccount        $userAccount
     */
    public function __construct(
        UserAccount $userAccount
    ) {
        $this->userAccount = $userAccount;
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
}
