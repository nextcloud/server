<?php

namespace Punic;

/**
 * Territory-related stuff.
 */
class Territory
{
    private static $CODE_TYPES = array('alpha3', 'numeric', 'fips10');

    /**
     * Retrieve the name of a territory/subdivision (country, continent, ...).
     *
     * @param string $territoryCode The territory/subdivision code
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string Returns the localized territory/subdivision name (returns $territoryCode if not found)
     */
    public static function getName($territoryCode, $locale = '')
    {
        $result = $territoryCode;
        if (preg_match('/^[a-z0-9]{2,5}$/i', $territoryCode)) {
            if (strlen($territoryCode) == 2 || (int) $territoryCode > 0) {
                $territoryCode = strtoupper($territoryCode);
                $data = Data::get('territories', $locale);
            } else {
                $territoryCode = strtolower($territoryCode);
                $data = Data::get('subdivisions', $locale);
            }
            if (isset($data[$territoryCode])) {
                $result = $data[$territoryCode];
            }
        }

        return $result;
    }

    /**
     * Retrieve the code of a territory in a different coding system.
     *
     * @param string $territoryCode The territory code
     * @param string $type The type of code to return. "alpha3" for ISO 3166-1 alpha-3 codes, "numeric" for UN M.49, or "fips10" for FIPS 10 codes
     *
     * @throws \Punic\Exception\ValueNotInList throws a ValueNotInList exception if $type is not valid
     *
     * @return string|array returns the code for the specified territory, or an empty string if the code is not defined for the territory or the territory is unknown
     *
     * @see http://unicode.org/reports/tr35/tr35-info.html#Supplemental_Code_Mapping
     */
    public static function getCode($territoryCode, $type)
    {
        $codeMappings = Data::getGeneric('codeMappings');
        $territories = $codeMappings['territories'];

        if (!in_array($type, static::$CODE_TYPES)) {
            throw new Exception\ValueNotInList($type, static::$CODE_TYPES);
        }

        if (!isset($territories[$territoryCode])) {
            $result = '';
        } elseif (isset($territories[$territoryCode][$type])) {
            $result = $territories[$territoryCode][$type];
        } elseif ($type !== 'numeric' && $type !== 'alpha3') {
            $result = $territoryCode;
        } else {
            $result = '';
        }

        return $result;
    }

    /**
     * Retrieve the territory code given its code in a different coding system.
     *
     * @param string $code The code
     * @param string $type The type of code provided. "alpha3" for ISO 3166-1 alpha-3 codes, "numeric" for UN M.49, or "fips10" for FIPS 10 codes
     *
     * @throws \Punic\Exception\ValueNotInList throws a ValueNotInList exception if $type is not valid
     *
     * @return string returns the code for the specified territory, or null if the code is unknown
     *
     * @see http://unicode.org/reports/tr35/tr35-info.html#Supplemental_Code_Mapping
     */
    public static function getByCode($code, $type)
    {
        $codeMappings = Data::getGeneric('codeMappings');
        $territories = $codeMappings['territories'];

        if (!in_array($type, static::$CODE_TYPES)) {
            throw new Exception\ValueNotInList($type, static::$CODE_TYPES);
        }

        foreach ($territories as $territoryCode => $territory) {
            $c = isset($territory[$type]) ? $territory[$type] : $territoryCode;
            if (is_array($c)) {
                if (in_array($code, $c)) {
                    return $territoryCode;
                }
            } elseif ($code == $c) {
                return $territoryCode;
            }
        }

        return null;
    }

    /**
     * Return the list of continents in the form of an array with key=ID, value=name.
     *
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return array
     */
    public static function getContinents($locale = '')
    {
        return static::getList('C', $locale);
    }

    /**
     * Return the list of countries in the form of an array with key=ID, value=name.
     *
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return array
     */
    public static function getCountries($locale = '')
    {
        return static::getList('c', $locale);
    }

    /**
     * Return a list of continents and relative countries. The resulting array is in the following form (JSON representation):
     * ```json
     * {
     *     "002": {
     *         "name": "Africa",
     *         "children": {
     *             "DZ": {"name": "Algeria"},
     *             "AO": {"name": "Angola"},
     *             ...
     *         }
     *     },
     *     "150": {
     *         "name": "Europe",
     *         "children": {
     *             "AL": {"name": "Albania"},
     *             "AD": {"name": "Andorra"},
     *             ...
     *         }
     *     }
     *     ...
     * }
     * ```
     * The arrays are sorted by territory name.
     *
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return array
     */
    public static function getContinentsAndCountries($locale = '')
    {
        return static::getList('Cc', $locale);
    }

