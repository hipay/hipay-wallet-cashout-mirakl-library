<?php

namespace HiPay\Wallet\Mirakl\Api\HiPay;

use HiPay\Wallet\Mirakl\Api\ConfigurationInterface
    as BaseConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

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
     *
     * @Assert\NotBlank()
     * @Assert\Type("string")
     */
    public function getWebServiceLogin();

    /**
     * Returns the web service password given by HiPay.
     *
     * @return string
     *
     * @Assert\NotBlank()
     * @Assert\Type("string")
     */
    public function getWebServicePassword();

    /**
     * Return the entity given to the merchant by HiPay.
     *
     * @return string
     *
     * @Assert\NotBlank()
     * @Assert\Type("string")
     */
    public function getEntity();

    /**
     * Returns the locale used in the webservice calls.
     *
     * @return string
     *
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Assert\Locale()
     * @Assert\Regex("/[a-z]{2}_[A-Z]{2}/")
     */
    public function getLocale();

    /**
     * Returns the timezone used in the webservice calls.
     *
     * @return string
     *
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Assert\Regex("#[A-Z][a-z_]+/[A-Z][a-z_]+#")
     */
    public function getTimezone();
}
