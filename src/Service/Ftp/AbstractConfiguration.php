<?php
namespace Hipay\MiraklConnector\Service\Ftp;
use Touki\FTP\Connection\Connection;
use Touki\FTP\Connection\SSLConnection;

/**
 * File Config.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
abstract class AbstractConfiguration
{
    const FTP = 'ftp';
    const sFTP = 'sftp';
    const FTP_SSL = 'ftp_ssl';

    /**
     * Returns the ftp host
     *
     * @return string
     */
    public abstract function getHost();

    /**
     * Returns the ftp port
     *
     * @return string
     */
    public abstract function getPort();

    /**
     * Returns the ftp login
     *
     * @return string
     */
    public abstract function getUsername();

    /**
     * Returns the ftp password
     *
     * @return string
     */
    public abstract function getPassword();

    /**
     * Returns the ftp timeout
     *
     * @return int
     */
    public abstract function getTimeout();

    /**
     * Return the true if connection is passive, false otherwise
     *
     * @return boolean
     */
    public abstract function isPassive();

    /**
     * Returns the ftp connection type
     * Expect one of : ftp, ftp_ssl, sftp
     *
     * @return string
     */
    public abstract function getConnectionType();

    /**
     * @return Connection
     */
    public function build()
    {
        switch (strtolower($this->getConnectionType())) {
            case  self::FTP:
                return new Connection(
                    $this->getHost(),
                    $this->getUsername(),
                    $this->getPassword(),
                    $this->getPort(),
                    $this->getTimeout(),
                    $this->isPassive()
                );
            case  self::sFTP:
                return new SSHConnection(
                    $this->getHost(),
                    $this->getPort(),
                    $this->getTimeout()

                );
                break;
            case  self::FTP_SSL:
                return new SSLConnection(
                    $this->getHost(),
                    $this->getUsername(),
                    $this->getPassword(),
                    $this->getPort(),
                    $this->getTimeout(),
                    $this->isPassive()
                );
                break;
            default:
                throw new \InvalidArgumentException(
                    "The connection type " .
                    $this->getConnectionType().
                    " don't exists"
                );
        }
    }
}