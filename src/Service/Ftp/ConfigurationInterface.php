<?php

namespace HiPay\Wallet\Mirakl\Service\Ftp;

use Symfony\Component\Validator\Constraints as Assert;

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
     *
     * @Assert\NotBlank()
     * @Assert\Type("string")
     */
    public function getHost();

    /**
     * Returns the ftp port.
     *
     * @return string
     *
     * @Assert\NotBlank()
     * @Assert\Type("integer")
     */
    public function getPort();

    /**
     * Returns the ftp login.
     *
     * @return string
     *
     * @Assert\NotBlank()
     * @Assert\Type("string")
     */
    public function getUsername();

    /**
     * Returns the ftp password.
     *
     * @return string
     *
     * @Assert\NotBlank()
     * @Assert\Type("string")
     */
    public function getPassword();

    /**
     * Returns the ftp timeout.
     *
     * @return int
     *
     * @Assert\NotBlank()
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual(0)
     */
    public function getTimeout();

    /**
     * Return the true if connection is passive, false otherwise.
     *
     * @return bool
     *
     * @Assert\NotNull()
     * @Assert\Type("boolean")
     */
    public function isPassive();

    /**
     * Returns the ftp connection type
     * Expect one of : ftp, ftp_ssl, sftp.
     *
     * @return string
     *
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Assert\Choice({"ftp", "ftp_ssl", "sftp"})
     */
    public function getConnectionType();
}