    /**
     * Return a list of some specified territory/subdivision, structured or not.
     * $levels control which data you want to retrieve. It can be one or more of the following values:
     * <ul>
     *     <li>'W': world</li>
     *     <li>'C': continents</li>
     *     <li>'S': sub-continents</li>
     *     <li>'c': countries</li>
     *     <li>'s': subdivisions</li>
     * </ul>
     * If only one level is specified you'll get a flat list (like the one returned by {@link getContinents}).
     * If one or more levels are specified, you'll get a structured list (like the one returned by {@link getContinentsAndCountries}).
     *
     * @param string $levels A string with one or more of the characters: 'W' (for world), 'C' (for continents), 'S' (for sub-continents), 'c' (for countries)
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws Exception\BadArgumentType
     *
     * @return array
     *
     * @see http://www.unicode.org/cldr/charts/latest/supplemental/territory_containment_un_m_49.html
     * @see http://www.unicode.org/cldr/charts/latest/supplemental/territory_subdivisions.html
     */
    public static function getList($levels = 'W', $locale = '')
    {
        static $levelMap = array('W' => 0, 'C' => 1, 'S' => 2, 'c' => 3, 's' => 4);
        $decodedLevels = array();
        $n = is_string($levels) ? strlen($levels) : 0;
        if ($n > 0) {
            for ($i = 0; $i < $n; $i++) {
                $l = substr($levels, $i, 1);
                if (!isset($levelMap[$l])) {
                    $decodedLevels = array();
                    break;
                }
                if (!in_array($levelMap[$l], $decodedLevels, true)) {
                    $decodedLevels[] = $levelMap[$l];
                }
            }
        }
        if (count($decodedLevels) === 0) {
            throw new Exception\BadArgumentType($levels, "list of territory kinds: it should be a list of one or more of the codes '" . implode("', '", array_keys($levelMap)) . "'");
        }
        $struct = self::filterStructure(self::getStructure(), $decodedLevels, 0);
        $flatList = (count($decodedLevels) > 1) ? false : true;
        $data = Data::get('territories', $locale);
        if (strpos($levels, 's') !== false) {
            $data += Data::get('subdivisions', $locale);
        }
        $finalized = self::finalizeWithNames($data, $struct, $flatList);

        if ($flatList) {
            $sorter = new Comparer();
            $sorter->sort($finalized, true);
        } else {
            $finalized = static::sort($finalized);
        }

        return $finalized;
    }

    /**
     * Return a list of territory identifiers for which we have some info (languages, population, literacy level, Gross Domestic Product).
     *
     * @return array The list of territory IDs for which we have some info
     */
    public static function getTerritoriesWithInfo()
    {
        return array_keys(Data::getGeneric('territoryInfo'));
    }

