<?php
/**
 * File SSHFTP.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace HiPay\Wallet\Mirakl\Service\Ftp;


use Touki\FTP\Exception\ConnectionUnestablishedException;
use Touki\FTP\FTPInterface;
use Touki\FTP\Model\Directory;
use Touki\FTP\Model\File;
use Touki\FTP\Model\Filesystem;

/**
 * Class SSHFTP
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class SSHFTP implements FTPInterface
{
    protected $connection;

    /**
     * SSHFTP constructor.
     * @param SSHConnection $connection
     */
    public function __construct(SSHConnection $connection)
    {
        $this->connection = $connection;
        $this->connection->connect();
    }

    /**
     * Finds remote filesystems in the given remote directory
     *
     * @param  Directory $directory A Directory instance
     * @param bool $filterFiles
     * @param bool $filterDir
     * @return array An array of Filesystem
     */
    public function findFilesystems(Directory $directory, $filterFiles = false, $filterDir = false)
    {
        $handle = opendir($this->getHandleString($directory));
        $result = array();
        while (false != ($entry = readdir($handle))) {
            if ($entry == "." || $entry == "..") {
                continue;
            }

            $entryPath = $directory->getRealpath() . $entry;

            if (!$filterFiles && is_file($entryPath)) {
                $result[] = new File($entryPath);
            }

            if (!$filterDir && is_dir($entryPath)) {
                $result[] = new Directory($entryPath);
            }

        }
        return $result;
    }

    /**
     * Finds files in the given remote directory
     *
     * @param  Directory $directory A Directory instance
     * @return array     An array of File
     */
    public function findFiles(Directory $directory)
    {
        $this->findFilesystems($directory, false, true);
    }

    /**
     * Finds directories in the given remote directory
     *
     * @param  Directory $directory A Directory instance
     * @return array     An array of Directory
     */
    public function findDirectories(Directory $directory)
    {
        $this->findFilesystems($directory, true, false);
    }

    /**
     * Checks whether a remote filesystem exists
     *
     * @param  Filesystem $fs A Filesystem instance
     * @return boolean    TRUE if it exists, FALSE if not
     */
    public function filesystemExists(Filesystem $fs)
    {
        return file_exists($this->getHandleString($fs));
    }

    /**
     * Checks whether a remote file exists
     *
     * @param  File $file A File instance
     * @return boolean TRUE if it exists, FALSE if not
     */
    public function fileExists(File $file)
    {
        return is_file($this->getHandleString($file));
    }

    /**
     * Checks whether a remote directory exists
     *
     * @param  Directory $directory A Directory instance
     * @return boolean   TRUE if it exists, FALSE if not
     */
    public function directoryExists(Directory $directory)
    {
        return is_dir($this->getHandleString($directory));
    }

    /**
     * Finds a remote Filesystem by its name
     *
     * @param  string $filename Filesystem name
     * @return Filesystem A Filesystem instance, NULL if it doesn't exists
     */
    public function findFilesystemByName($filename, Directory $inDirectory = null)
    {
        $path = "";

        if ($inDirectory) {
            $path = rtrim($inDirectory->getRealpath(), '/') . DIRECTORY_SEPARATOR;
        }

        $path .= $filename;

        $filesystem = new File($path);

        if ($this->fileExists($filesystem)) {
            return $filesystem;
        }

        $filesystem = new Directory($path);

        if ($this->directoryExists($filesystem)) {
            return $filesystem;
        }

        return null;
    }

    /**
     * Finds a remote File by its name
     *
     * @param  string $filename File name
     * @return File   A File instance, NULL if it doesn't exists
     */
    public function findFileByName($filename, Directory $inDirectory = null)
    {
        $path = "";

        if ($inDirectory) {
            $path = rtrim($inDirectory->getRealpath(), '/') . DIRECTORY_SEPARATOR;
        }

        $path .= $filename;

        $filesystem = new File($path);

        if ($this->fileExists($filesystem)) {
            return $filesystem;
        }

        return null;
    }

    /**
     * Finds a directory by its name
     *
     * @param  string $directory Directory name
     * @return Directory A Directory instance, NULL if it doesn't exists
     */
    public function findDirectoryByName($directory, Directory $inDirectory = null)
    {
        $path = "";

        if ($inDirectory) {
            $path = rtrim($inDirectory->getRealpath(), '/') . DIRECTORY_SEPARATOR;
        }

        $path .= $directory;

        $filesystem = new Directory($path);

        if ($this->directoryExists($filesystem)) {
            return $filesystem;
        }

        return null;
    }

    /**
     * Returns the current working directory
     * Doesn't work for this ftp
     *
     * @return Directory A Directory instance
     */
    public function getCwd()
    {
        return getcwd();
    }

    /**
     * Downloads a remote Filesystem into the given local
     *
     * @param  string $local Local file, resource
     * @param  Filesystem $remote The remote Filesystem
     * @param  array $options Downloader's options
     * @return boolean    TRUE on success, FALSE on failure
     */
    public function download($local, Filesystem $remote, array $options = array())
    {
        return ssh2_scp_recv($this->connection->getStream(), $remote->getRealpath(), $local);
    }

    /**
     * Uploads to a remote Filesystem from a given local
     *
     * @param  Filesystem $remote The remote Filesystem
     * @param  mixed $local Local file, resource
     * @param  array $options Uploader's options
     * @return boolean    TRUE on success, FALSE on failure
     */
    public function upload(Filesystem $remote, $local, array $options = array())
    {
        $mode = isset($options['mode']) ? $options['mode'] : 0644;
        return ssh2_scp_send($this->connection->getStream(), $local, $remote->getRealpath(), $mode);
    }

    /**
     * Creates a Filesystem on remote server
     *
     * @param  Filesystem $filesystem Filesystem to create
     * @param  array $options Creator's options
     * @return boolean    TRUE on success, FALSE on failure
     */
    public function create(Filesystem $filesystem, array $options = array())
    {
        if ($filesystem instanceof Directory) {
            $mode = isset($options['mode']) ? $options['mode'] : 0755;
            $recursive = isset($options['recursive']) ? $options['recursive'] : true;
            return ssh2_sftp_mkdir($this->connection->getStream(), $filesystem->getRealpath(), $mode, $recursive);
        }

        if ($filesystem instanceof File) {
            $mode = isset($options['mode']) ? $options['mode'] : 'w';
            $useIncludePath = isset($options['use_include_path']) ? $options['use_include_path'] : null;
            fopen($this->getHandleString($filesystem), $mode, $useIncludePath);
        }
    }

    /**
     * @param Filesystem $filesystem
     * @return string
     * @throws ConnectionUnestablishedException
     */
    protected function getHandleString(Filesystem $filesystem)
    {
        return "ssh2.sftp://{$this->connection->getStream()}"
        . DIRECTORY_SEPARATOR .
        ssh2_sftp_realpath($this->connection->getStream(), $filesystem->getRealpath());
    }
}