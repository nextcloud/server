<?php

namespace Punic;

/**
 * Units helper stuff.
 */
class Unit
{
    /**
     * Format a unit string.
     *
     * @param int|float|string $number The unit amount
     * @param string $unit The unit identifier (eg 'duration/millisecond' or 'millisecond')
     * @param string $width The format name; it can be 'long' (eg '3 milliseconds'), 'short' (eg '3 ms') or 'narrow' (eg '3ms'). You can also add a precision specifier ('long,2' or just '2')
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string
     *
     * @throws Exception\ValueNotInList
     */
    public static function format($number, $unit, $width = 'short', $locale = '')
    {
        $data = Data::get('units', $locale);
        $precision = null;
        if (is_int($width)) {
            $precision = $width;
            $width = 'short';
        } elseif (is_string($width) && preg_match('/^(?:(.*),)?([+\\-]?\\d+)$/', $width, $m)) {
            $precision = intval($m[2]);
            $width = $m[1];
            if (!isset($width[0])) {
                $width = 'short';
            }
        }
        if ((strpos($width, '_') === 0) || (!isset($data[$width]))) {
            $widths = array();
            foreach (array_keys($data) as $w) {
                if (strpos($w, '_') !== 0) {
                    $widths[] = $w;
                }
            }
            throw new Exception\ValueNotInList($width, $widths);
        }
        $data = $data[$width];
        if (strpos($unit, '/') === false) {
            $unitCategory = null;
            $unitID = null;
            foreach (array_keys($data) as $c) {
                if (strpos($c, '_') === false) {
                    if (isset($data[$c][$unit])) {
                        if ($unitCategory === null) {
                            $unitCategory = $c;
                            $unitID = $unit;
                        } else {
                            $unitCategory = null;
                            break;
                        }
                    }
                }
            }
        } else {
            list($unitCategory, $unitID) = explode('/', $unit, 2);
        }
        $rules = null;
        if ((strpos($unit, '_') === false) && ($unitCategory !== null) && ($unitID !== null) && isset($data[$unitCategory]) && array_key_exists($unitID, $data[$unitCategory])) {
            $rules = $data[$unitCategory][$unitID];
        }
        if ($rules === null) {
            $units = array();
            foreach ($data as $c => $us) {
                if (strpos($c, '_') === false) {
                    foreach (array_keys($us) as $u) {
                        if (strpos($c, '_') === false) {
                            $units[] = "$c/$u";
                        }
                    }
                }
            }
            throw new \Punic\Exception\ValueNotInList($unit, $units);
        }
        $pluralRule = Plural::getRule($number, $locale);
        //@codeCoverageIgnoreStart
        // These checks aren't necessary since $pluralRule should always be in $rules, but they don't hurt ;)
        if (!isset($rules[$pluralRule])) {
            if (isset($rules['other'])) {
                $pluralRule = 'other';
            } else {
                $availableRules = array_keys($rules);
                $pluralRule = $availableRules[0];
            }
        }
        //@codeCoverageIgnoreEnd
        return sprintf($rules[$pluralRule], Number::format($number, $precision, $locale));
    }

    /**
     * Retrieve the measurement systems and their localized names.
     *
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return array The array keys are the measurement system codes (eg 'metric', 'US', 'UK'), the values are the localized measurement system names (eg 'Metric', 'US', 'UK' for English)
     */
    public static function getMeasurementSystems($locale = '')
    {
        return Data::get('measurementSystemNames', $locale);
    }

    /**
     * Retrieve the measurement system for a specific territory.
     *
     * @param string $territoryCode The territory code (eg. 'US' for 'United States of America').
     *
     * @return string Return the measurement system code (eg: 'metric') for the specified territory. If $territoryCode is not valid we'll return an empty string.
     */
    public static function getMeasurementSystemFor($territoryCode)
    {
        $result = '';
        if (is_string($territoryCode) && preg_match('/^[a-z0-9]{2,3}$/i', $territoryCode)) {
            $territoryCode = strtoupper($territoryCode);
            $data = Data::getGeneric('measurementData');
            while (isset($territoryCode[0])) {
                if (isset($data['measurementSystem'][$territoryCode])) {
                    $result = $data['measurementSystem'][$territoryCode];
                    break;
                }
                $territoryCode = Territory::getParentTerritoryCode($territoryCode);
            }
        }

        return $result;
    }

