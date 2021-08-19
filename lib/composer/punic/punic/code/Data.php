<?php

namespace Punic;

/**
 * Common data helper stuff.
 */
class Data
{
    /**
     * Let's cache already loaded files (locale-specific).
     *
     * @var array
     */
    protected static $cache = array();

    /**
     * Let's cache already loaded files (not locale-specific).
     *
     * @var array
     */
    protected static $cacheGeneric = array();

    /**
     * The current default locale.
     *
     * @var string
     */
    protected static $defaultLocale = 'en_US';

    /**
     * The fallback locale (used if default locale is not found).
     *
     * @var string
     */
    protected static $fallbackLocale = 'en_US';

    /**
     * Return the current default locale.
     *
     * @return string
     */
    public static function getDefaultLocale()
    {
        return static::$defaultLocale;
    }

    /**
     * Return the current default language.
     *
     * @return string
     */
    public static function getDefaultLanguage()
    {
        $info = static::explodeLocale(static::$defaultLocale);

        return $info['language'];
    }

    /**
     * Set the current default locale and language.
     *
     * @param string $locale
     *
     * @throws \Punic\Exception\InvalidLocale Throws an exception if $locale is not a valid string
     */
    public static function setDefaultLocale($locale)
    {
        if (static::explodeLocale($locale) === null) {
            throw new Exception\InvalidLocale($locale);
        }
        static::$defaultLocale = $locale;
    }

    /**
     * Return the current fallback locale (used if default locale is not found).
     *
     * @return string
     */
    public static function getFallbackLocale()
    {
        return static::$fallbackLocale;
    }

    /**
     * Return the current fallback language (used if default locale is not found).
     *
     * @return string
     */
    public static function getFallbackLanguage()
    {
        $info = static::explodeLocale(static::$fallbackLocale);

        return $info['language'];
    }

    /**
     * Set the current fallback locale and language.
     *
     * @param string $locale
     *
     * @throws \Punic\Exception\InvalidLocale Throws an exception if $locale is not a valid string
     */
    public static function setFallbackLocale($locale)
    {
        if (static::explodeLocale($locale) === null) {
            throw new Exception\InvalidLocale($locale);
        }
        if (static::$fallbackLocale !== $locale) {
            static::$fallbackLocale = $locale;
            static::$cache = array();
        }
    }

    /**
     * Get the locale data.
     *
     * @param string $identifier The data identifier
     * @param string $locale The locale identifier (if empty we'll use the current default locale)
     *
     * @return array
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @internal
     */
    public static function get($identifier, $locale = '')
    {
        if (!(is_string($identifier) && isset($identifier[0]))) {
            throw new Exception\InvalidDataFile($identifier);
        }
        if (empty($locale)) {
            $locale = static::$defaultLocale;
        }
        if (!isset(static::$cache[$locale])) {
            static::$cache[$locale] = array();
        }
        if (!isset(static::$cache[$locale][$identifier])) {
            if (!@preg_match('/^[a-zA-Z0-9_\\-]+$/', $identifier)) {
                throw new Exception\InvalidDataFile($identifier);
            }
            $dir = static::getLocaleFolder($locale);
            if (!isset($dir[0])) {
                throw new Exception\DataFolderNotFound($locale, static::$fallbackLocale);
            }
            $file = $dir.DIRECTORY_SEPARATOR.$identifier.'.json';
            if (!is_file(__DIR__.DIRECTORY_SEPARATOR.$file)) {
                throw new Exception\DataFileNotFound($identifier, $locale, static::$fallbackLocale);
            }
            $json = @file_get_contents(__DIR__.DIRECTORY_SEPARATOR.$file);
            //@codeCoverageIgnoreStart
            // In test enviro we can't replicate this problem
            if ($json === false) {
                throw new Exception\DataFileNotReadable($file);
            }
            //@codeCoverageIgnoreEnd
            $data = @json_decode($json, true);
            //@codeCoverageIgnoreStart
            // In test enviro we can't replicate this problem
            if (!is_array($data)) {
                throw new Exception\BadDataFileContents($file, $json);
            }
            //@codeCoverageIgnoreEnd
            static::$cache[$locale][$identifier] = $data;
        }

        return static::$cache[$locale][$identifier];
    }

