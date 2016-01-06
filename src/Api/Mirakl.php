<?php
namespace Hipay\MiraklConnector\Api;

use Guzzle\Http\Exception\RequestException;
use Guzzle\Service\Client;
use Guzzle\Service\Description\ServiceDescription;
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
     * @return array the response
     *
     * @throws RequestException on a request error
     */
    public function getVendors(\DateTimeInterface $updatedSince = null)
    {
        $this->restClient->getConfig()->setPath(
            'request.options/headers/Authorization',
            $this->frontKey
        );
        $command = $this->restClient->getCommand(
            'GetVendors',
            array('updatedSince' => $updatedSince)
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
        $command = $this->restClient->getCommand(
            'GetDocuments',
            array(
                'shopIds' => $shopIds
            )
        );
        return $this->restClient->execute($command)->getBody();
    }

    /**
     * Download a zip archive of documents (use S31)
     *
     * @param array $shopIds
     * @param array $documentIds
     * @param array $typeCodes
     *
     * @return mixed the zip file binary data
     */
    public function downloadFiles(
        array $shopIds = array(),
        array $documentIds = array(),
        array $typeCodes = array()
    )
    {
        $command = $this->restClient->getCommand(
            'DownloadsDocuments',
            array(
                'shopIds' => $shopIds,
                'documentIds' => $documentIds,
                'typeCodes' => $typeCodes
            )
        );
        return $this->restClient->execute($command)->getBody();
    }

    /**
     * List the transaction (use TL01)
     *
     *
     */
    public function getTransactions()
    {
        $command = $this->restClient->getCommand(
            'GetTransactions',
            array(
            )
        );
        return $this->restClient->execute($command)->getBody();
    }
}