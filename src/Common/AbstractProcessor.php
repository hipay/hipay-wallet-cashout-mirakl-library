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
use Hipay\MiraklConnector\Api\Mirakl\ConfigurationInterface as MiraklConfiguration;
use Hipay\MiraklConnector\Api\Hipay;
use Hipay\MiraklConnector\Api\Hipay\ConfigurationInterface as HipayConfiguration;

use Symfony\Component\EventDispatcher\EventDispatcher;
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
    /** @var Mirakl $miraklClient */
    protected $mirakl;

    /** @var Hipay $hipay */
    protected $hipay;

    /** @var EventDispatcher event */
    protected $dispatcher;

    /**
     * AbstractProcessor constructor.
     * @param MiraklConfiguration $miraklConfig
     * @param HipayConfiguration $hipayConfig
     */
    public function __construct(
        MiraklConfiguration $miraklConfig,
        HipayConfiguration $hipayConfig
    )
    {
        $this->mirakl = new Mirakl(
            $miraklConfig->getBaseUrl(),
            $miraklConfig->getFrontKey(),
            $miraklConfig->getShopKey(),
            $miraklConfig->getOperatorKey(),
            $miraklConfig->getOptions()
        );

        $this->hipay = new Hipay(
            $hipayConfig->getBaseUrl(),
            $hipayConfig->getWebServiceLogin(),
            $hipayConfig->getWebServicePassword(),
            $hipayConfig->getOptions()
        );

        $this->dispatcher = new EventDispatcher();
    }

    /**
     * Add event listener to dispatcher
     *
     * @param $eventName
     * @param callable $function
     *
     * @see EventDispatcher::addListener
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
     * @see EventDispatcher::addSubscriber
     */
    public function addSubscriber(EventSubscriberInterface $subscriberInterface)
    {
        $this->dispatcher->addSubscriber($subscriberInterface);
    }
}