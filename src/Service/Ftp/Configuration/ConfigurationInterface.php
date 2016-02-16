<?php

namespace HiPay\Wallet\Mirakl\Service\Ftp\Configuration;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represent an ftp configuration
 * Must be implemented by the integrator.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
interface ConfigurationInterface
{
    /**
     * Returns the ftp connection type
     * Expect one of : ftp, ftp_ssl, sftp, local.
     *
     * @return string
     *
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Assert\Choice({"ftp", "ftp_ssl", "sftp", "local"})
     */
    public function getConnectionType();
}
