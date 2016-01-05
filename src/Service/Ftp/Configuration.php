<?php
namespace Hipay\MiraklConnector\Service\Ftp;
/**
 * File Config.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
interface ConfigurationInterface
{
    /**
     * Returns the ftp host
     *
     * @return string
     */
    public function getHost();

    /**
     * Returns the ftp port
     *
     * @return string
     */
    public function getPort();

    /**
     * Returns the ftp login
     *
     * @return string
     */
    public function getUsername();

    /**
     * Returns the ftp password
     *
     * @return string
     */
    public function getPassword();

    /**
     * Returns the ftp connection type
     * Expect one of : ftp, ftp_ssl, sftp
     *
     * @return string
     */
    public function getConnectionType();
}