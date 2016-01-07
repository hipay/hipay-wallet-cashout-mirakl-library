<?php
/**
 * File AbstractEvent.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Vendor\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class AbstractEvent
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class AbstractEvent extends Event
{
    protected $miraklData;

    /**
     * AbstractEvent constructor.
     *
     * @param array $miraklData
     */
    public function __construct(array $miraklData)
    {
        $this->miraklData = $miraklData;
    }
}