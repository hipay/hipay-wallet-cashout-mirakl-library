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
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
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
    public function getTransactions($shopId = null, DateTime $startDate = null, DateTime $endDate = null, DateTime $startTransactionDate = null, DateTime $endTransactionDate = null, DateTime $updatedSince = null, $paymentVoucher = null, $paymentStates = null, array $transactionTypes = array(), $paginate = false, $accountingDocumentNumber = null, array $orderIds = array(), array $orderLineIds = array());
}