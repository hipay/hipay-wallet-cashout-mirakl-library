<?php
/**
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
namespace HiPay\Wallet\Mirakl\Api\Mirakl;

use DateTime;
use HiPay\Wallet\Mirakl\Api\Mirakl;

/**
 * Make the calls the Mirakl Rest API.
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2017 HiPay
 */
interface ApiInterface
{
    /**
     * Fetch from Mirakl all vendors (uses S20).
     *
     * @param DateTime $updatedSince date of the last Update
     * @param bool $paginate
     * @param array $shopIds
     *
     * @return array the response
     */
    public function getVendors(DateTime $updatedSince = null, $paginate = false, $shopIds = array());

    /**
     * List files from Mirakl (Uses S30).
     *
     * @param array $shopIds the shops id to list document from
     *
     * @return string the JSON response
     */
    public function getFiles(array $shopIds);

    /**
     * Download a zip archive of documents (use S31) based on the documents ids.
     *
     * @param array $documentIds
     * @param array $typeCodes
     *
     * @return mixed the zip file binary data
     */
    public function downloadDocuments(array $documentIds = array(), array $typeCodes = array());

    /**
     * Download a zip archive of documents (use S31) based on the shopsId.
     *
     * @param array $shopIds
     * @param array $typeCodes
     *
     * @return mixed the zip file binary data
     */
    public function downloadShopsDocuments(array $shopIds = array(), array $typeCodes = array());

    /**
     * Fetch from Mirakl additional_fields (uses DO01).
     *
     * @param entities $entities (SHOP)
     *
     * @return array response
     */
    public function getDocumentTypesDto($entities = null);

    /**
     * controlMiraklSettings
     *
     * @return boolean
     */
    public function controlMiraklSettings($docTypes);
}