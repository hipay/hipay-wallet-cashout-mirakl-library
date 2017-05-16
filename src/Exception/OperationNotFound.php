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
 * Thrown when the operation is not found in the storage
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class OperationNotFound extends DispatchableException
{
    /**
     * ChecksumFailedException constructor.
     * @param string $withdrawId
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(
        $withdrawId,
        $message = '',
        $code = 0,
        Exception $previous = null
    ) {
        parent::__construct($message ?: "No operation was found with this withdrawId : $withdrawId", $code, $previous);
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'operation.not.found';
    }
}