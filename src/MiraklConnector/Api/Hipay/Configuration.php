<?php
namespace Hipay\MiraklConector\Api\Hipay;

use Hipay\MiraklConector\Api\ConfigurationInterface as BaseConfigurationInterface;
/**
 * File Config.php
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
interface ConfigurationInterface extends BaseConfigurationInterface
{
    /**
     * Returns the web service login given by HiPay
     * @return string
     */
    public function getWebServiceLogin();

    /**
     * Returns the web service password given by HiPay
     * @return string
     */
    public function getWebServicePassword();
}