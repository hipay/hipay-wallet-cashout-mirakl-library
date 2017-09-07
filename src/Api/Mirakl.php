<?php

namespace HiPay\Wallet\Mirakl\Api;

use DateTime;
use Guzzle\Service\Client;
use Guzzle\Service\Command\AbstractCommand;
use Guzzle\Service\Description\ServiceDescription;
use HiPay\Wallet\Mirakl\Api\Mirakl\ApiInterface;

/**
 * Make the calls the Mirakl Rest API.
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
class Mirakl implements ApiInterface
{
    // For all types of businesses
    CONST DOCUMENT_ALL_PROOF_OF_BANK_ACCOUNT = 'ALL_PROOF_OF_BANK_ACCOUNT';

    // For legal entity businesses only
    CONST DOCUMENT_LEGAL_IDENTITY_OF_REPRESENTATIVE = 'LEGAL_IDENTITY_OF_REPRESENTATIVE';
    CONST DOCUMENT_LEGAL_PROOF_OF_REGISTRATION_NUMBER = 'LEGAL_PROOF_OF_REGISTRATION_NUMBER';
    CONST DOCUMENT_LEGAL_ARTICLES_DISTR_OF_POWERS = 'LEGAL_ARTICLES_DISTR_OF_POWERS';

    // For one man businesses only
    CONST DOCUMENT_SOLE_MAN_BUS_IDENTITY = 'SOLE_MAN_BUS_IDENTITY';
    CONST DOCUMENT_SOLE_MAN_BUS_PROOF_OF_REG_NUMBER = 'SOLE_MAN_BUS_PROOF_OF_REG_NUMBER';
    CONST DOCUMENT_SOLE_MAN_BUS_PROOF_OF_TAX_STATUS = 'SOLE_MAN_BUS_PROOF_OF_TAX_STATUS';

    /** @var Client guzzle client used for the request */
    protected $restClient;

    /** @var string front api key */
    protected $frontKey;

    /** @var string operator api key */
    protected $operatorKey;

    /**
     * Mirakl Api Client constructor (Extends Guzzle service client).
     *
     * @param string                               $baseUrl
     * @param string                               $frontKey
     * @param string                               $operatorKey
     * @param array|\Guzzle\Common\Collection|null $config
     */
    public function __construct(
        $baseUrl,
        $frontKey,
        $operatorKey,
        $config = array()
    ) {
        $this->restClient = new Client($config);
        $description = ServiceDescription::factory(__DIR__.'../../../data/api/mirakl.json');
        $description->setBaseUrl($baseUrl);
        $this->frontKey = $frontKey;
        $this->operatorKey = $operatorKey;
        $this->restClient->setDescription($description);
    }

    /**
     * Fetch from Mirakl all vendors (uses S20).
     *
     * @param DateTime $updatedSince date of the last Update
     * @param bool     $paginate
     * @param array    $shopIds
     *
     * @return array the response
     */
    public function getVendors(
        DateTime $updatedSince = null,
        $paginate = false,
        $shopIds = array()
    ) {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/Authorization',
            $this->frontKey
        );
        $command = $this->restClient->getCommand(
            'GetVendors',
            array(
                'updatedSince' => $updatedSince,
                'paginate' => $paginate,
                'shopIds' => $shopIds,
            )
        );
        $result = $this->restClient->execute($command);

        return $result['shops'];
    }

    /**
     * List files from Mirakl (Uses S30).
     *
     * @param array $shopIds the shops id to list document from
     *
     * @return array The documents
     */
    public function getFiles(array $shopIds)
    {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/Authorization',
            $this->frontKey
        );
        $command = $this->restClient->getCommand(
            'GetDocuments',
            array(
                'shopIds' => $shopIds,
            )
        );

        $result = $this->restClient->execute($command);

        return $result['shop_documents'];
    }

    /**
     * Download a zip archive of documents (use S31) based on the documents ids.
     *
     * @param array $documentIds
     * @param array $typeCodes
     *
     * @return mixed the zip file binary data
     */
    public function downloadDocuments(
        array $documentIds = array(),
        array $typeCodes = array()
    ) {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/Authorization',
            $this->frontKey
        );
        $command = $this->restClient->getCommand(
            'DownloadDocuments',
            array(
                'documentIds' => $documentIds,
                'typeCodes' => $typeCodes,
            )
        );

        $command[AbstractCommand::RESPONSE_PROCESSING] = AbstractCommand::TYPE_RAW;

        return $this->restClient->execute($command)->getBody();
    }

    /**
     * Download a zip archive of documents (use S31) based on the shopsId.
     *
     * @param array $shopIds
     * @param array $typeCodes
     *
     * @return mixed the zip file binary data
     */
    public function downloadShopsDocuments(
        array $shopIds = array(),
        array $typeCodes = array()
    ) {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/Authorization',
            $this->frontKey
        );
        $command = $this->restClient->getCommand(
            'DownloadDocuments',
            array(
                'shopIds' => $shopIds,
                'typeCodes' => $typeCodes,
            )
        );

        return $this->restClient->execute($command)->getBody();
    }

    /**
     * List the transaction (use TL01).
     *
     * @param $shopId
     * @param $startDate
     * @param $endDate
     * @param $startTransactionDate
     * @param $endTransactionDate
     * @param $updatedSince
     * @param $paymentVoucher
     * @param $paymentStates
     * @param $transactionTypes
     * @param $paginate
     * @param $accountingDocumentNumber
     * @param $orderIds
     * @param $orderLineIds
     *
     * @return \Guzzle\Http\EntityBodyInterface|string
     */
    public function getTransactions(
        $shopId = null,
        DateTime $startDate = null,
        DateTime $endDate = null,
        DateTime $startTransactionDate = null,
        DateTime $endTransactionDate = null,
        DateTime $updatedSince = null,
        $paymentVoucher = null,
        $paymentStates = null,
        array $transactionTypes = array(),
        $paginate = false,
        $accountingDocumentNumber = null,
        array $orderIds = array(),
        array $orderLineIds = array()
    ) {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/Authorization',
            $this->frontKey
        );
        $command = $this->restClient->getCommand(
            'GetTransactions',
            array(
                'shopId' => $shopId,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'startTransactionDate' => $startTransactionDate,
                'endTransactionDate' => $endTransactionDate,
                'updatedSince' => $updatedSince,
                'paymentVoucher' => $paymentVoucher,
                'paymentStates' => $paymentStates,
                'transactionTypes' => $transactionTypes,
                'paginate' => $paginate,
                'accountingDocumentNumber' => $accountingDocumentNumber,
                'orderIds' => $orderIds,
                'orderLineIds' => $orderLineIds,
            )
        );
        $result = $this->restClient->execute($command);

        return $result['lines'];
    }

    /**
     * Fetch from Mirakl additional_fields (uses DO01).
     *
     * @param entities $entities (SHOP)
     *
     * @return array the response
     */
    public function getDocumentTypesDto(
        $entities = 'SHOP'
    ) {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/Authorization',
            $this->operatorKey
        );

        $command = $this->restClient->getCommand(
            'DocumentTypesDto',
            array(
                'entities' => $entities,
            )
        );
        $result = $this->restClient->execute($command);

        return $result['documents'];
    }

    /**
     * Control if the mirakl settings is ok with the HiPay Prerequisites
     *
     * @return boolean
     */
    public function controlMiraklSettings($docTypes)
    {
        // init mirakl settings by API Mirakl
        $documentDto = $this->getDocumentTypesDto();
        $countDocHiPay = count($docTypes);
        $cpt = 0;
        $cptLegal = 3;
        $cptSoleMan = 3;

        // control exist between mirakl settings and HiPay
        foreach ($documentDto as $document)
        {
            $pattern1 = '/^LEGAL_/';
            $pattern2 = '/^SOLE_MAN_/';
            // read if document mirakl is a Legal document
            if (preg_match($pattern1, $document['code'])) {
                $cptLegal--;
            }
            // read if document mirakl is a Sole man document
            if (preg_match($pattern2, $document['code'])) {
                $cptSoleMan--;
            }
            // if exist in HiPay Prerequisites
            if (array_key_exists($document['code'], $docTypes)) {
                $cpt++;
            }
        }

        // Update count calculation
        if ($cptLegal < 0) {
            $cptLegal = 0;
        }
        if ($cptSoleMan < 0) {
            $cptSoleMan = 0;
        }
        // calcul count cpt and countDocHiPay
        $cpt = $cpt-($cptLegal+$cptSoleMan);
        $countDocHiPay = $countDocHiPay-($cptLegal+$cptSoleMan);
        // if equal it's ok else mirakl settings not ok
        if ($countDocHiPay == $cpt) {
            $bool = true;
        } else {
            $bool = false;
        }
        return $bool;
    }
}