    /**
     * Return the list of languages spoken in a territory.
     *
     * @param string $territoryCode The territory code
     * @param string $filterStatuses Filter language status.
     *                               <ul>
     *                               <li>If empty no filter will be applied</li>
     *                               <li>'o' to include official languages</li>
     *                               <li>'r' to include official regional languages</li>
     *                               <li>'f' to include de facto official languages</li>
     *                               <li>'m' to include official minority languages</li>
     *                               <li>'u' to include unofficial or unknown languages</li>
     *                               </ul>
     * @param string $onlyCodes Set to true to retrieve only the language codes. If set to false (default) you'll receive a list of arrays with these keys:
     *                          <ul>
     *                          <li>string id: the language identifier</li>
     *                          <li>string status: 'o' for official; 'r' for official regional; 'f' for de facto official; 'm' for official minority; 'u' for unofficial or unknown</li>
     *                          <li>number population: the amount of people speaking the language (%)</li>
     *                          <li>number|null writing: the amount of people able to write (%). May be null if no data is available</li>
     *                          </ul>
     *
     * @return array|null Return the languages spoken in the specified territory, as described by the $onlyCodes parameter (or null if $territoryCode is not valid or no data is available)
     */
    public static function getLanguages($territoryCode, $filterStatuses = '', $onlyCodes = false)
    {
        $result = null;
        $info = self::getTerritoryInfo($territoryCode);
        if (is_array($info)) {
            $result = array();
            foreach ($info['languages'] as $languageID => $languageInfo) {
                if (!isset($languageInfo['status'])) {
                    $languageInfo['status'] = 'u';
                }
                if ((strlen($filterStatuses) === 0) || (stripos($filterStatuses, $languageInfo['status']) !== false)) {
                    if (!isset($languageInfo['writing'])) {
                        $languageInfo['writing'] = null;
                    }
                    if ($onlyCodes) {
                        $result[] = $languageID;
                    } else {
                        $result[] = array_merge(array('id' => $languageID), $languageInfo);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Return the population of a specific territory.
     *
     * @param string $territoryCode The territory code
     *
     * @return number|null Return the size of the population of the specified territory (or null if $territoryCode is not valid or no data is available)
     */
    public static function getPopulation($territoryCode)
    {
        $result = null;
        $info = self::getTerritoryInfo($territoryCode);
        if (is_array($info)) {
            $result = $info['population'];
        }

        return $result;
    }

    /**
     * Return the literacy level for a specific territory, in %.
     *
     * @param string $territoryCode The territory code
     *
     * @return number|null Return the % of literacy lever of the specified territory (or null if $territoryCode is not valid or no data is available)
     */
    public static function getLiteracyLevel($territoryCode)
    {
        $result = null;
        $info = self::getTerritoryInfo($territoryCode);
        if (is_array($info)) {
            $result = $info['literacy'];
        }

        return $result;
    }

    /**
     * Return the GDP (Gross Domestic Product) for a specific territory, in US$.
     *
     * @param string $territoryCode The territory code
     *
     * @return number|null Return the GDP of the specified territory (or null if $territoryCode is not valid or no data is available)
     */
    public static function getGrossDomesticProduct($territoryCode)
    {
        $result = null;
        $info = self::getTerritoryInfo($territoryCode);
        if (is_array($info)) {
            $result = $info['gdp'];
        }

        return $result;
    }

    /**
     * Return a list of territory IDs where a specific language is spoken, sorted by the total number of people speaking that language.
     *
     * @param string $languageID The language identifier
     * @param float $threshold The minimum percentage (from 0 to 100) to consider a language as spoken in a Country
     *
     * @return array
     */
    public static function getTerritoriesForLanguage($languageID, $threshold = 0)
    {
        $peopleInTerritory = array();
        foreach (Data::getGeneric('territoryInfo') as $territoryID => $territoryInfo) {
            $percentage = null;
            foreach ($territoryInfo['languages'] as $langID => $langInfo) {
                if ((strcasecmp($languageID, $langID) === 0) || (stripos($langID, $languageID . '_') === 0)) {
                    if ($percentage === null) {
                        $percentage = $langInfo['population'];
                    } else {
                        $percentage += $langInfo['population'];
                    }
                }
            }
            if ($percentage !== null && $percentage >= $threshold) {
                $peopleInTerritory[$territoryID] = $territoryInfo['population'] * $percentage;
            }
        }
        arsort($peopleInTerritory, SORT_NUMERIC);
        return array_keys($peopleInTerritory);
    }

    /**
     * Return the code of the territory/subdivision that contains a territory/subdivision.
     *
     * @param string $childTerritoryCode
     *
     * @return string return the parent territory/subdivision code, or an empty string if $childTerritoryCode is the World (001) or if it's invalid
     */
    public static function getParentTerritoryCode($childTerritoryCode)
    {
        $result = '';
        if (is_string($childTerritoryCode) && preg_match('/^[a-z0-9]{2,5}$/i', $childTerritoryCode)) {
            if (strlen($childTerritoryCode) == 2 || (int) $childTerritoryCode > 0) {
                $childTerritoryCode = strtoupper($childTerritoryCode);
                $data = Data::getGeneric('territoryContainment');
            } else {
                $childTerritoryCode = strtolower($childTerritoryCode);
                $data = Data::getGeneric('subdivisionContainment');
            }
            foreach ($data as $parentTerritoryCode => $parentTerritoryInfo) {
                if (in_array($childTerritoryCode, $parentTerritoryInfo['contains'], false)) {
                    $result = is_int($parentTerritoryCode) ? substr('00' . $parentTerritoryCode, -3) : $parentTerritoryCode;
                    if ($result === '001' || static::getParentTerritoryCode($result) !== '') {
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Retrieve the child territories/subdivisions of a parent territory.
     *
     * @param string $parentTerritoryCode
     * @param bool $expandSubGroups set to true to expand the sub-groups, false to retrieve them
     * @param bool $expandSubdivisions set to true to expand countries into subdivisions, false to retrieve them
     *
     * @return array Return the list of territory codes that are children of $parentTerritoryCode (if $parentTerritoryCode is invalid you'll get an empty list)
     */
    public static function getChildTerritoryCodes($parentTerritoryCode, $expandSubGroups = false, $expandSubdivisions = false)
    {
        $result = array();
        if (is_string($parentTerritoryCode) && preg_match('/^[a-z0-9]{2,5}$/i', $parentTerritoryCode)) {
            if (strlen($parentTerritoryCode) == 2 || (int) $parentTerritoryCode > 0) {
                $parentTerritoryCode = strtoupper($parentTerritoryCode);
            } else {
                $parentTerritoryCode = strtolower($parentTerritoryCode);
                $expandSubdivisions = true;
            }
            $data = Data::getGeneric('territoryContainment');
            if ($expandSubdivisions) {
                $data += Data::getGeneric('subdivisionContainment');
            }
            if (isset($data[$parentTerritoryCode])) {
                $children = $data[$parentTerritoryCode]['contains'];
                if ($expandSubGroups) {
                    foreach ($children as $child) {
                        $grandChildren = static::getChildTerritoryCodes($child, true, $expandSubdivisions);
                        if (empty($grandChildren)) {
                            $result[] = $child;
                        } else {
                            $result = array_merge($result, $grandChildren);
                        }
                    }
                } else {
                    $result = $children;
                }
            }
        }

        return $result;
    }

    /**
     * @param string $territoryCode
     *
     * @return array|null
     */
    protected static function getTerritoryInfo($territoryCode)
    {
        $result = null;
        if (preg_match('/^[a-z0-9]{2,3}$/i', $territoryCode)) {
            $territoryCode = strtoupper($territoryCode);
            $data = Data::getGeneric('territoryInfo');
            if (isset($data[$territoryCode])) {
                $result = $data[$territoryCode];
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    protected static function getStructure()
    {
        static $cache = null;
        if ($cache === null) {
            $data = Data::getGeneric('territoryContainment') + Data::getGeneric('subdivisionContainment');
            $result = static::fillStructure($data, '001', 0);
            $cache = $result;
        } else {
            $result = $cache;
        }

        return $result;
    }

    /**
     * @param array $data
     * @param string $id
     * @param int $level
     *
     * @return array
     */
    protected static function fillStructure($data, $id, $level)
    {
        $item = array('id' => $id, 'level' => $level, 'children' => array());
        if (isset($data[$id])) {
            foreach ($data[$id]['contains'] as $childID) {
                $item['children'][] = static::fillStructure($data, $childID, $level + 1);
            }
        }

        return $item;
    }

    /**
     * @param array $data
     * @param array $list
     * @param bool $flatList
     *
     * @return array
     */
    protected static function finalizeWithNames($data, $list, $flatList)
    {
        $result = array();
        foreach ($list as $item) {
            $name = $data[$item['id']];
            if ($flatList) {
                $result[$item['id']] = $name;
            } else {
                $result[$item['id']] = array('name' => $name);
                if (count($item['children']) > 0) {
                    $result[$item['id']]['children'] = static::finalizeWithNames($data, $item['children'], $flatList);
                }
            }
        }

        return $result;
    }

    /**
     * @param array $parent
     * @param int[] $levels
     *
     * @return array
     */
    protected static function filterStructure($parent, $levels)
    {
        $thisResult = array();
        if (in_array($parent['level'], $levels, true)) {
            $thisResult[0] = $parent;
            $thisResult[0]['children'] = array();
            $addToSub = true;
        } else {
            $addToSub = false;
        }

        $subList = array();
        foreach ($parent['children'] as $child) {
            $subList = array_merge($subList, static::filterStructure($child, $levels));
        }
        if ($addToSub) {
            $thisResult[0]['children'] = $subList;
        } else {
            $thisResult = $subList;
        }

        return $thisResult;
    }

    /**
     * @param array $list
     *
     * @return array
     */
    protected static function sort($list)
    {
        foreach (array_keys($list) as $i) {
            if (isset($list[$i]['children'])) {
                $list[$i]['children'] = static::sort($list[$i]['children']);
            }
        }
        $sorter = new \Punic\Comparer();
        uasort($list, function ($a, $b) use ($sorter) {
            return $sorter->compare($a['name'], $b['name']);
        });

        return $list;
    }
}
