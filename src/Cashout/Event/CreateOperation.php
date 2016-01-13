<?php
/**
 * File CreateOperation.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Cashout\Event;


use Hipay\MiraklConnector\Cashout\Model\Operation\OperationInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class CreateOperation extends Event
{
    /** @var  OperationInterface */
    protected $operation;

    /**
     * CreateOperation constructor.
     * @param OperationInterface $operation
     */
    public function __construct(OperationInterface $operation)
    {
        $this->operation = $operation;
    }

    /**
     * @return OperationInterface
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param OperationInterface $operation
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
    }

}