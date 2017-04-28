<?php

namespace HiPay\Wallet\Mirakl\Vendor\Model;

interface DocumentInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return int
     */
    public function getMiraklDocumentId();

    /**
     * @param int $miraklDocumentId
     */
    public function setMiraklDocumentId($miraklDocumentId);

    /**
     * @return DateTime
     */
    public function getMiraklUploadDate();

    /**
     * @param DateTime $miraklUploadDate
     */
    public function setMiraklUploadDate($miraklUploadDate);

    /**
     * @return int
     */
    public function getDocumentType();

    /**
     * @param int $documentType
     */
    public function setDocumentType($documentType);

    /**
     * @return mixed
     */
    public function getVendor();

    /**
     * @param mixed $vendor
     */
    public function setVendor($vendor);

}