    /**
     * Get the generic data.
     *
     * @param string $identifier The data identifier
     *
     * @return array
     *
     * @throws Exception Throws an exception in case of problems
     *
     * @internal
     */
    public static function getGeneric($identifier)
    {
        if (!(is_string($identifier) && isset($identifier[0]))) {
            throw new Exception\InvalidDataFile($identifier);
        }
        if (isset(static::$cacheGeneric[$identifier])) {
            return static::$cacheGeneric[$identifier];
        }
        if (!preg_match('/^[a-zA-Z0-9_\\-]+$/', $identifier)) {
            throw new Exception\InvalidDataFile($identifier);
        }
        $file = 'data'.DIRECTORY_SEPARATOR."$identifier.json";
        if (!is_file(__DIR__.DIRECTORY_SEPARATOR.$file)) {
            throw new Exception\DataFileNotFound($identifier);
        }
        $json = @file_get_contents(__DIR__.DIRECTORY_SEPARATOR.$file);
        //@codeCoverageIgnoreStart
        // In test enviro we can't replicate this problem
        if ($json === false) {
            throw new Exception\DataFileNotReadable($file);
        }
        //@codeCoverageIgnoreEnd
        $data = @json_decode($json, true);
        //@codeCoverageIgnoreStart
        // In test enviro we can't replicate this problem
        if (!is_array($data)) {
            throw new Exception\BadDataFileContents($file, $json);
        }
        //@codeCoverageIgnoreEnd
        static::$cacheGeneric[$identifier] = $data;

        return $data;
    }

    /**
     * Return a list of available locale identifiers.
     *
     * @param bool $allowGroups Set to true if you want to retrieve locale groups (eg. 'en-001'), false otherwise
     *
     * @return array
     */
    public static function getAvailableLocales($allowGroups = false)
    {
        $locales = array();
        $dir = __DIR__.DIRECTORY_SEPARATOR.'data';
        if (is_dir($dir) && is_readable($dir)) {
            $contents = @scandir($dir);
            if (is_array($contents)) {
                foreach (array_diff($contents, array('.', '..')) as $item) {
                    if (is_dir($dir.DIRECTORY_SEPARATOR.$item)) {
                        if ($item === 'root') {
                            $item = 'en-US';
                        }
                        $info = static::explodeLocale($item);
                        if (is_array($info)) {
                            if ((!$allowGroups) && preg_match('/^[0-9]{3}$/', $info['territory'])) {
                                foreach (Territory::getChildTerritoryCodes($info['territory'], true) as $territory) {
                                    if (isset($info['script'][0])) {
                                        $locales[] = "{$info['language']}-{$info['script']}-$territory";
                                    } else {
                                        $locales[] = "{$info['language']}-$territory";
                                    }
                                }
                                $locales[] = $item;
                            } else {
                                $locales[] = $item;
                            }
                        }
                    }
                }
            }
        }

        return $locales;
    }

