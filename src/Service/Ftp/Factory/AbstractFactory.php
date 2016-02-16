<?php

namespace HiPay\Wallet\Mirakl\Service\Ftp\Factory;

use HiPay\Wallet\Mirakl\Service\Ftp\Configuration\ConfigurationInterface;
use HiPay\Wallet\Mirakl\Service\Validation\ModelValidator;
use Touki\FTP\FTP;
use Touki\FTP\FTPFactory as BaseFactory;

/**
 * Generate a connection according to the parameters given in the constructor.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
abstract class AbstractFactory extends BaseFactory
{
    /** @var  ConfigurationInterface */
    protected $configuration;

    /**
     * Construct a connection from a ftp configuration.
     *
     * @return FTP
     */
    public function getFTP()
    {
        ModelValidator::validate($this->configuration);
        return $this->buildFTP();
    }

    /**
     * @return FTP
     */
    abstract protected function buildFTP();
}
