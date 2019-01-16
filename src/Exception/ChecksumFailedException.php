<?php
/**
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace HiPay\Wallet\Mirakl\Exception;

use Exception;

/**
 * Thrown when the notification checksum (md5) failed
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class ChecksumFailedException extends DispatchableException
{
    /**
     * ChecksumFailedException constructor.
     * @param $md5string
     * @param $callback_salt
     * @param $hipayId
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(
        $md5string,
        $callback_salt,
        $hipayId,
        $message = '',
        $code = 0,
        Exception $previous = null
    ) {
        parent::__construct(
            $message ?: 'Wrong checksum (md5string: ' .
                $md5string .
                ' | callback_salt: ' .
                $callback_salt .
                ' | hipayId: ' .
                $hipayId .
                ')',
            $code,
            $previous
        );
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'checksum.failed';
    }
}
