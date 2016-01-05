<?php
/**
 * File Config.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Api;

/**
 * Interface ConfigurationInterface
 * Base Interface for the configuration of the Api class
 *
 * @package Hipay\MiraklConnector\Api
 */
interface ConfigurationInterface
{
    /**
     * Returns the base url who serve to construct the call
     *
     * @return string
     */
    public function getBaseUrl();

    /**
     * Returns the configuration array
     * compatible with the rest or soap client used
     *
     * @return array
     */
    public function getOptions();
}