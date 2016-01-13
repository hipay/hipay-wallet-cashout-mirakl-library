<?php
namespace Hipay\MiraklConnector\Api;

use Guzzle\Service\Client;
use Guzzle\Service\Description\ServiceDescription;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class Mirakl
 * Make the calls the Mirakl Rest API
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Mirakl
{
    /** @var Client guzzle client used for the request */
    protected $restClient;

    /** @var string front api key */
    protected $frontKey;

    /** @var string shop api key */
    protected $shopKey;

    /** @var string operator api key */
    protected $operatorKey;

    /**
     * Mirakl Api Client constructor (Extends Guzzle service client)
     *
     * @param string $baseUrl
     * @param string $frontKey
     * @param string $shopKey
     * @param string $operatorKey
     * @param array|\Guzzle\Common\Collection|null $config
     */
    public function __construct(
        $baseUrl,
        $frontKey,
        $shopKey,
        $operatorKey,
        $config = array()
    )
    {
        $this->restClient = new Client($config);
        $description = ServiceDescription::factory(__DIR__ . '../../../data/api/mirakl.json');
        $description->setBaseUrl($baseUrl);
        $this->frontKey = $frontKey;
        $this->shopKey = $shopKey;
        $this->operatorKey = $operatorKey;
        $this->restClient->setDescription($description);
    }

    /**
     * Fetch from Mirakl all vendors (uses S20)
     *
     * @param \DateTimeInterface $updatedSince date of the last Update
     *
     * @param bool $paginate
     * @return array the response
     *
     */
    public function getVendors(
        \DateTimeInterface $updatedSince = null,
        $paginate = false
    )
    {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/Authorization',
            $this->frontKey
        );
        $command = $this->restClient->getCommand(
            'GetVendors',
            array(
                'updatedSince' => $updatedSince,
                'paginate' => $paginate
            )
        );
        $result = $this->restClient->execute($command);
        return $result['shops'];
    }

    /**
     * List files from Mirakl (Uses S30)
     *
     * @param array $shopIds the shops id to list document from
     *
     * @return string the JSON response
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
                'shopIds' => $shopIds
            )
        );
        return $this->restClient->execute($command)->getBody();
    }

    /**
     * Download a zip archive of documents (use S31) based on the docuements ids
     *
     * @param array $documentIds
     * @param array $typeCodes
     *
     * @return mixed the zip file binary data
     */
    public function downloadDocuments(
        array $documentIds = array(),
        array $typeCodes = array()
    )
    {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/Authorization',
            $this->frontKey
        );
        $command = $this->restClient->getCommand(
            'DownloadDocuments',
            array(
                'documentIds' => $documentIds,
                'typeCodes' => $typeCodes
            )
        );
        return $this->restClient->execute($command)->getBody();
    }

    /**
     * Download a zip archive of documents (use S31) based on the shopsid
     *
     * @param array $shopIds
     * @param array $typeCodes
     *
     * @return mixed the zip file binary data
     */
    public function downloadShopsDocuments(
        array $shopIds = array(),
        array $typeCodes = array()
    )
    {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/Authorization',
            $this->frontKey
        );
        $command = $this->restClient->getCommand(
            'DownloadDocuments',
            array(
                'shopIds' => $shopIds,
                'typeCodes' => $typeCodes
            )
        );
        return $this->restClient->execute($command)->getBody();
    }

    /**
     * List the transaction (use TL01)
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
    )
    {
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
                'orderLineIds' => $orderLineIds
            )
        );
        $result = $this->restClient->execute($command)->getBody();
        return $result['lines'];
    }
}