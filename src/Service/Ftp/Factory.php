<?php

namespace HiPay\Wallet\Mirakl\Service\Ftp;

use HiPay\Wallet\Mirakl\Service\Validation\ModelValidator;
use InvalidArgumentException;
use Touki\FTP\Connection\Connection;
use Touki\FTP\Connection\SSLConnection;
use Touki\FTP\FTPFactory as BaseFactory;

/**
 * Generate a connection according to the parameters given in the constructor.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Factory extends BaseFactory
{
    const FTP = 'ftp';
    const SFTP = 'sftp';
    const FTP_SSL = 'ftp_ssl';

    /** @var  ConfigurationInterface */
    protected $configuration;

    /**
     * ConnectionFactory constructor.
     *
     * @param ConfigurationInterface $configuration
     */
    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Construct a connection from a ftp configuration.
     *
     * @return Connection
     */
    public function getFtp()
    {
        ModelValidator::validate($this->configuration);
        switch (strtolower($this->configuration->getConnectionType())) {
            case static::FTP:
                $connection = new Connection(
                    $this->configuration->getHost(),
                    $this->configuration->getUsername(),
                    $this->configuration->getPassword(),
                    $this->configuration->getPort(),
                    $this->configuration->getTimeout(),
                    $this->configuration->isPassive()
                );
                break;
            case static::SFTP:
                $connection =  new SSHConnection(
                    $this->configuration->getHost(),
                    $this->configuration->getPort()
                );
                break;
            case static::FTP_SSL:
                $connection =  new SSLConnection(
                    $this->configuration->getHost(),
                    $this->configuration->getUsername(),
                    $this->configuration->getPassword(),
                    $this->configuration->getPort(),
                    $this->configuration->getTimeout(),
                    $this->configuration->isPassive()
                );
                break;
            default:
                throw new InvalidArgumentException(
                    'The connection type '.
                    $this->configuration->getConnectionType().
                    " don't exists"
                );
        }
        return parent::build($connection);
    }
}
