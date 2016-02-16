<?php
namespace HiPay\Wallet\Mirakl\Service\Ftp\Factory;

use HiPay\Wallet\Mirakl\Service\Ftp\Configuration\LocalConfigurationInterface;
use HiPay\Wallet\Mirakl\Service\Ftp\LocalFTP;

/**
 * File Local.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Local extends AbstractFactory
{
    const LOCAL = 'local';

    /**
     * ConnectionFactory constructor.
     *
     * @param LocalConfigurationInterface $configuration
     */
    public function __construct(LocalConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }


    /**
     * @return LocalFTP
     */
    protected function buildFTP()
    {
        return new LocalFTP();
    }
}