    /**
     * Try to guess the full locale (with script and territory) ID associated to a language.
     *
     * @param string $language The language identifier (if empty we'll use the current default language)
     * @param string $script The script identifier (if $language is empty we'll use the current default script)
     *
     * @return string Returns an empty string if the territory was not found, the territory ID otherwise
     */
    public static function guessFullLocale($language = '', $script = '')
    {
        $result = '';
        if (empty($language)) {
            $defaultInfo = static::explodeLocale(static::$defaultLocale);
            $language = $defaultInfo['language'];
            $script = $defaultInfo['script'];
        }
        $data = static::getGeneric('likelySubtags');
        $keys = array();
        if (!empty($script)) {
            $keys[] = "$language-$script";
        }
        $keys[] = $language;
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $result = $data[$key];
                if (isset($script[0]) && (stripos($result, "$language-$script-") !== 0)) {
                    $parts = static::explodeLocale($result);
                    if ($parts !== null) {
                        $result = "{$parts['language']}-$script-{$parts['territory']}";
                    }
                }
                break;
            }
        }

        return $result;
    }

    /**
     * Return the terrotory associated to the locale (guess it if it's not present in $locale).
     *
     * @param string $locale The locale identifier (if empty we'll use the current default locale)
     * @param bool $checkFallbackLocale Set to true to check the fallback locale if $locale (or the default locale) don't have an associated territory, false to don't fallback to fallback locale
     *
     * @return string
     */
    public static function getTerritory($locale = '', $checkFallbackLocale = true)
    {
        $result = '';
        if (empty($locale)) {
            $locale = static::$defaultLocale;
        }
        $info = static::explodeLocale($locale);
        if (is_array($info)) {
            if (!isset($info['territory'][0])) {
                $fullLocale = static::guessFullLocale($info['language'], $info['script']);
                if (strlen($fullLocale)) {
                    $info = static::explodeLocale($fullLocale);
                }
            }
            if (isset($info['territory'][0])) {
                $result = $info['territory'];
            } elseif ($checkFallbackLocale) {
                $result = static::getTerritory(static::$fallbackLocale, false);
            }
        }

        return $result;
    }

    /**
     * @deprecated
     */
    protected static function getParentTerritory($territory)
    {
        return Territory::getParentTerritoryCode($territory);
    }

    /**
     * @deprecated
     */
    protected static function expandTerritoryGroup($parentTerritory)
    {
        return Territory::getChildTerritoryCodes($parentTerritory, true);
    }

    /**
     * Return the node associated to the locale territory.
     *
     * @param array $data The parent array for which you want the territory node
     * @param string $locale The locale identifier (if empty we'll use the current default locale)
     *
     * @return mixed Returns null if the node was not found, the node data otherwise
     *
     * @internal
     */
    public static function getTerritoryNode($data, $locale = '')
    {
        $result = null;
        $territory = static::getTerritory($locale);
        while (isset($territory[0])) {
            if (isset($data[$territory])) {
                $result = $data[$territory];
                break;
            }
            $territory = Territory::getParentTerritoryCode($territory);
        }

        return $result;
    }

    /**
     * Return the node associated to the language (not locale) territory.
     *
     * @param array $data The parent array for which you want the language node
     * @param string $locale The locale identifier (if empty we'll use the current default locale)
     *
     * @return mixed Returns null if the node was not found, the node data otherwise
     *
     * @internal
     */
    public static function getLanguageNode($data, $locale = '')
    {
        $result = null;
        if (empty($locale)) {
            $locale = static::$defaultLocale;
        }
        foreach (static::getLocaleAlternatives($locale) as $l) {
            if (isset($data[$l])) {
                $result = $data[$l];
                break;
            }
        }

        return $result;
    }

    /**
     * Returns the item of an array associated to a locale.
     *
     * @param array $data The data containing the locale info
     * @param string $locale The locale identifier (if empty we'll use the current default locale)
     *
     * @return mixed Returns null if $data is not an array or it does not contain locale info, the array item otherwise
     *
     * @internal
     */
    public static function getLocaleItem($data, $locale = '')
    {
        $result = null;
        if (is_array($data)) {
            if (empty($locale)) {
                $locale = static::$defaultLocale;
            }
            foreach (static::getLocaleAlternatives($locale) as $alternative) {
                if (isset($data[$alternative])) {
                    $result = $data[$alternative];
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Parse a string representing a locale and extract its components.
     *
     * @param string $locale
     *
     * @return null|string[] Return null if $locale is not valid; if $locale is valid returns an array with keys 'language', 'script', 'territory', 'parentLocale'
     *
     * @internal
     */
    public static function explodeLocale($locale)
    {
        $result = null;
        if (is_string($locale)) {
            if ($locale === 'root') {
                $locale = 'en-US';
            }
            $chunks = explode('-', str_replace('_', '-', strtolower($locale)));
            if (count($chunks) <= 3) {
                if (preg_match('/^[a-z]{2,3}$/', $chunks[0])) {
                    $language = $chunks[0];
                    $script = '';
                    $territory = '';
                    $parentLocale = '';
                    $ok = true;
                    $chunkCount = count($chunks);
                    for ($i = 1; $ok && ($i < $chunkCount); ++$i) {
                        if (preg_match('/^[a-z]{4}$/', $chunks[$i])) {
                            if (isset($script[0])) {
                                $ok = false;
                            } else {
                                $script = ucfirst($chunks[$i]);
                            }
                        } elseif (preg_match('/^([a-z]{2})|([0-9]{3})$/', $chunks[$i])) {
                            if (isset($territory[0])) {
                                $ok = false;
                            } else {
                                $territory = strtoupper($chunks[$i]);
                            }
                        } else {
                            $ok = false;
                        }
                    }
                    if ($ok) {
                        $parentLocales = static::getGeneric('parentLocales');
                        if (isset($script[0]) && isset($territory[0]) && isset($parentLocales["$language-$script-$territory"])) {
                            $parentLocale = $parentLocales["$language-$script-$territory"];
                        } elseif (isset($script[0]) && isset($parentLocales["$language-$script"])) {
                            $parentLocale = $parentLocales["$language-$script"];
                        } elseif (isset($territory[0]) && isset($parentLocales["$language-$territory"])) {
                            $parentLocale = $parentLocales["$language-$territory"];
                        } elseif (isset($parentLocales[$language])) {
                            $parentLocale = $parentLocales[$language];
                        }
                        $result = array(
                            'language' => $language,
                            'script' => $script,
                            'territory' => $territory,
                            'parentLocale' => $parentLocale,
                        );
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Returns the path of the locale-specific data, looking also for the fallback locale.
     *
     * @param string $locale The locale for which you want the data folder
     *
     * @return string Returns an empty string if the folder is not found, the absolute path to the folder otherwise
     */
    protected static function getLocaleFolder($locale)
    {
        static $cache = array();
        $result = '';
        if (is_string($locale)) {
            $key = $locale.'/'.static::$fallbackLocale;
            if (!isset($cache[$key])) {
                foreach (static::getLocaleAlternatives($locale) as $alternative) {
                    $dir = 'data'.DIRECTORY_SEPARATOR.$alternative;
                    if (is_dir(__DIR__.DIRECTORY_SEPARATOR.$dir)) {
                        $result = $dir;
                        break;
                    }
                }
                $cache[$key] = $result;
            }
            $result = $cache[$key];
        }

        return $result;
    }

    /**
     * Returns a list of locale identifiers associated to a locale.
     *
     * @param string $locale The locale for which you want the alternatives
     * @param string $addFallback Set to true to add the fallback locale to the result, false otherwise
     *
     * @return array
     */
    protected static function getLocaleAlternatives($locale, $addFallback = true)
    {
        $result = array();
        $localeInfo = static::explodeLocale($locale);
        if (!is_array($localeInfo)) {
            throw new Exception\InvalidLocale($locale);
        }
        $language = $localeInfo['language'];
        $script = $localeInfo['script'];
        $territory = $localeInfo['territory'];
        $parentLocale = $localeInfo['parentLocale'];
        if (!isset($territory[0])) {
            $fullLocale = static::guessFullLocale($language, $script);
            if (isset($fullLocale[0])) {
                $localeInfo = static::explodeLocale($fullLocale);
                $language = $localeInfo['language'];
                $script = $localeInfo['script'];
                $territory = $localeInfo['territory'];
                $parentLocale = $localeInfo['parentLocale'];
            }
        }
        $territories = array();
        while (isset($territory[0])) {
            $territories[] = $territory;
            $territory = Territory::getParentTerritoryCode($territory);
        }
        if (isset($script[0])) {
            foreach ($territories as $territory) {
                $result[] = "{$language}-{$script}-{$territory}";
            }
        }
        if (isset($script[0])) {
            $result[] = "{$language}-{$script}";
        }
        foreach ($territories as $territory) {
            $result[] = "{$language}-{$territory}";
            if ("{$language}-{$territory}" === 'en-US') {
                $result[] = 'root';
            }
        }
        if (isset($parentLocale[0])) {
            $result = array_merge($result, static::getLocaleAlternatives($parentLocale, false));
        }
        $result[] = $language;
        if ($addFallback && ($locale !== static::$fallbackLocale)) {
            $result = array_merge($result, static::getLocaleAlternatives(static::$fallbackLocale, false));
        }
        for ($i = count($result) - 1; $i > 1; --$i) {
            for ($j = 0; $j < $i; ++$j) {
                if ($result[$i] === $result[$j]) {
                    array_splice($result, $i, 1);
                    break;
                }
            }
        }
        $i = array_search('root', $result, true);
        if ($i !== false) {
            array_splice($result, $i, 1);
            $result[] = 'root';
        }

        return $result;
    }
}
