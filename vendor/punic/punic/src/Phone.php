<?php

namespace Punic;

/**
 * Numbers helpers.
 */
class Phone
{
    /**
     * Retrieve the list of the country calling codes for a specific country.
     *
     * @param string $territoryCode The country identifier ('001' for global systems, for instance satellite communications like Iridium)
     *
     * @return array Returns the list of country calling codes found for the specified country (eg: for 'US' you'll get array('1'))
     */
    public static function getPrefixesForTerritory($territoryCode)
    {
        $result = array();
        if (is_string($territoryCode) && preg_match('/^([a-z]{2}|[0-9]{3})$/i', $territoryCode)) {
            $territoryCode = strtoupper($territoryCode);
            $data = Data::getGeneric('telephoneCodeData');
            if (isset($data[$territoryCode])) {
                $result = $data[$territoryCode];
            }
        }

        return $result;
    }

    /**
     * Retrieve the list of territory codes for a specific prefix.
     *
     * @param string $prefix The country calling code (for instance: '1')
     *
     * @return array Returns the list of territories for which the specified prefix is applicable (eg: for '1' you'll get array('US', 'AG', 'AI', 'CA', ...))
     */
    public static function getTerritoriesForPrefix($prefix)
    {
        $result = array();
        if (is_string($prefix) && preg_match('/^[0-9]+$/', $prefix)) {
            $data = Data::getGeneric('telephoneCodeData');
            foreach ($data as $territoryCode => $prefixes) {
                if (in_array($prefix, $prefixes)) {
                    $result[] = $territoryCode;
                }
            }
        }

        return $result;
    }

    /**
     * Retrieve the max length of the country calling codes.
     *
     * @return int
     */
    public static function getMaxPrefixLength()
    {
        static $result;
        if (!isset($result)) {
            $maxLen = 0;
            $data = Data::getGeneric('telephoneCodeData');
            foreach ($data as $prefixes) {
                foreach ($prefixes as $prefix) {
                    $len = strlen($prefix);
                    if ($maxLen < $len) {
                        $maxLen = $len;
                    }
                }
            }
            $result = $maxLen;
        }

        return $result;
    }
}
