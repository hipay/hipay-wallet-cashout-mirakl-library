<?php
/**
 *
 * @category
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
namespace HiPay\Wallet\Mirakl\Api;

/**
 * Base Interface for the configuration of the Api class.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
interface ConfigurationInterface
{
    /**
     * Returns the configuration array
     * compatible with the rest or soap client used.
     *
     * @return array
     */
    public function getOptions();
}
