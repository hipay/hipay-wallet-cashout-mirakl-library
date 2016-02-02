<?php

namespace HiPay\Wallet\Mirakl\Exception;

use Exception;

/**
 * Class FTPUploadFailed
 * Thrown when a ftp upload failed.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class FTPUploadFailed extends DispatchableException
{
    /** @var  string */
    protected $source;

    /** @var  string */
    protected $remote;

    /**
     * FTPUploadFailed constructor.
     *
     * @param string $source
     * @param int    $remote
     * @param string $message
     * @param $code
     * @param Exception $previous
     */
    public function __construct(
        $source,
        $remote,
        $message = '',
        $code = 0,
        Exception $previous = null
    ) {
        $this->source = $source;
        $this->remote = $remote;
        parent::__construct(
            $message ?: "The uploading of the document $source has failed.",
            $code,
            $previous
        );
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getRemote()
    {
        return $this->remote;
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'ftp.upload.failed';
    }
}
