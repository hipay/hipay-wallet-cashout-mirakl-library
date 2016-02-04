<?php
/**
 * File OperationNotFound.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace HiPay\Wallet\Mirakl\Exception;

use Exception;
use HiPay\Wallet\Mirakl\Cashout\Model\Operation\OperationInterface;

/**
 * Class OperationNotFound
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class WrongOperationStatus extends DispatchableException
{
    /**
     * @var OperationInterface
     */
    protected $operation;

    /**
     * ChecksumFailedException constructor.
     * @param string $operation
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(
        $operation,
        $message = '',
        $code = 0,
        Exception $previous = null
    ) {
        parent::__construct($message ?: "This operation's status is incorrect", $code, $previous);
        $this->operation = $operation;
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return 'checksum.failed';
    }

    /**
     * @return OperationInterface
     */
    public function getOperation()
    {
        return $this->operation;
    }
}