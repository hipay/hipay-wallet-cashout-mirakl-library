<?php

namespace HiPay\Wallet\Mirakl\Service;

/**
 * Class ZipArchive
 * Represent a Zip archive.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Zip
{
    /** @var  string the path to the zip file */
    protected $filePath;

    /**
     * ZipArchive constructor.
     *
     * @param $filePath
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Extract a zip file.
     *
     * @param string $destination the path of the destination folder
     *
     * @return true if the extract went well
     */
    public function extractFiles($destination)
    {
        $zip = new \ZipArchive();
        if ($zip->open($this->filePath) !== true) {
            throw new \InvalidArgumentException(
                "The $this->filePath couldn't be opened as a zip"
            );
        }
        if ($zip->extractTo($destination) !== true) {
            throw new \RuntimeException(
                'There was a problem during extraction'
            );
        };
        $zip->close();

        return true;
    }
}
