<?php
/**
 * File FileManager.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConector\Service;

/**
 * Class FileManager
 * Contains function to deal the the files functionaries of the library
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Ftp
{
    const FTP = 'ftp';
    const sFTP = 'sftp';
    const FTP_SSL = 'ftp_ssl';

    protected $connection;

    protected $loggedIn;

    /**
     * FileManager constructor.
     * Connect to the ftp
     *
     * @param string $host the host
     * @param int $port the port
     * @param string $connectionType
     * the connection type (must one of FTP, sFTP, FTP_SSL)
     * @param string $username the ftp login
     * @param string $password the ftp password
     * @param array $methods
     * the methods for the ssh connection call (only used for the sftp method)
     * @param array $callbacks
     * the callbacks for the ssh connection call (only used for the sftp method)
     */
    public function __construct(
        $host,
        $port = 21,
        $connectionType = self::FTP,
        $username =  'anonymous',
        $password =  '',
        $methods = array(),
        $callbacks = array()
    )
    {
        $this->connect(
            $host,
            $port,
            $connectionType,
            $methods,
            $callbacks);
        $this->login($username, $password);
    }

    /**
     * Setter for the connection
     * Connects or reconnect to the ftp
     *
     * @param string $host the host
     * @param int $port the port
     * @param string $connectionType
     * the connection type (must one of FTP, sFTP, FTP_SSL)
     * @param array $methods
     * the methods for the ssh connection call (only used for the sftp method)
     * @param array $callbacks
     * the callbacks for the ssh connection call (only used for the sftp method)
     */
    protected function connect(
        $host,
        $port = 21,
        $connectionType = self::FTP,
        $methods = array(),
        $callbacks = array()
    )
    {
        switch (strtolower($connectionType)) {
            case  self::FTP:
                $this->connection = ftp_connect($host, $port);
                break;
            case  self::sFTP:
                $this->connection = ssh2_sftp(
                    ssh2_connect($host, $port, $methods, $callbacks)
                );
                break;
            case  self::FTP_SSL:
                $this->connection = ftp_ssl_connect($host, $port);
                break;
            default:
                throw new \InvalidArgumentException(
                    "The connection type $connectionType don't exists"
                );
        }
    }

    /**
     * Login into the ftp
     *
     * @param string $username the login
     * @param string $password the password
     *
     * @return bool
     */
    protected function login($username, $password)
    {
        if (!$this->connection) {
            throw new \RuntimeException("The connection must set before login");
        }
        $this->loggedIn = ftp_login($this->connection, $username, $password);
        return $this->loggedIn;
    }

    /**
     * Upload a file into the ftp
     *
     * @param string $source the path to the source file
     * @param string $destination the folder to upload into
     *
     * @return bool
     */
    public function uploadFile($source, $destination)
    {
        if (!$this->connection) {
            throw new \RuntimeException("The connection set before uploading");
        }

        if (!$this->loggedIn) {
            throw new \RuntimeException(
                "You must be logged in before uploading files"
            );
        }

        $handle = fopen($source, 'r');
        if ($handle === false) {
            throw new \RuntimeException("The file $source is not readable");
        }
        return ftp_fput($this->connection, $destination, $handle, FTP_ASCII);
    }

    /**
     * Create a directory on the FTP
     *
     * @param string $dirPath the path to the directory to create
     * @return string
     */
    public function createDirectory($dirPath)
    {
        return ftp_mkdir($this->connection, $dirPath);
    }

    /**
     * Create FTP object from array
     *
     * @param array $parameters
     *
     * @return self
     */
    public static function factory(array $parameters)
    {
        return new self(
            $parameters['host'],
            $parameters['port'],
            $parameters['username'],
            $parameters['methods'],
            $parameters['callbacks']
        );
    }
}