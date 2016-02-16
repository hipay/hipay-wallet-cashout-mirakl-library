<?php
namespace HiPay\Wallet\Mirakl\Service\Ftp\Factory;

use HiPay\Wallet\Mirakl\Service\Ftp\Configuration\RemoteConfigurationInterface;
use HiPay\Wallet\Mirakl\Service\Ftp\SSHConnection;
use InvalidArgumentException;
use Touki\FTP\Connection\Connection;
use Touki\FTP\Connection\SSLConnection;
use Touki\FTP\FTP;

/**
 * File Remote.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Remote extends AbstractFactory
{

    const FTP = 'ftp';
    const SFTP = 'sftp';
    const FTP_SSL = 'ftp_ssl';

    /**
     * ConnectionFactory constructor.
     *
     * @param RemoteConfigurationInterface $configuration
     */
    public function __construct(RemoteConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return FTP
     * @throws InvalidArgumentException
     */
    protected function buildFTP()
    {
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
        $ftp = parent::build($connection);
        return $ftp;
    }
}