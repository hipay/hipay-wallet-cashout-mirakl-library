<?php

namespace Hipay\MiraklConnector\Service\Ftp;

use Touki\FTP\Connection\Connection;

/**
 * Class SSHConnection
 * Represent a ssh connection.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class SSHConnection extends Connection
{
    /**
     * Calls the connector.
     *
     * @return bool TRUE on success, FALSE on failure
     */
    public function doConnect()
    {
        return ssh2_sftp(ssh2_connect($this->getHost(), $this->getPort()));
    }
}
