<?php

namespace HiPay\Wallet\Mirakl\Api\HiPay;

use HiPay\Wallet\Mirakl\Api\ConfigurationInterface
    as BaseConfigurationInterface;

/**
 * File Config.php.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
interface ConfigurationInterface extends BaseConfigurationInterface
{
    /**
     * Returns the web service login given by HiPay.
     *
     * @return string
     */
    public function getWebServiceLogin();

    /**
     * Returns the web service password given by HiPay.
     *
     * @return string
     */
    public function getWebServicePassword();

    /**
     * Return the entity given to the merchant by HiPay.
     *
     * @return string
     */
    public function getEntity();

    /**
     * Returns the locale used in the webservice calls.
     */
    public function getLocale();

    /**
     * Returns the timezone used in the webservice calls.
     */
    public function getTimezone();
}
