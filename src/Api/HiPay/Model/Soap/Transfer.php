<?php

namespace HiPay\Wallet\Mirakl\Api\HiPay\Model\Soap;

use HiPay\Wallet\Mirakl\Vendor\Model\VendorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Transfer.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Transfer extends ModelAbstract
{
    /**
     * @var int
     *
     * @Assert\NotBlank
     * @Assert\Type("float")
     */
    protected $amount;

    /**
     * @var int
     *
     * @Assert\NotBlank
     * @Assert\Type("integer")
     */
    protected $recipientUserId;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $recipientUsername;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $privateLabel;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $publicLabel;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    protected $entity;


    /**
     * Transfer constructor.
     *
     * @param float             $amount
     * @param VendorInterface $vendorInterface
     * @param string          $privateLabel
     * @param string          $publicLabel
     */
    public function __construct(
        $amount,
        VendorInterface $vendorInterface,
        $privateLabel,
        $publicLabel
    ) {
        $this->amount = $amount;
        $this->recipientUserId = $vendorInterface->getHiPayId();
        $this->recipientUsername = $vendorInterface->getEmail();
        $this->privateLabel = $privateLabel;
        $this->publicLabel = $publicLabel;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return int
     */
    public function getRecipientUserId()
    {
        return $this->recipientUserId;
    }

    /**
     * @return string
     */
    public function getRecipientUsername()
    {
        return $this->recipientUsername;
    }

    /**
     * @return string
     */
    public function getPrivateLabel()
    {
        return $this->privateLabel;
    }

    /**
     * @return string
     */
    public function getPublicLabel()
    {
        return $this->publicLabel;
    }

    /**
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param int $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @param int $recipientUserId
     */
    public function setRecipientUserId($recipientUserId)
    {
        $this->recipientUserId = $recipientUserId;
    }

    /**
     * @param string $recipientUsername
     */
    public function setRecipientUsername($recipientUsername)
    {
        $this->recipientUsername = $recipientUsername;
    }

    /**
     * @param string $privateLabel
     */
    public function setPrivateLabel($privateLabel)
    {
        $this->privateLabel = $privateLabel;
    }

    /**
     * @param string $publicLabel
     */
    public function setPublicLabel($publicLabel)
    {
        $this->publicLabel = $publicLabel;
    }

    /**
     * @param string $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

}
