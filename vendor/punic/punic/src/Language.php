<?php

namespace Punic;

/**
 * Language-related stuff.
 */
class Language
{
    /**
     * Return all the languages.
     *
     * @param bool $excludeCountrySpecific Set to false (default) to include also Country-specific languages (eg 'U.S. English' in addition to 'English'), set to true to exclude them
     * @param bool $excludeScriptSpecific Set to false (default) to include also script-specific languages (eg 'Simplified Chinese' in addition to 'Chinese'), set to true to exclude them
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return array Return an array, sorted by values, whose keys are the language IDs and the values are the localized language names
     */
    public static function getAll($excludeCountrySpecific = false, $excludeScriptSpecific = false, $locale = '')
    {
        if ($excludeCountrySpecific && $excludeScriptSpecific) {
            $filter = function ($languageID) {
                return (strpos($languageID, '-') === false) ? true : false;
            };
        } elseif ($excludeCountrySpecific) {
            $filter = function ($languageID) {
                return preg_match('/^[a-z]+(-[A-Z][a-z]{3})?$/', $languageID) ? true : false;
            };
        } elseif ($excludeScriptSpecific) {
            $filter = function ($languageID) {
                return preg_match('/^[a-z]+(-([A-Z]{2}|[0-9]{3}))?$/', $languageID) ? true : false;
            };
        } else {
            $filter = function ($languageID) {
                return preg_match('/^[a-z]++(-[A-Z][a-z]{3})?(-([A-Z]{2}|[0-9]{3}))?$/', $languageID) ? true : false;
            };
        }
        $data = Data::get('languages', $locale);
        $result = array();
        foreach (array_filter(array_keys($data), $filter) as $languageID) {
            $result[$languageID] = $data[$languageID];
        }

        return $result;
    }

    /**
     * Retrieve the name of a language.
     *
     * @param string $languageCode The language code. If it also contains a territory code (eg: 'en-US'), the result will also contain the territory code (eg 'English (United States)')
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     * @param bool $allowCompoundNames Set to true to allow compound names (eg 'American English (United States)' instead of 'English (United States)')
     *
     * @return string Returns the localized language name (returns $languageCode if not found)
     */
    public static function getName($languageCode, $locale = '', $allowCompoundNames = false)
    {
        $result = $languageCode;
        $info = Data::explodeLocale($languageCode);
        if ($info !== null) {
            $language = $info['language'];
            $script = $info['script'];
            $territory = $info['territory'];
            $lookFor = array();
            if ($script !== '') {
                if ($allowCompoundNames && $territory !== '') {
                    $lookFor[] = "{$language}-{$script}-{$territory}";
                }
                $lookFor[] = "{$language}-{$script}";
            }
            if ($allowCompoundNames && $territory !== '') {
                $lookFor[] = "{$language}-{$territory}";
            }
            $lookFor[] = $language;
            $data = Data::get('languages', $locale);
            foreach ($lookFor as $key) {
                if (isset($data[$key])) {
                    $result = $data[$key];
                    break;
                }
            }
            if ($territory !== '') {
                $territoryName = Territory::getName($territory, $locale);
                if ($territoryName !== '') {
                    $patternData = Data::get('localeDisplayNames');
                    $pattern = $patternData['localeDisplayPattern']['localePattern'];
                    $result = sprintf($pattern, $result, $territoryName);
                }
            }
        }

        return $result;
    }
}
