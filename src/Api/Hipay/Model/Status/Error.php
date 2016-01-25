<?php

namespace Hipay\MiraklConnector\Api\Hipay\Model\Status;

use Hipay\MiraklConnector\Common\AbstractEnumeration;

/**
 * Class Error.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Error extends AbstractEnumeration
{
    // Hipay Wallet error codes.
    const AUTHENTICATION_FAILED = 1;
    const MISSING_PARAMETER = 2;
    const PARAMETER_NOT_VALID = 3;
    const UNAUTHORIZED_METHOD = 4;
    const OBJECT_NOT_FOUND = 7;
    const TECHNICAL_ERROR = 13;
    const WRONG_BANK_STATUS = 23;
}
