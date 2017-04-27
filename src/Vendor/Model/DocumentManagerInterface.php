<?php

namespace HiPay\Wallet\Mirakl\Vendor\Model;

/**
 * Interface DocumentManagerInterface
 * @package HiPay\Wallet\Mirakl\Vendor\Model
 */
interface DocumentManagerInterface
{
    /**
     * @param $miraklDocumentId
     * @param \DateTime $miraklUploadDate
     * @param $documentType
     * @param VendorInterface $vendor
     * @return DocumentInterface
     */
    public function create(
        $miraklDocumentId,
        \DateTime $miraklUploadDate,
        $documentType,
        VendorInterface $vendor
    );

    public function findByVendor(VendorInterface $vendor);

    public function saveAll(array $documents);

    public function save(DocumentInterface $document);

}
