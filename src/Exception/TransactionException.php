<?php
/**
 * File TransactionException.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Exception;

/**
 * Class TransactionException
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class TransactionException extends DispatchableException
{

    /**
     * TransactionException constructor.
     *
     * @param string $message
     * @param int $code
     * @param $previousException
     */
    public function __construct(
        $message = "",
        $code = 0,
        $previousException = null
    )
    {
        parent::__construct($message, $code, $previousException);

    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'transaction.exception';
    }
}