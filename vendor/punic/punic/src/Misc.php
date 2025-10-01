<?php

namespace Punic;

use Traversable;

/**
 * Various helper stuff.
 */
class Misc
{
    /**
     * Concatenates a list of items returning a localized string using "and" as separator.
     *
     * For instance, array(1, 2, 3) will result in '1, 2, and 3' for English or '1, 2 e 3' for Italian.
     *
     * @param array|\Traversable $list The list to concatenate
     * @param string $width The preferred width ('' for default, or 'short' or 'narrow')
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string returns an empty string if $list is not an array of it it's empty, the joined items otherwise
     */
    public static function joinAnd($list, $width = '', $locale = '')
    {
        return static::joinInternal($list, 'standard', $width, $locale);
    }

    /**
     * Concatenates a list of items returning a localized string using "or" as separator.
     *
     * For instance, array(1, 2, 3) will result in '1, 2, or 3' for English or '1, 2 o 3' for Italian.
     *
     * @param array|\Traversable $list The list to concatenate
     * @param string $width The preferred width ('' for default, or 'short' or 'narrow')
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string returns an empty string if $list is not an array of it it's empty, the joined items otherwise
     */
    public static function joinOr($list, $width = '', $locale = '')
    {
        return static::joinInternal($list, 'or', $width, $locale);
    }

    /**
     * Concatenates a list of unit items returning a localized string.
     *
     * For instance, array('3 ft', '2 in') will result in '3 ft, 2 in'.
     *
     * @param array|\Traversable $list The list to concatenate
     * @param string $width The preferred width ('' for default, or 'short' or 'narrow')
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string returns an empty string if $list is not an array of it it's empty, the joined items otherwise
     */
    public static function joinUnits($list, $width = '', $locale = '')
    {
        return static::joinInternal($list, 'unit', $width, $locale);
    }

