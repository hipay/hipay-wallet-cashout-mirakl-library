<?php

namespace HiPay\Wallet\Mirakl\Test\Stub\Entity;
use HiPay\Wallet\Mirakl\Vendor\Model\DocumentInterface;

/**
 *
 * @author    Jonathan Tiret <jtiret@hipay.com>
 */
class Document implements DocumentInterface
{

    protected $id;
    protected $miraklDocumentId;
    protected $miraklUploadDate;
    protected $documentType;
    protected $vendor;

    /**
     * Document constructor.
     * @param $miraklDocumentId
     * @param $documentType
     */
    public function __construct($miraklDocumentId = null, $documentType = null)
    {
        $this->miraklDocumentId = $miraklDocumentId;
        $this->documentType = $documentType;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getMiraklDocumentId()
    {
        return $this->miraklDocumentId;
    }

    /**
     * @param mixed $miraklDocumentId
     */
    public function setMiraklDocumentId($miraklDocumentId)
    {
        $this->miraklDocumentId = $miraklDocumentId;
    }

    /**
     * @return mixed
     */
    public function getMiraklUploadDate()
    {
        return $this->miraklUploadDate;
    }

    /**
     * @param mixed $miraklUploadDate
     */
    public function setMiraklUploadDate($miraklUploadDate)
    {
        $this->miraklUploadDate = $miraklUploadDate;
    }

    /**
     * @return mixed
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * @param mixed $documentType
     */
    public function setDocumentType($documentType)
    {
        $this->documentType = $documentType;
    }

    /**
     * @return mixed
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @param mixed $vendor
     */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;
    }


}