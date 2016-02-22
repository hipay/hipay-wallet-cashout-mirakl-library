<?php
/**
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace HiPay\Wallet\Mirakl\Common;

use HiPay\Wallet\Mirakl\Api\Factory;
use HiPay\Wallet\Mirakl\Api\HiPay;
use HiPay\Wallet\Mirakl\Api\HiPay\ApiInterface as HiPayInterface;
use HiPay\Wallet\Mirakl\Api\Mirakl;
use HiPay\Wallet\Mirakl\Api\Mirakl\ApiInterface as MiraklInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 *
 * Processor who need the API to function
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
abstract class AbstractApiProcessor extends AbstractProcessor
{
    /** @var MiraklInterface $mirakl */
    protected $mirakl;

    /** @var HiPayInterface $hipay */
    protected $hipay;

    /**
     * AbstractProcessor constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     * @param Factory $factory
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        Factory $factory
    ) {

        parent::__construct($dispatcher, $logger);

        $this->mirakl = $factory->getMirakl();

        $this->hipay = $factory->getHiPay();
    }
}
