<?php

namespace HiPay\Wallet\Mirakl\Service\Ftp;

use Touki\FTP\Connection\Connection;
use Touki\FTP\ConnectionInterface;
use Touki\FTP\Exception\ConnectionUnestablishedException;

/**
 * Represent a ssh connection.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class SSHConnection implements ConnectionInterface
{
    /** @var  string */
    protected $host;

    /** @var  string */
    protected $port;

    /** @var  array */
    protected $method;

    /** @var  array */
    protected $callback;

    /** @var  boolean */
    protected $connected;

    protected $stream;

    /**
     * SSHConnection constructor.
     * @param string $host
     * @param string $port
     * @param array $method
     * @param array $callback
     */
    public function __construct($host, $port, array $method = null, array $callback = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->method = $method;
        $this->callback = $callback;
        $this->connected = false;
        $this->stream = false;
    }


    /**
     * Calls the connector.
     *
     * @return bool TRUE on success, FALSE on failure
     */
    public function connect()
    {
        $this->stream = ssh2_sftp(ssh2_connect($this->host, $this->port, $this->method, $this->callback));
    }

    /**
     * Returns the connection stream
     * @return resource FTP Connection stream
     * @throws ConnectionUnestablishedException
     */
    public function getStream()
    {
        if (!$this->isConnected()) {
            throw new ConnectionUnestablishedException("Cannot get stream context. Connection is not established");
        }
        return $this->stream;
    }

    /**
     * Tells wether the connection is established
     *
     * @return boolean TRUE if connected, FALSE if not
     */
    public function isConnected()
    {
        return $this->connected;
    }
}
