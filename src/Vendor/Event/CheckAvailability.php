<?php
/**
 * File ${FILE_NAME}
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Vendor\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class CheckAvailability
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class CheckAvailability extends Event
{

    protected $email;

    protected $entity;

    /**
     * CheckAvailability constructor.
     * @param $email
     * @param $entity
     */
    public function __construct($email, $entity)
    {
        $this->email = $email;
        $this->entity = $entity;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param mixed $entity
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }

}