<?php

namespace HiPay\Wallet\Mirakl\Api\Mirakl;

use HiPay\Wallet\Mirakl\Api\ConfigurationInterface as BaseConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Mirakl configuration object interface.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
interface ConfigurationInterface extends BaseConfigurationInterface
{
    /**
     * Returns the web service base URL given by Mirakl.
     *
     * @return string
     *
     * @Assert\NotBlank()
     * @Assert\Type("string")
     */
    public function getBaseUrl();

    /**
     * Returns the front api key.
     *
     * @return string
     *
     * @Assert\NotBlank()
     * @Assert\Type("string")
     */
    public function getFrontKey();

    /**
     * Return the operator api key.
     *
     * @return string
     *
     * @Assert\Type("string")
     */
    public function getOperatorKey();
}
