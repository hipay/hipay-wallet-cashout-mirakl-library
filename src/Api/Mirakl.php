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
    const DOCUMENT_ALL_PROOF_OF_BANK_ACCOUNT = 'ALL_PROOF_OF_BANK_ACCOUNT';

    // For legal entity businesses only
    const DOCUMENT_LEGAL_IDENTITY_OF_REPRESENTATIVE = 'LEGAL_IDENTITY_OF_REPRESENTATIVE';
    const DOCUMENT_LEGAL_IDENTITY_OF_REP_REAR = 'LEGAL_IDENTITY_OF_REP_REAR';
    const DOCUMENT_LEGAL_PROOF_OF_REGISTRATION_NUMBER = 'LEGAL_PROOF_OF_REGISTRATION_NUMBER';
    const DOCUMENT_LEGAL_ARTICLES_DISTR_OF_POWERS = 'LEGAL_ARTICLES_DISTR_OF_POWERS';

    // For one man businesses only
    const DOCUMENT_SOLE_MAN_BUS_IDENTITY = 'SOLE_MAN_BUS_IDENTITY';
    const DOCUMENT_SOLE_MAN_BUS_IDENTITY_REAR = 'SOLE_MAN_BUS_IDENTITY_REAR';
    const DOCUMENT_SOLE_MAN_BUS_PROOF_OF_REG_NUMBER = 'SOLE_MAN_BUS_PROOF_OF_REG_NUMBER';
    const DOCUMENT_SOLE_MAN_BUS_PROOF_OF_TAX_STATUS = 'SOLE_MAN_BUS_PROOF_OF_TAX_STATUS';

    const MIRAKL_API_MAX_PAGINATE = 100;
    const MIRAKL_API_DEFAULT_OFFSET_PAGINATE = 0;

    /** @var Client guzzle client used for the request */
    protected $restClient;

    /** @var string front api key */
    protected $frontKey;

    /** @var string operator api key */
    protected $operatorKey;

    /**
     * Mirakl Api Client constructor (Extends Guzzle service client).
     *
     * @param string $baseUrl
     * @param string $frontKey
     * @param string $operatorKey
     * @param array|\Guzzle\Common\Collection|null $config
     */
    public function __construct(
        $baseUrl,
        $frontKey,
        $operatorKey,
        $config = array()
    ) {
        $this->restClient = new Client($config);
        $description = ServiceDescription::factory(__DIR__ . '../../../data/api/mirakl.json');
        $description->setBaseUrl($baseUrl);
        $this->frontKey = $frontKey;
        $this->operatorKey = $operatorKey;
        $this->restClient->setDescription($description);
    }

    /**
     * Fetch from Mirakl all vendors (uses S20).
     *
     * @param DateTime $updatedSince date of the last Update
     * @param bool $paginate
     * @param array $shopIds
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
     * List invoices (use IV01)
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param type $shopId
     * @param type $type
     * @param type $currency
     * @return type
     */
    public function getInvoices(
        DateTime $startDate = null,
        DateTime $endDate = null,
        $max = self::MIRAKL_API_MAX_PAGINATE,
        $offset = self::MIRAKL_API_DEFAULT_OFFSET_PAGINATE,
        $shopId = null,
        $type = 'AUTO_INVOICE',
        $currency = null
    ) {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/Authorization',
            $this->frontKey
        );
        $command = $this->restClient->getCommand(
            'GetInvoices',
            array(
                'shopId' => $shopId,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'type' => $type,
                'currency' => $currency,
                'max' => $max,
                'offset' => $offset
            )
        );
        $result = $this->restClient->execute($command);

        return $result;
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
        foreach ($documentDto as $document) {
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
        $cpt = $cpt - ($cptLegal + $cptSoleMan);
        $countDocHiPay = $countDocHiPay - ($cptLegal + $cptSoleMan);
        // if equal it's ok else mirakl settings not ok
        if ($countDocHiPay == $cpt) {
            $bool = true;
        } else {
            $bool = false;
        }
        return $bool;
    }

}