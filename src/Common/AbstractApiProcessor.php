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

    /** @var Mirakl\ConfigurationInterface $miraklConfig */
    protected $miraklConfig;

    /** @var HiPay documents $documentTypes */
    /** @var array documents additional fields */
    public $documentTypes = array(
        // For all types of businesses
        Mirakl::DOCUMENT_ALL_PROOF_OF_BANK_ACCOUNT => HiPay::DOCUMENT_ALL_PROOF_OF_BANK_ACCOUNT,
        // For individual only
        //'INDIVIDUAL_IDENTITY' => HiPay::DOCUMENT_INDIVIDUAL_IDENTITY,
        //'INDIVIDUAL_PROOF_OF_ADDRESS' => HiPay::DOCUMENT_INDIVIDUAL_PROOF_OF_ADDRESS,
        // For legal entity businesses only
        Mirakl::DOCUMENT_LEGAL_IDENTITY_OF_REPRESENTATIVE => HiPay::DOCUMENT_LEGAL_IDENTITY_OF_REPRESENTATIVE,
        //Mirakl::DOCUMENT_LEGAL_IDENTITY_OF_REP_REAR => HiPay::DOCUMENT_LEGAL_IDENTITY_OF_REPRESENTATIVE,
        Mirakl::DOCUMENT_LEGAL_PROOF_OF_REGISTRATION_NUMBER => HiPay::DOCUMENT_LEGAL_PROOF_OF_REGISTRATION_NUMBER,
        Mirakl::DOCUMENT_LEGAL_ARTICLES_DISTR_OF_POWERS => HiPay::DOCUMENT_LEGAL_ARTICLES_DISTR_OF_POWERS,
        // For one man businesses only
        Mirakl::DOCUMENT_SOLE_MAN_BUS_IDENTITY => HiPay::DOCUMENT_SOLE_MAN_BUS_IDENTITY,
        //Mirakl::DOCUMENT_SOLE_MAN_BUS_IDENTITY_REAR => HiPay::DOCUMENT_SOLE_MAN_BUS_IDENTITY,
        Mirakl::DOCUMENT_SOLE_MAN_BUS_PROOF_OF_REG_NUMBER => HiPay::DOCUMENT_SOLE_MAN_BUS_PROOF_OF_REG_NUMBER,
        Mirakl::DOCUMENT_SOLE_MAN_BUS_PROOF_OF_TAX_STATUS => HiPay::DOCUMENT_SOLE_MAN_BUS_PROOF_OF_TAX_STATUS
    );

    public $backDocumentTypes = array(
        Mirakl::DOCUMENT_LEGAL_IDENTITY_OF_REPRESENTATIVE => Mirakl::DOCUMENT_LEGAL_IDENTITY_OF_REP_REAR,
        Mirakl::DOCUMENT_SOLE_MAN_BUS_IDENTITY => Mirakl::DOCUMENT_SOLE_MAN_BUS_IDENTITY_REAR,
    );

    /** @var string critical message about mirakl settings */
    public $criticalMessageMiraklSettings = "Your Mirakl account is not configured with the HiPay prerequisites as indicated in the HiPay documentation. You must configure the Mirakl account with the additional fields.";

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
