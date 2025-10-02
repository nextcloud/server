<?php

namespace Punic;

/**
 * Units helper stuff.
 */
class Unit
{
    /**
     * Get the list of all the available units.
     *
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     */
    public static function getAvailableUnits($locale = '')
    {
        $data = Data::get('units', $locale);
        $categories = array();
        foreach ($data as $width => $units) {
            if ($width[0] !== '_') {
                foreach ($units as $category => $units) {
                    if ($category[0] !== '_') {
                        $unitIDs = array_keys($units);
                        if (isset($categories[$category])) {
                            $categories[$category] = array_unique(array_merge($categories[$category], $unitIDs));
                        } else {
                            $categories[$category] = array_keys($units);
                        }
                    }
                }
            }
        }
        ksort($categories);
        foreach (array_keys($categories) as $category) {
            sort($categories[$category]);
        }

        return $categories;
    }

    /**
     * Get the localized name of a unit.
     *
     * @param string $unit The unit identifier (eg 'duration/millisecond' or 'millisecond')
     * @param string $width The format name; it can be 'long' ('milliseconds'), 'short' (eg 'millisecs') or 'narrow' (eg 'msec')
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws Exception\ValueNotInList
     *
     * @return string
     */
    public static function getName($unit, $width = 'short', $locale = '')
    {
        $data = self::getDataForWidth($width, $locale);
        $unitData = self::getDataForUnit($data, $unit);

        return $unitData['_name'];
    }

    /**
     * Get the "per" localized format string of a unit.
     *
     * @param string $unit The unit identifier (eg 'duration/minute' or 'minute')
     * @param string $width The format name; it can be 'long' ('%1$s per minute'), 'short' (eg '%1$s/min') or 'narrow' (eg '%1$s/min')
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws Exception\ValueNotInList
     *
     * @return string
     */
    public static function getPerFormat($unit, $width = 'short', $locale = '')
    {
        $data = self::getDataForWidth($width, $locale);
        $unitData = self::getDataForUnit($data, $unit);

        if (isset($unitData['_per'])) {
            return $unitData['_per'];
        }
        $pluralRule = Plural::getRuleOfType(1, Plural::RULETYPE_CARDINAL, $locale);
        $name = trim(sprintf($unitData[$pluralRule], ''));

        return sprintf($data['_compoundPattern'], '%1$s', $name);
    }

    /**
     * Format a unit string.
     *
     * @param int|float|string $number The unit amount
     * @param string $unit The unit identifier (eg 'duration/millisecond' or 'millisecond')
     * @param string $width The format name; it can be 'long' (eg '3 milliseconds'), 'short' (eg '3 ms') or 'narrow' (eg '3ms'). You can also add a precision specifier ('long,2' or just '2')
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws Exception\ValueNotInList
     *
     * @return string
     */
    public static function format($number, $unit, $width = 'short', $locale = '')
    {
        $precision = null;
        $m = null;
        if (is_int($width)) {
            $precision = $width;
            $width = 'short';
        } elseif (is_string($width) && preg_match('/^(?:(.*),)?([+\\-]?\\d+)$/', $width, $m)) {
            $precision = (int) $m[2];
            $width = (string) $m[1];
            if ($width === '') {
                $width = 'short';
            }
        }
        $data = self::getDataForWidth($width, $locale);
        $rules = self::getDataForUnit($data, $unit);
        $pluralRule = Plural::getRuleOfType($number, Plural::RULETYPE_CARDINAL, $locale);
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
            while ($territoryCode !== '') {
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
        if (is_string($measurementSystem) && $measurementSystem !== '') {
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
            while ($territoryCode !== '') {
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
        if (is_string($paperSize) && $paperSize !== '') {
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

    /**
     * Get the width-specific unit data.
     *
     * @param string $width the data width
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws Exception\ValueNotInList
     *
     * @return array
     */
    private static function getDataForWidth($width, $locale = '')
    {
        $data = Data::get('units', $locale);
        if ($width[0] === '_' || !isset($data[$width])) {
            $widths = array();
            foreach (array_keys($data) as $w) {
                if (strpos($w, '_') !== 0) {
                    $widths[] = $w;
                }
            }
            throw new Exception\ValueNotInList($width, $widths);
        }

        return $data[$width];
    }

    /**
     * Get a unit-specific data.
     *
     * @param array $data the width-specific data
     * @param string $unit The unit identifier (eg 'duration/millisecond' or 'millisecond')
     *
     * @throws Exception\ValueNotInList
     *
     * @return array
     */
    private static function getDataForUnit(array $data, $unit)
    {
        $chunks = explode('/', $unit, 2);
        if (isset($chunks[1])) {
            list($unitCategory, $unitID) = $chunks;
        } else {
            $unitCategory = null;
            $unitID = null;
            foreach (array_keys($data) as $c) {
                if ($c[0] !== '_') {
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
        }
        if (
            $unitCategory === null || $unitCategory[0] === '_'
            || !isset($data[$unitCategory])
            || $unitID === null || $unitID[0] === '_'
            || !isset($data[$unitCategory][$unitID])
            ) {
            $units = array();
            foreach ($data as $c => $us) {
                if (strpos($c, '_') === false) {
                    foreach (array_keys($us) as $u) {
                        if (strpos($c, '_') === false) {
                            $units[] = "{$c}/{$u}";
                        }
                    }
                }
            }
            throw new \Punic\Exception\ValueNotInList($unit, $units);
        }

        return $data[$unitCategory][$unitID];
    }
}
