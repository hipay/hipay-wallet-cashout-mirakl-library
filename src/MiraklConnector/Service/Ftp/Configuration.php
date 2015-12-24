<?php
namespace Hipay\MiraklConector\Service\Ftp;
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
    public function getHost();
    public function getPort();
    public function getUsername();
    public function getPassword();
    public function getConnectionType();
}