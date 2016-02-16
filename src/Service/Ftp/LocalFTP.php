<?php
/**
 * File LocalFTP.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace HiPay\Wallet\Mirakl\Service\Ftp;


use Touki\FTP\FTPInterface;
use Touki\FTP\Model\Directory;
use Touki\FTP\Model\File;
use Touki\FTP\Model\Filesystem;

/**
 * Ftp on the same machine
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class LocalFTP implements FTPInterface
{
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
        if ($filterFiles && $filterDir) {
            return array();
        }

        $result = array();
        $dirPath = $directory->getRealpath();
        $handle = opendir($dirPath);
        while (($entry = readdir($handle) !== false)) {
            if ($entry == "." || $entry == "..") {
                continue;
            }

            $entryPath = $dirPath . $entry;

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
        return file_exists($fs->getRealpath());
    }

    /**
     * Checks whether a remote file exists
     *
     * @param  File $file A File instance
     * @return boolean TRUE if it exists, FALSE if not
     */
    public function fileExists(File $file)
    {

        return $this->filesystemExists($file) && is_file($file->getRealpath());
    }

    /**
     * Checks whether a remote directory exists
     *
     * @param  Directory $directory A Directory instance
     * @return boolean   TRUE if it exists, FALSE if not
     */
    public function directoryExists(Directory $directory)
    {
        return $this->filesystemExists($directory) && is_dir($directory->getRealpath());
    }

    /**
     * Finds a remote Filesystem by its name
     *
     * @param  string $filename Filesystem name
     * @param Directory $inDirectory
     * @return Filesystem A Filesystem instance, NULL if it doesn't exists
     */
    public function findFilesystemByName($filename, Directory $inDirectory = null)
    {
        $cwd = getcwd();

        if ($inDirectory) {
            chdir($inDirectory->getRealpath());
        }

        $return = null;

        if (is_file($filename)) {
            $return = new File(getcwd() . DIRECTORY_SEPARATOR .$filename);
        }

        if (is_dir($filename)) {
            $return = new Directory(getcwd() . DIRECTORY_SEPARATOR .$filename);
        }

        chdir($cwd);

        return $return;
    }

    /**
     * Finds a remote File by its name
     *
     * @param  string $filename File name
     * @param Directory $inDirectory
     * @return File A File instance, NULL if it doesn't exists
     */
    public function findFileByName($filename, Directory $inDirectory = null)
    {
        $cwd = getcwd();

        if ($inDirectory) {
            chdir($inDirectory->getRealpath());
        }

        $return = null;

        if (is_file($filename)) {
            $return = new File(getcwd() . DIRECTORY_SEPARATOR .$filename);
        }

        chdir($cwd);

        return $return;
    }

    /**
     * Finds a directory by its name
     *
     * @param  string $directory Directory name
     * @param Directory $inDirectory
     * @return Directory A Directory instance, NULL if it doesn't exists
     */
    public function findDirectoryByName($directory, Directory $inDirectory = null)
    {
        $cwd = getcwd();

        if ($inDirectory) {
            chdir($inDirectory->getRealpath());
        }

        $return = null;

        if (is_dir($directory)) {
            $return = new Directory(getcwd() . DIRECTORY_SEPARATOR . $directory);
        }

        chdir($cwd);

        return $return;
    }

    /**
     * Returns the current working directory
     *
     * @return Directory A Directory instance
     */
    public function getCwd()
    {
        return new Directory(getcwd());
    }

    /**
     * Downloads a remote Filesystem into the given local
     *
     * @param  string $local Local file path
     * @param  Filesystem $remote The remote Filesystem
     * @param  array $options Downloader's options
     * @return boolean    TRUE on success, FALSE on failure
     */
    public function download($local, Filesystem $remote, array $options = array())
    {
        copy($remote->getRealpath(), $local);
        return true;
    }

    /**
     * Uploads to a remote Filesystem from a given local
     *
     * @param  Filesystem $remote The remote Filesystem
     * @param  string $local Local file, resource
     * @param  array $options Uploader's options
     * @return boolean    TRUE on success, FALSE on failure
     */
    public function upload(Filesystem $remote, $local, array $options = array())
    {
        copy($local, $remote->getRealpath());
        return true;
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
            $mode = isset($options['mode']) ?: 0755;
            $recursive = isset($options['recursive']) ?: false;
            mkdir($filesystem->getRealpath(), $mode, $recursive);
        }

        if ($filesystem instanceof File) {
            $mode = isset($options['mode']) ?: 'w';
            $useIncludePath = isset($options['use_include_path']) ?: null;
            fopen($filesystem->getRealpath(), $mode, $useIncludePath);
        }

        return true;
    }
}