    /**
     * Fix the case of a string.
     *
     * @param string $string The string whose case needs to be fixed
     * @param string $case How to fix case. Allowed values:
     *                     <li>`'titlecase-words'` all words in the phrase should be title case</li>
     *                     <li>`'titlecase-firstword'` the first word should be title case</li>
     *                     <li>`'lowercase-words'` all words in the phrase should be lower case</li>
     *                     </ul>
     *
     * @return string
     *
     * @see http://cldr.unicode.org/development/development-process/design-proposals/consistent-casing
     */
    public static function fixCase($string, $case)
    {
        $result = $string;
        if (is_string($string) && is_string($case) && $string !== '') {
            switch ($case) {
                case 'titlecase-words':
                    if (function_exists('mb_strtoupper') && (@preg_match('/\pL/u', 'a'))) {
                        $result = preg_replace_callback('/\\pL+/u', function ($m) {
                            $s = $m[0];
                            $l = mb_strlen($s, 'UTF-8');
                            if ($l === 1) {
                                $s = mb_strtoupper($s, 'UTF-8');
                            } else {
                                $s = mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($s, 1, $l - 1, 'UTF-8');
                            }

                            return $s;
                        }, $string);
                    }
                    break;
                case 'titlecase-firstword':
                    if (function_exists('mb_strlen')) {
                        $l = mb_strlen($string, 'UTF-8');
                        if ($l === 1) {
                            $result = mb_strtoupper($string, 'UTF-8');
                        } elseif ($l > 1) {
                            $result = mb_strtoupper(mb_substr($string, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($string, 1, $l - 1, 'UTF-8');
                        }
                    }
                    break;
                case 'lowercase-words':
                    if (function_exists('mb_strtolower')) {
                        $result = mb_strtolower($string, 'UTF-8');
                    }
                    break;
            }
        }

        return $result;
    }

    /**
     * Parse the browser HTTP_ACCEPT_LANGUAGE header and return the found locales, sorted in descending order by the quality values.
     *
     * @param bool $ignoreCache Set to true if you want to ignore the cache
     *
     * @return array Array keys are the found locales, array values are the relative quality value (from 0 to 1)
     */
    public static function getBrowserLocales($ignoreCache = false)
    {
        static $result;
        if (!isset($result) || $ignoreCache) {
            $httpAcceptLanguages = @getenv('HTTP_ACCEPT_LANGUAGE');
            if ((!is_string($httpAcceptLanguages)) || (strlen($httpAcceptLanguages) === 0)) {
                if (isset($_SERVER) && is_array($_SERVER) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                    $httpAcceptLanguages = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
                }
            }
            $result = self::parseHttpAcceptLanguage($httpAcceptLanguages);
        }

        return $result;
    }

    /**
     * Parse the value of an HTTP_ACCEPT_LANGUAGE header and return the found locales, sorted in descending order by the quality values.
     *
     * @param string $httpAcceptLanguages
     *
     * @return array Array keys are the found locales, array values are the relative quality value (from 0 to 1)
     */
    public static function parseHttpAcceptLanguage($httpAcceptLanguages)
    {
        $result = array();
        if (is_string($httpAcceptLanguages) && $httpAcceptLanguages !== '') {
            $m = null;
            foreach (explode(',', $httpAcceptLanguages) as $httpAcceptLanguage) {
                if (preg_match('/^([a-z]{2,3}(?:[_\\-][a-z]+)*)(?:\\s*;(?:\\s*q(?:\\s*=(?:\\s*([\\d.]+))?)?)?)?$/', strtolower(trim($httpAcceptLanguage, " \t")), $m)) {
                    if (count($m) > 2) {
                        if (strpos($m[2], '.') === 0) {
                            $m[2] = '0' . $m[2];
                        }
                        if (preg_match('/^[01](\\.\\d*)?$/', $m[2])) {
                            $quality = round(@(float) $m[2], 4);
                        } else {
                            $quality = -1;
                        }
                    } else {
                        $quality = 1;
                    }
                    if (($quality >= 0) && ($quality <= 1)) {
                        $found = array();
                        $chunks = explode('-', str_replace('_', '-', $m[1]));
                        $numChunks = count($chunks);
                        if ($numChunks === 1) {
                            $found[] = $m[1];
                        } else {
                            $base = $chunks[0];
                            for ($i = 1; $i < $numChunks; $i++) {
                                if (strlen($chunks[$i]) === 4) {
                                    $base .= '-' . ucfirst($chunks[$i]);
                                    break;
                                }
                            }
                            for ($i = 1; $i < $numChunks; $i++) {
                                if (preg_match('/^[a-z][a-z]$/', $chunks[$i])) {
                                    $found[] = $base . '-' . strtoupper($chunks[$i]);
                                } elseif (preg_match('/^\\d{3}$/', $chunks[$i])) {
                                    $found[] = $base . '-' . $chunks[$i];
                                }
                            }
                            if (empty($found)) {
                                $found[] = $base;
                            }
                        }
                        foreach ($found as $f) {
                            if (isset($result[$f])) {
                                if ($result[$f] < $quality) {
                                    $result[$f] = $quality;
                                }
                            } else {
                                $result[$f] = $quality;
                            }
                        }
                    }
                }
            }
        }
        arsort($result, SORT_NUMERIC);

        return $result;
    }

    /**
     * Retrieve the character order (right-to-left or left-to-right).
     *
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string Return 'left-to-right' or 'right-to-left'
     */
    public static function getCharacterOrder($locale = '')
    {
        $data = Data::get('layout', $locale);

        return $data['characterOrder'];
    }

    /**
     * Retrieve the line order (top-to-bottom or bottom-to-top).
     *
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string Return 'top-to-bottom' or 'bottom-to-top'
     */
    public static function getLineOrder($locale = '')
    {
        $data = Data::get('layout', $locale);

        return $data['lineOrder'];
    }

    /**
     * @deprecated use joinAnd($list, '', $locale)
     *
     * @param array|\Traversable $list
     * @param string $locale
     *
     * @return string
     */
    public static function join($list, $locale = '')
    {
        return static::joinInternal($list, 'standard', '', $locale);
    }

    /**
     * Concatenates a list of items returning a localized string.
     *
     * @param array|\Traversable $list The list to concatenate
     * @param string $type The type of list; 'standard' (e.g. '1, 2, and 3'), 'or' ('1, 2, or 3') or 'unit' ('3 ft, 2 in').
     * @param string $width The preferred width ('' for default, or 'short' or 'narrow')
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string returns an empty string if $list is not an array of it it's empty, the joined items otherwise
     */
    protected static function joinInternal($list, $type, $width, $locale)
    {
        $result = '';
        if ($list instanceof Traversable) {
            $list = iterator_to_array($list);
        }
        if (is_array($list)) {
            switch ((string) $width) {
                case 'narrow':
                    $suffixes = array('-narrow', '-short', '');
                    break;
                case 'short':
                    $suffixes = array('-short', '-narrow', '');
                    break;
                case '':
                    $suffixes = array('', '-short', '-narrow');
                    break;
                default:
                    throw new \Punic\Exception\ValueNotInList($width, array('', 'short', 'narrow'));
            }

            $list = array_values($list);
            $n = count($list);
            switch ($n) {
                case 0:
                    break;
                case 1:
                    $result = (string) $list[0];
                    break;
                default:
                    $allData = Data::get('listPatterns', $locale);
                    $data = null;
                    foreach ($suffixes as $suffix) {
                        $key = $type . $suffix;
                        if (isset($allData[$key])) {
                            $data = $allData[$key];
                            break;
                        }
                    }
                    if ($data === null) {
                        $types = array_unique(array_map(function ($key) {
                            return strtok($key, '-');
                        }, array_keys($allData)));
                        throw new \Punic\Exception\ValueNotInList($type, $types);
                    }
                    if (isset($data[$n])) {
                        $result = vsprintf($data[$n], $list);
                    } else {
                        $result = sprintf($data['end'], $list[$n - 2], $list[$n - 1]);
                        if ($n > 2) {
                            for ($index = $n - 3; $index > 0; $index--) {
                                $result = sprintf($data['middle'], $list[$index], $result);
                            }
                            $result = sprintf($data['start'], $list[0], $result);
                        }
                    }
                    break;
            }
        }

        return $result;
    }
}
