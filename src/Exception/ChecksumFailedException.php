<?php
/**
 * File ChecksumFailedException.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace HiPay\Wallet\Mirakl\Exception;

use Exception;

/**
 * Class ChecksumFailedException
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class ChecksumFailedException extends DispatchableException
{
    /**
     * ChecksumFailedException constructor.
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(
        $message = '',
        $code = 0,
        Exception $previous = null
    ) {
        parent::__construct($message ?: 'Wrong checksum', $code, $previous);
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'checksum.failed';
    }
}