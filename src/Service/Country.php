<?php

namespace Hipay\MiraklConnector\Service;

/**
 * Class Countries
 * Convert a ISO1366Alpha3 country code into a ISO1366-Alpha3 country code.
 *
 * @author    Ivanis Kouamé <ivanis.kouame@smile.fr>
 * @copyright 2015 Smile
 */
class Country
{
    protected static $data;

    /**
     * Return the corresponding ISO 1366-Alpha2 for the given ISO 1366-Alpha3.
     *
     * @param string $countryCode an ISO 1366-Alpha3
     *
     * @return string|false
     */
    public static function toISO1366Alpha2($countryCode)
    {
        if (!static::$data) {
            $file = fopen(__DIR__.'../../../data/countries.csv', 'r');
            while (!feof($file)) {
                $line = fgetcsv($file);
                static::$data[reset($line)] = end($line);
            }
        }

        return isset(static::$data[$countryCode]) ?
            static::$data[$countryCode] : false;
    }
}
