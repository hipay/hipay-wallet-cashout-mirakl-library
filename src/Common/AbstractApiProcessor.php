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
use HiPay\Wallet\Mirakl\Exception;

/**
 *
 * Processor who need the API to function
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
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

        // the treatment is stopped if the Mirakl settings is not correct
        $this->controlMiraklSettings();
    }

    /**
     * Control if the mirakl settings is ok with the HiPay Prerequisites
     */
    private function controlMiraklSettings()
    {
        // init mirakl settings by API Mirakl
        $documentDto = $this->mirakl->getDocumentTypesDto();
        $documentTypes = $this->mirakl->getDocumentTypes();

        foreach ($documentDto as $document)
        {
            if (!array_key_exists($document['code'], $documentTypes)) {
                throw new Exception\InvalidMiraklSettingException();
            }
        }


    }
}
