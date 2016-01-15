<?php
/**
 * File Transfer.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Api\Hipay\Model\Soap;
use Hipay\MiraklConnector\Vendor\VendorInterface;

/**
 * Class Transfer
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Transfer extends ModelAbstract
{
    /**
     * @var int
     */
    protected $amount;

    /**
     * @var int
     */
    protected $recipientUserId;

    /**
     * @var string
     */
    protected $recipientUsername;

    /**
     * @var string
     */
    protected $privateLabel;

    /**
     * @var string
     */
    protected $publicLabel;

    /**
     * @var string
     */
    protected $entity;

    /**
     * Transfert constructor.
     * @param int $amount
     * @param VendorInterface $vendorInterface
     * @param string $privateLabel
     * @param string $publicLabel
     */
    public function __construct(
        $amount,
        VendorInterface $vendorInterface,
        $privateLabel,
        $publicLabel
    )
    {
        $this->amount = $amount;
        $this->recipientUserId = $vendorInterface->getHipayId();
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
}