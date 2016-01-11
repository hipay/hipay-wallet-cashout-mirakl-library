<?php
/**
 * File ConnectionFactory.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Service\Ftp;


use Touki\FTP\Connection\Connection;
use Touki\FTP\Connection\SSLConnection;

/**
 * Class ConnectionFactory
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class ConnectionFactory
{
    const FTP = 'ftp';
    const sFTP = 'sftp';
    const FTP_SSL = 'ftp_ssl';

    /** @var  ConfigurationInterface */
    protected $configuration;

    /**
     * ConnectionFactory constructor.
     * @param ConfigurationInterface $configuration
     */
    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Construct a connection from a ftp configuration
     *
     * @return Connection
     */
    public function build()
    {
        switch (strtolower($this->configuration->getConnectionType()))
        {
            case  self::FTP:
                return new Connection(
                    $this->configuration->getHost(),
                    $this->configuration->getUsername(),
                    $this->configuration->getPassword(),
                    $this->configuration->getPort(),
                    $this->configuration->getTimeout(),
                    $this->configuration->isPassive()
                );
            case  self::sFTP:
                return new SSHConnection(
                    $this->configuration->getHost(),
                    $this->configuration->getPort()

                );
                break;
            case  self::FTP_SSL:
                return new SSLConnection(
                    $this->configuration->getHost(),
                    $this->configuration->getUsername(),
                    $this->configuration->getPassword(),
                    $this->configuration->getPort(),
                    $this->configuration->getTimeout(),
                    $this->configuration->isPassive()
                );
                break;
            default:
                throw new \InvalidArgumentException(
                    "The connection type " .
                    $this->configuration->getConnectionType().
                    " don't exists"
                );
        }
    }

}