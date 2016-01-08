<?php
/**
 * File Constants.php
 *
 * @category
 * @package
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */

namespace Hipay\MiraklConnector\Api\Hipay;

/**
 * Class holding different constants for hipay
 * 
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
abstract class Constants
{
    // Hipay Wallet error codes.
    const HIPAY_STATUS_SUCCESS =  0;
    const HIPAY_STATUS_ERROR_AUTHENTICATION_FAILED = 1;
    const HIPAY_STATUS_ERROR_MISSING_PARAMETER = 2;
    const HIPAY_STATUS_ERROR_PARAMETER_NOT_VALID = 3;
    const HIPAY_STATUS_ERROR_UNAUTHORIZED_METHOD = 4;
    const HIPAY_STATUS_ERROR_OBJECT_NOT_FOUND = 7;
    const HIPAY_STATUS_ERROR_TECHNICAL_ERROR = 13;
    const HIPAY_STATUS_ERROR_WRONG_BANK_STATUS = 23;

    // Hipay Wallet bankInfosStatus response 'status' values.
    const HIPAY_ACCOUNT_NO_BANK_INFO = 'No bank information';
    const HIPAY_ACCOUNT_BANK_INFO_VALIDATED = 'Validated';

    // Hipay Wallet accountInfosIdentified response 'status' values.
    const HIPAY_ACCOUNT_NOT_IDENTIFIED = 'no';
    const HIPAY_ACCOUNT_IDENTIFIED = 'yes';

    // Hipay Wallet transfer transaction types.
    const HIPAY_TRANSFER_TYPE_TRANSFER = 'Envoi';
    const HIPAY_TRANSFER_TYPE_OTHER = ' Autre';

    // Hipay Wallet transfer statuses.
    const HIPAY_TRANSFER_STATUS_CAPTURED = 'CAPTURED';
}