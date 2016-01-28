<?php

namespace HiPay\Wallet\Mirakl\Api\Soap;

use Exception;
use SoapClient;
use SoapFault;

/**
 * SoapClient with protection on fatal error.
 *
 * @category  Smile
 *
 * @author    Laurent MINGUET <lamin@smile.fr>
 * @copyright 2015 Smile
 */
class SmileClient extends SoapClient
{
    /**
     * construct.
     *
     * @param string $wsdl    wsdl url to use
     * @param array  $options table of options
     *
     * @return SmileClient
     *
     * @link http://www.php.net/manual/en/soapclient.soapclient.php
     */
    public function __construct($wsdl = null, $options = array())
    {
        $options = $this->_initOptions($options);

        self::startErrorHandlerForFatal('WSDL');
        // the @ is mandatory !!! does not remove it !
        // @codingStandardsIgnoreStart
        $res = @parent::__construct($wsdl, $options);
        // @codingStandardsIgnoreEnd
        self::stopErrorHandlerForFatal();

        return $res;
    }

    /**
     * init the options.
     *
     * @param array $options array of options
     *
     * @return array
     */
    protected function _initOptions($options)
    {
        $defaultOptions = array(
            'soap_version' => SOAP_1_1,
            'authentication' => SOAP_AUTHENTICATION_BASIC,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
            'exceptions' => true,
            'timeout' => (int) ini_get('default_socket_timeout'), // in seconds
        );
        $options = array_merge($defaultOptions, $options);

        ini_set('default_socket_timeout', (int) $options['timeout']);

        return $options;
    }

    /******************************************************************
     *********** METHODES AND PROPERTIES FOR ERROR HANDLING ***********
     ******************************************************************/

    /**
     * default code to use for error handling.
     *
     * @var null|string
     */
    private static $_defaultCode = null;

    /**
     * start handling the error to catch fatal errors.
     *
     * @param string $defaultCode default code to use
     */
    public static function startErrorHandlerForFatal($defaultCode)
    {
        self::$_defaultCode = $defaultCode;
        set_error_handler(
            'HiPay\\MiraklConnector\\Api\\Soap\\SmileClient::errorHandlerForFatal',
            E_ALL
        );
    }

    /**
     * stop handling the error to catch fatal errors.
     */
    public static function stopErrorHandlerForFatal()
    {
        self::$_defaultCode = null;
        restore_error_handler();
    }

    /**
     * handling the error to catch fatal errors.
     *
     * @param int              $errno   error number
     * @param string|exception $errstr  error message
     * @param string           $errfile file of the error
     * @param int              $errline line of the error
     *
     * @throws SoapFault
     */
    public static function errorHandlerForFatal(
        $errno,
        $errstr,
        $errfile = null,
        $errline = null
    ) {
        $code = self::$_defaultCode;
        self::stopErrorHandlerForFatal();

        if ($errstr instanceof Exception) {
            if ($errstr->getCode()) {
                $code = $errstr->getCode();
            }
            $errstr = $errstr->getMessage();
        } elseif (!is_string($errstr)) {
            $errstr = 'Unknown error';
        }

        throw new SoapFault($code, $errstr.' (error ['.$errno.'])');
    }
}
