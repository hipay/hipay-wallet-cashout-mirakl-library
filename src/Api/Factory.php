<?php

namespace HiPay\Wallet\Mirakl\Api;

use HiPay\Wallet\Mirakl\Api\HiPay\ApiInterface as HiPayInterface;
use HiPay\Wallet\Mirakl\Api\HiPay\ConfigurationInterface as HiPayConfiguration;
use HiPay\Wallet\Mirakl\Api\Mirakl\ApiInterface as MiraklInterface;
use HiPay\Wallet\Mirakl\Api\Mirakl\ConfigurationInterface as MiraklConfiguration;
use HiPay\Wallet\Mirakl\Service\Validation\ModelValidator;

/**
 * Api Factory from configuration objects
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Factory
{
    /** @var  MiraklConfiguration */
    protected $miraklConfiguration;
    /** @var HiPayConfiguration  */
    protected $hiPayConfiguration;

    /**
     * Factory constructor.
     *
     * @param MiraklConfiguration $miraklConfiguration
     * @param HiPayConfiguration $hiPayConfiguration
     */
    public function __construct(
        MiraklConfiguration $miraklConfiguration,
        HiPayConfiguration $hiPayConfiguration
    ) {
        $this->miraklConfiguration = $miraklConfiguration;
        $this->hiPayConfiguration = $hiPayConfiguration;
    }

    /**
     * @return MiraklInterface
     */
    public function getMirakl()
    {
        ModelValidator::validate($this->miraklConfiguration);
        return new Mirakl(
            $this->miraklConfiguration->getBaseUrl(),
            $this->miraklConfiguration->getFrontKey(),
            $this->miraklConfiguration->getOperatorKey(),
            $this->miraklConfiguration->getOptions()
        );
    }

    /**
     * @return HiPayInterface
     */
    public function getHiPay()
    {
        ModelValidator::validate($this->hiPayConfiguration);
        return new HiPay(
            $this->hiPayConfiguration->getBaseSoapUrl(),
            $this->hiPayConfiguration->getBaseRestUrl(),
            $this->hiPayConfiguration->getWebServiceLogin(),
            $this->hiPayConfiguration->getWebServicePassword(),
            $this->hiPayConfiguration->getEntity(),
            $this->hiPayConfiguration->getLocale(),
            $this->hiPayConfiguration->getTimezone(),
            $this->hiPayConfiguration->getOptions(),
            $this->hiPayConfiguration->getRestTransferAndWithdraw()
        );
    }
}
