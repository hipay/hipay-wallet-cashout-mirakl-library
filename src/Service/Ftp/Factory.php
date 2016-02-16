<?php
namespace HiPay\Wallet\Mirakl\Service\Ftp;

use HiPay\Wallet\Mirakl\Service\Validation\ModelValidator;
use InvalidArgumentException;
use Touki\FTP\Connection\Connection;
use Touki\FTP\Connection\SSLConnection;
use Touki\FTP\FTP;
use Touki\FTP\FTPFactory;

/**
 * File Factory.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Factory extends FTPFactory
{

    const FTP = 'ftp';
    const SFTP = 'sftp';
    const FTP_SSL = 'ftp_ssl';
    const LOCAL = 'local';

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
     * @return FTP
     * @throws InvalidArgumentException
     */
    public function getFTP()
    {
        ModelValidator::validate($this->configuration);
        switch ($this->configuration->getConnectionType()) {
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
            case static::LOCAL:
                return new LocalFTP();
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