<?php
/**
 * File LocalConfigurationInterface.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace HiPay\Wallet\Mirakl\Service\Ftp\Configuration;


/**
 * Interface RemoteConfigurationInterface
 * @package HiPay\Wallet\Mirakl\Service\Ftp
 */
interface LocalConfigurationInterface extends ConfigurationInterface
{
    /**
     * Returns the ftp connection type
     * Expect one of : ftp, ftp_ssl, sftp.
     *
     * @return string
     *
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Assert\EqualTo("local")
     */
    public function getConnectionType();

    /**
     * @return string
     *
     * @Assert\NotBlank()
     * @Assert\Type("string")
     */
    public function getPath();
}