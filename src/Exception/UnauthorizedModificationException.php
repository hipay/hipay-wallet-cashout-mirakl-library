<?php
namespace Hipay\MiraklConnector\Exception;

use Exception;

/**
 * Class UnauthorizedModificationException
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class UnauthorizedModificationException extends DispatchableException
{
    protected $object;
    protected $modifiedProperties = array();

    /**
     * UnauthorizedModificationException constructor.
     * @param $object
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     * @param array $modifiedProperties
     */
    public function __construct(
        $object,
        $message = "",
        $code = 0,
        Exception $previous = null,
        $modifiedProperties = array()
    )
    {
        parent::__construct($message, $code, $previous);

        $this->object = $object;

        $this->modifiedProperties = $modifiedProperties;
    }

    /**
     * @return string
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @return array
     */
    public function getModifiedProperties()
    {
        return $this->modifiedProperties;
    }

    /**
     * Add a modified property
     *
     * @param $propertyName
     */
    public function addModifiedProperty($propertyName)
    {
        $this->modifiedProperties[] = $propertyName;
    }

    /**
     * Check if there is any modified property
     *
     * @return bool
     */
    public function hasModifiedProperty()
    {
        return count($this->modifiedProperties) > 0;
    }

    /**
     * Return the event name
     *
     * @return string
     */
    public function getEventName()
    {
        return 'unauthorized.property.modified';
    }
}