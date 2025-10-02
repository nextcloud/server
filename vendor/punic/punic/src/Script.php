<?php

namespace Punic;

/**
 * Script helper stuff.
 */
class Script
{
    /**
     * Script alternative name: secondary.
     *
     * @var string
     */
    const ALTERNATIVENAME_SECONDARY = 'secondary';

    /**
     * Script alternative name: variant.
     *
     * @var string
     */
    const ALTERNATIVENAME_VARIANT = 'variant';

    /**
     * Script alternative name: short.
     *
     * @var string
     */
    const ALTERNATIVENAME_SHORT = 'short';

    /**
     * Script alternative name: stand alone.
     *
     * @var string
     */
    const ALTERNATIVENAME_STANDALONE = 'stand-alone';

    /**
     * Get the list of all the script codes.
     *
     * @return string[]
     */
    public static function getAllScriptCodes()
    {
        return static::getAvailableScriptCodes('en');
    }

    /**
     * Get the list of all the available script codes that have a translation for a specific language.
     *
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string[]
     */
    public static function getAvailableScriptCodes($locale)
    {
        $data = Data::get('scripts', $locale);
        $scriptCodes = array_keys($data);
        sort($scriptCodes);

        return $scriptCodes;
    }

    /**
     * Get the name of a script given its code.
     *
     * @param string|mixed $scriptCode the script code
     * @param string $preferredVariant the preferred variant (valid values are the values of the ALTERNATIVENAME_... constants)
     * @param bool $fallbackToEnglish some languages may be missing translation for some scripts: should we look for English names in this case?
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string empty string if $scriptCode is not a valid script code
     *
     * @see \Punic\Script::ALTERNATIVENAME_SECONDARY
     * @see \Punic\Script::ALTERNATIVENAME_VARIANT
     * @see \Punic\Script::ALTERNATIVENAME_SHORT
     * @see \Punic\Script::ALTERNATIVENAME_STANDALONE
     */
    public static function getScriptName($scriptCode, $preferredVariant = '', $fallbackToEnglish = true, $locale = '')
    {
        if (!is_string($scriptCode) || $scriptCode === '') {
            return '';
        }
        $data = Data::get('scripts', $locale);
        if (!isset($data[$scriptCode])) {
            if (!$fallbackToEnglish || $locale === 'en') {
                return '';
            }
            return self::getScriptName($scriptCode, $preferredVariant, false, 'en');
        }

        return self::extractScriptName($data[$scriptCode], $preferredVariant);
    }

    /**
     * Get all the scripts.
     *
     * @param string $preferredVariant the preferred variant (valid values are the values of the ALTERNATIVENAME_... constants)
     * @param bool $fallbackToEnglish some languages may be missing translation for some scripts: should we look for English names in this case?
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return array Array keys are the script codes, array values are the script names (the array is sorted by the values)
     *
     * @see \Punic\Script::ALTERNATIVENAME_SECONDARY
     * @see \Punic\Script::ALTERNATIVENAME_VARIANT
     * @see \Punic\Script::ALTERNATIVENAME_SHORT
     * @see \Punic\Script::ALTERNATIVENAME_STANDALONE
     */
    public static function getAllScripts($preferredVariant = '', $fallbackToEnglish = true, $locale = '')
    {
        $data = Data::get('scripts', $locale);
        if ($fallbackToEnglish) {
            $data += Data::get('scripts', 'en');
        }
        $result = array();
        foreach ($data as $scriptCode => $scriptData) {
            $result[$scriptCode] = self::extractScriptName($scriptData, $preferredVariant);
        }
        $comparer = new Comparer($locale);
        $comparer->sort($result, true);

        return $result;
    }

    /**
     * @param string|array $scriptData
     * @param string|mixed $preferredVariant
     *
     * @return string
     */
    private static function extractScriptName($scriptData, $preferredVariant)
    {
        if (is_string($scriptData)) {
            return $scriptData;
        }
        if (!is_string($preferredVariant) || $preferredVariant === '' || !isset($scriptData[$preferredVariant])) {
            return $scriptData[''];
        }
        return $scriptData[$preferredVariant][0];
    }
}
