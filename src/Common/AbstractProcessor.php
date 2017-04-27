<?php

namespace HiPay\Wallet\Mirakl\Common;

use Exception;
use HiPay\Wallet\Mirakl\Api\HiPay;
use HiPay\Wallet\Mirakl\Api\Mirakl;
use HiPay\Wallet\Mirakl\Exception\DispatchableException;
use HiPay\Wallet\Mirakl\Exception\Event\ThrowException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use HiPay\Wallet\Mirakl\Notification\FormatNotification;

/**
 *
 * Abstract class for all processors
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
abstract class AbstractProcessor
{


    /** @var EventDispatcherInterface event */
    protected $dispatcher;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @var FormatNotification class
     */
    protected $formatNotification;

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

        $this->formatNotification = new FormatNotification();
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
        $title = 'Handle Exception: '. $level;
        $messageException = $exception->getMessage();
        $message = $this->formatNotification->formatMessage($title,false,$messageException);
        $this->logger->$level(
            $message, $context
        );
        $this->dispatcher->dispatch(
            $exception instanceof DispatchableException ? $exception->getEventName() : 'exception.thrown',
            new ThrowException($exception)
        );
    }
}