    /**
     * Returns the list of countries that use a specific measurement system.
     *
     * @param string $measurementSystem The measurement system identifier ('metric', 'US' or 'UK')
     *
     * @return array The list of country IDs that use the specified measurement system (if $measurementSystem is invalid you'll get an empty array)
     */
    public static function getCountriesWithMeasurementSystem($measurementSystem)
    {
        $result = array();
        if (is_string($measurementSystem) && (isset($measurementSystem[0]))) {
            $someGroup = false;
            $data = Data::getGeneric('measurementData');
            foreach ($data['measurementSystem'] as $territory => $ms) {
                if (strcasecmp($measurementSystem, $ms) === 0) {
                    $children = Territory::getChildTerritoryCodes($territory, true);
                    if (empty($children)) {
                        $result[] = $territory;
                    } else {
                        $someGroup = true;
                        $result = array_merge($result, $children);
                    }
                }
            }
            if ($someGroup) {
                $otherCountries = array();
                foreach ($data['measurementSystem'] as $territory => $ms) {
                    if (($territory !== '001') && (strcasecmp($measurementSystem, $ms) !== 0)) {
                        $children = Territory::getChildTerritoryCodes($territory, true);
                        if (empty($children)) {
                            $otherCountries[] = $territory;
                        } else {
                            $otherCountries = array_merge($otherCountries, $children);
                        }
                    }
                }
                $result = array_values(array_diff($result, $otherCountries));
            }
        }

        return $result;
    }

    /**
     * Retrieve the standard paper size for a specific territory.
     *
     * @param string $territoryCode The territory code (eg. 'US' for 'United States of America').
     *
     * @return string Return the standard paper size (eg: 'A4' or 'US-Letter') for the specified territory. If $territoryCode is not valid we'll return an empty string.
     */
    public static function getPaperSizeFor($territoryCode)
    {
        $result = '';
        if (is_string($territoryCode) && preg_match('/^[a-z0-9]{2,3}$/i', $territoryCode)) {
            $territoryCode = strtoupper($territoryCode);
            $data = Data::getGeneric('measurementData');
            while (isset($territoryCode[0])) {
                if (isset($data['paperSize'][$territoryCode])) {
                    $result = $data['paperSize'][$territoryCode];
                    break;
                }
                $territoryCode = Territory::getParentTerritoryCode($territoryCode);
            }
        }

        return $result;
    }

    /**
     * Returns the list of countries that use a specific paper size by default.
     *
     * @param string $paperSize The paper size identifier ('A4' or 'US-Letter')
     *
     * @return array The list of country IDs that use the specified paper size (if $paperSize is invalid you'll get an empty array)
     */
    public static function getCountriesWithPaperSize($paperSize)
    {
        $result = array();
        if (is_string($paperSize) && (isset($paperSize[0]))) {
            $someGroup = false;
            $data = Data::getGeneric('measurementData');
            foreach ($data['paperSize'] as $territory => $ms) {
                if (strcasecmp($paperSize, $ms) === 0) {
                    $children = Territory::getChildTerritoryCodes($territory, true);
                    if (empty($children)) {
                        $result[] = $territory;
                    } else {
                        $someGroup = true;
                        $result = array_merge($result, $children);
                    }
                }
            }
            if ($someGroup) {
                $otherCountries = array();
                foreach ($data['paperSize'] as $territory => $ms) {
                    if (($territory !== '001') && (strcasecmp($paperSize, $ms) !== 0)) {
                        $children = Territory::getChildTerritoryCodes($territory, true);
                        if (empty($children)) {
                            $otherCountries[] = $territory;
                        } else {
                            $otherCountries = array_merge($otherCountries, $children);
                        }
                    }
                }
                $result = array_values(array_diff($result, $otherCountries));
            }
        }

        return $result;
    }
}
