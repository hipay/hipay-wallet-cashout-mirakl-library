<?php
/**
 * File AbstractApiProcessor.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace HiPay\Wallet\Mirakl\Common;

use HiPay\Wallet\Mirakl\Api\Mirakl;
use HiPay\Wallet\Mirakl\Api\Mirakl\ConfigurationInterface
    as MiraklConfiguration;
use HiPay\Wallet\Mirakl\Api\HiPay;
use HiPay\Wallet\Mirakl\Api\HiPay\ConfigurationInterface
    as HiPayConfiguration;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AbstractApiProcessor
 *
 * Processor who need the API to function
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
abstract class AbstractApiProcessor extends AbstractProcessor
{
    /** @var Mirakl $mirakl */
    protected $mirakl;

    /** @var HiPay $hipay */
    protected $hipay;

    /**
     * AbstractProcessor constructor.
     *
     * @param MiraklConfiguration      $miraklConfig
     * @param HiPayConfiguration       $hipayConfig
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface          $logger
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        MiraklConfiguration $miraklConfig,
        HiPayConfiguration $hipayConfig
    ) {

        parent::__construct($dispatcher, $logger);

        $this->mirakl = Mirakl::factory($miraklConfig);

        $this->hipay = HiPay::factory($hipayConfig);
    }
}
