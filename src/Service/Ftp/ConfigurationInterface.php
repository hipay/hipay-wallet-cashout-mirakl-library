<?php

namespace HiPay\Wallet\Mirakl\Service\Ftp;

/**
 * Interface ConfigurationInterface
 * Represent an ftp configuration
 * Must be implemented by the integrator.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
interface ConfigurationInterface
{
    /**
     * Returns the ftp host.
     *
     * @return string
     */
    public function getHost();

    /**
     * Returns the ftp port.
     *
     * @return string
     */
    public function getPort();

    /**
     * Returns the ftp login.
     *
     * @return string
     */
    public function getUsername();

    /**
     * Returns the ftp password.
     *
     * @return string
     */
    public function getPassword();

    /**
     * Returns the ftp timeout.
     *
     * @return int
     */
    public function getTimeout();

    /**
     * Return the true if connection is passive, false otherwise.
     *
     * @return bool
     */
    public function isPassive();

    /**
     * Returns the ftp connection type
     * Expect one of : ftp, ftp_ssl, sftp.
     *
     * @return string
     */
    public function getConnectionType();
}
