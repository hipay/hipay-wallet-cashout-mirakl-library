<?php

namespace HiPay\Wallet\Mirakl\Common;

use Exception;
use HiPay\Wallet\Mirakl\Api\Mirakl;
use HiPay\Wallet\Mirakl\Api\HiPay;
use HiPay\Wallet\Mirakl\Exception\DispatchableException;
use HiPay\Wallet\Mirakl\Exception\Event\ThrowException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class AbstractProcessor.
 *
 * Abstract class for all processors
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
abstract class AbstractProcessor
{


    /** @var EventDispatcherInterface event */
    protected $dispatcher;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * AbstractProcessor constructor.
     *
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface          $logger
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger
    ) {

        $this->dispatcher = $dispatcher;

        $this->logger = $logger;
    }

    /**
     * Add event listener to dispatcher.
     *
     * @param $eventName
     * @param $function
     *
     * @see EventDispatcherInterface::addListener
     */
    public function addListener($eventName, $function)
    {
        $this->dispatcher->addListener($eventName, $function);
    }

    /**
     * Add event subscriber to dispatcher.
     *
     * @param EventSubscriberInterface $subscriberInterface
     *
     * @see EventDispatcherInterface::addSubscriber
     */
    public function addSubscriber(EventSubscriberInterface $subscriberInterface)
    {
        $this->dispatcher->addSubscriber($subscriberInterface);
    }

    /**
     * Handle the exception
     * @param Exception $exception
     * @param array $context
     * @param string $level
     */
    public function handleException(Exception $exception, $level = 'warning', array $context = array())
    {
        $this->logger->$level(
            $exception->getMessage(), $context
        );
        $this->dispatcher->dispatch(
            $exception instanceof DispatchableException ? $exception->getEventName() : 'exception.thrown',
            new ThrowException($exception)
        );
    }
}
