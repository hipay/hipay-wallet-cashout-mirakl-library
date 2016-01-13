<?php
/**
 * File AbstractProcessor.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Common;

use Hipay\MiraklConnector\Api\Mirakl;
use Hipay\MiraklConnector\Api\Mirakl\ConfigurationInterface
    as MiraklConfiguration;
use Hipay\MiraklConnector\Api\Hipay;
use Hipay\MiraklConnector\Api\Hipay\ConfigurationInterface
    as HipayConfiguration;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class AbstractProcessor
 *
 * Abstract class for all processors
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
abstract class AbstractProcessor
{
    /** @var Mirakl $mirakl */
    protected $mirakl;

    /** @var Hipay $hipay */
    protected $hipay;

    /** @var EventDispatcherInterface event */
    protected $dispatcher;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * AbstractProcessor constructor.
     * @param MiraklConfiguration $miraklConfig
     * @param HipayConfiguration $hipayConfig
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(
        MiraklConfiguration $miraklConfig,
        HipayConfiguration $hipayConfig,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger
    )
    {
        $this->mirakl = new Mirakl(
            $miraklConfig->getBaseUrl(),
            $miraklConfig->getFrontKey(),
            $miraklConfig->getShopKey(),
            $miraklConfig->getOperatorKey(),
            $miraklConfig->getOptions()
        );

        $this->hipay = Hipay::factory($hipayConfig);

        $this->dispatcher = $dispatcher;

        $this->logger = $logger;
    }

    /**
     * Add event listener to dispatcher
     *
     * @param $eventName
     * @param callable $function
     *
     * @see EventDispatcherInterface::addListener
     */
    public function addListener($eventName, callable $function)
    {
        $this->dispatcher->addListener($eventName, $function);
    }

    /**
     * Add event subscriber to dispatcher
     *
     * @param EventSubscriberInterface $subscriberInterface
     *
     * @see EventDispatcherInterface::addSubscriber
     */
    public function addSubscriber(EventSubscriberInterface $subscriberInterface)
    {
        $this->dispatcher->addSubscriber($subscriberInterface);
    }
}