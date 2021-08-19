<?php

namespace Punic;

/*
 * Comments marked as @TZWS have been added because it seems than PHP does
 * not support timezones with seconds.
 * Furthermore: the Unicode specs (http://www.unicode.org/reports/tr35/tr35-dates.html#Date_Field_Symbol_Table) says the following:
 * "The ISO8601 basic format with hours, minutes and optional seconds fields. Note: The seconds field is not supported by the
 * ISO8601 specification."
 */

/**
 * Date and time related functions.
 */
class Calendar
{
    /** @var array */
    protected static $timezoneCache;
    /** @var string[] */
    protected static $weekdayDictionary = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');

    /**
     * Convert a date/time representation to a {@link http://php.net/manual/class.datetime.php \DateTime} instance.
     *
     * @param number|\DateTime|string $value An Unix timestamp, a `\DateTime` instance \DateTime or a string accepted by {@link http://php.net/manual/function.strtotime.php strtotime}.
     * @param string|\DateTimeZone $toTimezone The timezone to set; leave empty to use the value of $fromTimezone (if it's empty we'll use the default timezone or the timezone associated to $value if it's already a `\DateTime`).
     * @param string|\DateTimeZone $fromTimezone The original timezone of $value; leave empty to use the default timezone (or the timezone associated to $value if it's already a `\DateTime`).
     *
     * @return \DateTime|null Returns null if $value is empty, a `\DateTime` instance otherwise.
     *
     * @throws \Punic\Exception\BadArgumentType Throws an exception if $value is not empty and can't be converted to a `\DateTime` instance or if $toTimezone is not empty and is not valid.
     *
     * @example Convert a Unix timestamp to a \DateTime instance with the current time zone:
     * ```php
     * \Punic\Calendar::toDateTime(1409648286);
     * ```
     * @example Convert a Unix timestamp to a \DateTime instance with a specific time zone:
     * ```php
     * \Punic\Calendar::toDateTime(1409648286, 'Europe/Rome');
     * \Punic\Calendar::toDateTime(1409648286, new \DateTimeZone('Europe/Rome'));
     * ```
     * @example Convert a string to a \DateTime instance with the current time zone:
     * ```php
     * \Punic\Calendar::toDateTime('2014-03-07 13:30');
     * ```
     * @example Convert a string to a \DateTime instance with a specific time zone:
     * ```php
     * \Punic\Calendar::toDateTime('2014-03-07 13:30', 'Europe/Rome');
     * ```
     * Please remark that in this case '2014-03-07 13:30' is converted to a \DateTime instance with the current timezone, and <u>after</u> we change the timezone.
     * So, if your system default timezone is 'America/Los_Angeles' (GMT -8), the resulting date/time will be '2014-03-07 22:30 GMT+1' since it'll be converted to 'Europe/Rome' (GMT +1)
     */
    public static function toDateTime($value, $toTimezone = '', $fromTimezone = '')
    {
        $result = null;
        if ((!empty($value)) || ($value === 0) || ($value === '0')) {
            $tzFrom = null;
            if (!empty($fromTimezone)) {
                if (is_string($fromTimezone)) {
                    try {
                        $tzFrom = new \DateTimeZone($fromTimezone);
                    } catch (\Exception $x) {
                        throw new Exception\BadArgumentType($fromTimezone, '\\DateTimeZone', $x);
                    }
                } elseif (is_a($fromTimezone, '\DateTimeZone')) {
                    $tzFrom = $fromTimezone;
                } else {
                    throw new Exception\BadArgumentType($fromTimezone, '\\DateTimeZone');
                }
            }
            if (is_int($value) || is_float($value)) {
                $result = new \DateTime();
                $result->setTimestamp($value);
                if ($tzFrom !== null) {
                    $result->setTimezone($tzFrom);
                }
            } elseif ($value instanceof \DateTime) {
                $result = clone $value;
                if ($tzFrom !== null) {
                    $result->setTimezone($tzFrom);
                }
            } elseif (is_string($value)) {
                if (is_numeric($value)) {
                    $result = new \DateTime();
                    $result->setTimestamp($value);
                    if ($tzFrom !== null) {
                        $result->setTimezone($tzFrom);
                    }
                } else {
                    try {
                        if ($tzFrom === null) {
                            $result = new \DateTime($value);
                        } else {
                            $result = new \DateTime($value, $tzFrom);
                        }
                    } catch (\Exception $x) {
                        throw new Exception\BadArgumentType($value, '\\DateTime', $x);
                    }
                }
            } else {
                throw new Exception\BadArgumentType($value, '\\DateTime');
            }
            if ($result) {
                if (!empty($toTimezone)) {
                    if (is_string($toTimezone)) {
                        try {
                            $result->setTimezone(new \DateTimeZone($toTimezone));
                        } catch (\Exception $x) {
                            throw new Exception\BadArgumentType($toTimezone, '\\DateTimeZone', $x);
                        }
                    } elseif (is_a($toTimezone, '\DateTimeZone')) {
                        $result->setTimezone($toTimezone);
                    } else {
                        throw new Exception\BadArgumentType($toTimezone, '\\DateTimeZone');
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Converts a format string from {@link http://php.net/manual/en/function.date.php#refsect1-function.date-parameters PHP's date format} to {@link http://www.unicode.org/reports/tr35/tr35-dates.html#Date_Field_Symbol_Table ISO format}.
     * The following extra format chunks are introduced:
     * - 'P': ISO-8601 numeric representation of the day of the week (same as 'e' but not locale dependent)
     * - 'PP': Numeric representation of the day of the week, from 0 (for Sunday) to 6 (for Saturday)
     * - 'PPP': English ordinal suffix for the day of the month
     * - 'PPPP': The day of the year (starting from 0)
     * - 'PPPPP': Number of days in the given month
     * - 'PPPPPP': Whether it's a leap year: 1 if it is a leap year, 0 otherwise.
     * - 'PPPPPPP': Lowercase Ante meridiem and Post meridiem (English only, for other locales it's the same as 'a')
     * - 'PPPPPPPP': Swatch Internet time
     * - 'PPPPPPPPP': Microseconds
     * - 'PPPPPPPPPP': Whether or not the date is in daylight saving time	1 if Daylight Saving Time, 0 otherwise.
     * - 'PPPPPPPPPPP': Timezone offset in seconds
     * - 'PPPPPPPPPPPP': RFC 2822 formatted date (Example: 'Thu, 21 Dec 2000 16:01:07 +0200')
     * - 'PPPPPPPPPPPPP': Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).
     *
     * @param string $format The PHP date/time format string to convert.
     *
     * @return string Returns the ISO date/time format corresponding to the specified PHP date/time format.
     */
    public static function convertPhpToIsoFormat($format)
    {
        static $cache = array();
        static $convert = array(
            'd' => 'dd',
            'D' => 'EE',
            'j' => 'd',
            'l' => 'EEEE',
            'N' => 'P',
            'S' => 'PPP',
            'w' => 'PP',
            'z' => 'PPPP',
            'W' => 'ww',
            'F' => 'MMMM',
            'm' => 'MM',
            'M' => 'MMM',
            'n' => 'M',
            't' => 'PPPPP',
            'L' => 'PPPPPP',
            'o' => 'YYYY',
            'Y' => 'yyyy',
            'y' => 'yy',
            'a' => 'PPPPPPP',
            'A' => 'a',
            'B' => 'PPPPPPPP',
            'g' => 'h',
            'G' => 'H',
            'h' => 'hh',
            'H' => 'HH',
            'i' => 'mm',
            's' => 'ss',
            'e' => 'VV',
            'I' => 'I',
            'O' => 'Z',
            'P' => 'ZZZZZ',
            'T' => 'z',
            'Z' => 'PPPPPPPPPPP',
            'c' => 'yyyy-MM-ddTHH:mm:ssZZZZZ',
            'r' => 'PPPPPPPPPPPP',
            'U' => 'U',
            'u' => 'PPPPPPPPP',
            'I' => 'PPPPPPPPPP',
            'U' => 'PPPPPPPPPPPPP',
        );
        if (!is_string($format)) {
            return '';
        }
        if (!isset($cache[$format])) {
            $escaped = false;
            $inEscapedString = false;
            $converted = array();
            foreach (str_split($format) as $char) {
                if (!$escaped && $char == '\\') {
                    // Next char will be escaped: let's remember it
                    $escaped = true;
                } elseif ($escaped) {
                    if (!$inEscapedString) {
                        // First escaped string: start the quoted chunk
                        $converted[] = "'";
                        $inEscapedString = true;
                    }
                    // Since the previous char was a \ and we are in the quoted
                    // chunk, let's simply add $char as it is
                    $converted[] = $char;
                    $escaped = false;
                } elseif ($char == "'") {
                    // Single quotes need to be escaped like this
                    $converted[] = "''";
                } else {
                    if ($inEscapedString) {
                        // Close the single-quoted chunk
                        $converted[] = "'";
                        $inEscapedString = false;
                    }
                    // Convert the unescaped char if needed
                    if (isset($convert[$char])) {
                        $converted[] = $convert[$char];
                    } else {
                        $converted[] = $char;
                    }
                }
            }
            $cache[$format] = implode($converted);
        }

        return $cache[$format];
    }

    /**
     * Get the name of an era.
     *
     * @param number|\DateTime $value The year number or the \DateTime instance for which you want the name of the era.
     * @param string $width The format name; it can be 'wide' (eg 'Before Christ'), 'abbreviated' (eg 'BC') or 'narrow' (eg 'B').
     * @param string $locale The locale to use. If empty we'll use the default locale set with {@link \Punic\Data::setDefaultLocale()}.
     *
     * @return string Returns an empty string if $value is empty, the name of the era otherwise.
     *
     * @throws \Punic\Exception\BadArgumentType Throws a BadArgumentType exception if $value is not valid.
     * @throws \Punic\Exception\ValueNotInList Throws a ValueNotInList exception if $width is not valid.
     * @throws \Punic\Exception Throws a generic exception in case of other problems (for instance if you specify an invalid locale).
     */
    public static function getEraName($value, $width = 'abbreviated', $locale = '')
    {
        $result = '';
        if ((!empty($value)) || ($value === 0) || ($value === '0')) {
            $year = null;
            if (is_int($value)) {
                $year = $value;
            } elseif (is_float($value)) {
                $year = intval($value);
            } elseif (is_string($value)) {
                if (is_numeric($value)) {
                    $year = intval($value);
                }
            } elseif (is_a($value, '\DateTime')) {
                $year = intval($value->format('Y'));
            }
            if ($year === null) {
                throw new Exception\BadArgumentType($value, 'year number');
            }
            $data = Data::get('calendar', $locale);
            $data = $data['eras'];
            if (!isset($data[$width])) {
                throw new Exception\ValueNotInList($width, array_keys($data));
            }
            $result = $data[$width][($year < 0) ? '0' : '1'];
        }

        return $result;
    }

    /**
     * Get the name of a month.
     *
     * @param number|\DateTime $value The month number (1-12) or a \DateTime instance for which you want the name of the month.
     * @param string $width The format name; it can be 'wide' (eg 'January'), 'abbreviated' (eg 'Jan') or 'narrow' (eg 'J').
     * @param string $locale The locale to use. If empty we'll use the default locale set with {@link \Punic\Data::setDefaultLocale()}.
     * @param bool $standAlone Set to true to return the form used independently (such as in calendar header), set to false if the month name will be part of a date.
     *
     * @return string Returns an empty string if $value is empty, the name of the month otherwise.
     *
     * @throws \Punic\Exception\BadArgumentType Throws a BadArgumentType exception if $value is not valid.
     * @throws \Punic\Exception\ValueNotInList Throws a ValueNotInList exception if $width is not valid.
     * @throws \Punic\Exception Throws a generic exception in case of other problems (for instance if you specify an invalid locale).
     */
    public static function getMonthName($value, $width = 'wide', $locale = '', $standAlone = false)
    {
        $result = '';
        if ((!empty($value)) || ($value === 0) || ($value === '0')) {
            $month = null;
            if (is_int($value)) {
                $month = $value;
            } elseif (is_float($value)) {
                $month = intval($value);
            } elseif (is_string($value)) {
                if (is_numeric($value)) {
                    $month = intval($value);
                }
            } elseif (is_a($value, '\DateTime')) {
                $month = intval($value->format('n'));
            }
            if (($month === null) || (($month < 1) || ($month > 12))) {
                throw new Exception\BadArgumentType($value, 'month number');
            }
            $data = Data::get('calendar', $locale);
            $data = $data['months'][$standAlone ? 'stand-alone' : 'format'];
            if (!isset($data[$width])) {
                throw new Exception\ValueNotInList($width, array_keys($data));
            }
            $result = $data[$width][$month];
        }

        return $result;
    }

    /**
     * Get the name of a week day.
     *
     * @param number|\DateTime $value A week day number (from 0-Sunday to 6-Saturnday) or a \DateTime instance for which you want the name of the day of the week.
     * @param string $width The format name; it can be 'wide' (eg 'Sunday'), 'abbreviated' (eg 'Sun'), 'short' (eg 'Su') or 'narrow' (eg 'S').
     * @param string $locale The locale to use. If empty we'll use the default locale set with {@link \Punic\Data::setDefaultLocale()}.
     * @param bool $standAlone Set to true to return the form used independently (such as in calendar header), set to false if the week day name will be part of a date.
     *
     * @return string Returns an empty string if $value is empty, the name of the week day name otherwise.
     *
     * @throws \Punic\Exception\BadArgumentType Throws a BadArgumentType exception if $value is not valid.
     * @throws \Punic\Exception\ValueNotInList Throws a ValueNotInList exception if $width is not valid.
     * @throws \Punic\Exception Throws a generic exception in case of other problems (for instance if you specify an invalid locale).
     */
    public static function getWeekdayName($value, $width = 'wide', $locale = '', $standAlone = false)
    {
        $result = '';
        if ((!empty($value)) || ($value === 0) || ($value === '0')) {
            $weekday = null;
            if (is_int($value)) {
                $weekday = $value;
            } elseif (is_float($value)) {
                $weekday = intval($value);
            } elseif (is_string($value)) {
                if (is_numeric($value)) {
                    $weekday = intval($value);
                }
            } elseif (is_a($value, '\DateTime')) {
                $weekday = intval($value->format('w'));
            }
            if (($weekday === null) || (($weekday < 0) || ($weekday > 6))) {
                throw new Exception\BadArgumentType($value, 'weekday number');
            }
            $weekday = self::$weekdayDictionary[$weekday];
            $data = Data::get('calendar', $locale);
            $data = $data['days'][$standAlone ? 'stand-alone' : 'format'];
            if (!isset($data[$width])) {
                throw new Exception\ValueNotInList($width, array_keys($data));
            }
            $result = $data[$width][$weekday];
        }

        return $result;
    }

    /**
     * Get the name of a quarter.
     *
     * @param number|\DateTime $value A quarter number (from 1 to 4) or a \DateTime instance for which you want the name of the day of the quarter.
     * @param string $width The format name; it can be 'wide' (eg '1st quarter'), 'abbreviated' (eg 'Q1') or 'narrow' (eg '1').
     * @param string $locale The locale to use. If empty we'll use the default locale set with {@link \Punic\Data::setDefaultLocale()}.
     * @param bool $standAlone Set to true to return the form used independently (such as in calendar header), set to false if the quarter name will be part of a date.
     *
     * @return string Returns an empty string if $value is empty, the name of the quarter name otherwise.
     *
     * @throws \Punic\Exception\BadArgumentType Throws a BadArgumentType exception if $value is not valid.
     * @throws \Punic\Exception\ValueNotInList Throws a ValueNotInList exception if $width is not valid.
     * @throws \Punic\Exception Throws a generic exception in case of other problems (for instance if you specify an invalid locale).
     */
    public static function getQuarterName($value, $width = 'wide', $locale = '', $standAlone = false)
    {
        $result = '';
        if ((!empty($value)) || ($value === 0) || ($value === '0')) {
            $quarter = null;
            if (is_int($value)) {
                $quarter = $value;
            } elseif (is_float($value)) {
                $quarter = intval($value);
            } elseif (is_string($value)) {
                if (is_numeric($value)) {
                    $quarter = intval($value);
                }
            } elseif (is_a($value, '\DateTime')) {
                $quarter = 1 + intval(floor((intval($value->format('n')) - 1) / 3));
            }
            if (($quarter === null) || (($quarter < 1) || ($quarter > 4))) {
                throw new Exception\BadArgumentType($value, 'quarter number');
            }
            $data = Data::get('calendar', $locale);
            $data = $data['quarters'][$standAlone ? 'stand-alone' : 'format'];
            if (!isset($data[$width])) {
                throw new Exception\ValueNotInList($width, array_keys($data));
            }
            $result = $data[$width][$quarter];
        }

        return $result;
    }

    /**
     * Get the name of a day period (AM/PM).
     *
     * @param number|string|\DateTime $value An hour (from 0 to 23), a standard period name ('am' or 'pm', lower or upper case) a \DateTime instance for which you want the name of the day period.
     * @param string $width The format name; it can be 'wide' (eg 'AM'), 'abbreviated' (eg 'AM') or 'narrow' (eg 'a').
     * @param string $locale The locale to use. If empty we'll use the default locale set with {@link \Punic\Data::setDefaultLocale()}.
     * @param bool $standAlone Set to true to return the form used independently (such as in calendar header), set to false if the day period name will be part of a date.
     *
     * @return string Returns an empty string if $value is empty, the name of the day period name otherwise.
     *
     * @throws \Punic\Exception\BadArgumentType Throws a BadArgumentType exception if $value is not valid.
     * @throws \Punic\Exception\ValueNotInList Throws a ValueNotInList exception if $width is not valid.
     * @throws \Punic\Exception Throws a generic exception in case of other problems (for instance if you specify an invalid locale).
     */
    public static function getDayperiodName($value, $width = 'wide', $locale = '', $standAlone = false)
    {
        static $dictionary = array('am', 'pm');
        $result = '';
        if ((!empty($value)) || ($value === 0) || ($value === '0')) {
            $dayperiod = null;
            $hours = null;
            if (is_int($value)) {
                $hours = $value;
            } elseif (is_float($value)) {
                $hours = intval($value);
            } elseif (is_string($value)) {
                if (is_numeric($value)) {
                    $hours = intval($value);
                } else {
                    $s = strtolower($value);
                    if (in_array($s, $dictionary, true)) {
                        $dayperiod = $s;
                    }
                }
            } elseif (is_a($value, '\DateTime')) {
                $dayperiod = $value->format('a');
            }
            if (($hours !== null) && ($hours >= 0) && ($hours <= 23)) {
                $dayperiod = ($hours < 12) ? 'am' : 'pm';
            }
            if ($dayperiod === null) {
                throw new Exception\BadArgumentType($value, 'day period');
            }
            $data = Data::get('calendar', $locale);
            $data = $data['dayPeriods'][$standAlone ? 'stand-alone' : 'format'];
            if (!isset($data[$width])) {
                throw new Exception\ValueNotInList($width, array_keys($data));
            }
            $result = $data[$width][$dayperiod];
        }

        return $result;
    }

    /**
     * Returns the localized name of a timezone, no location-specific.
     *
     * @param string|\DateTime|\DateTimeZone $value The php name of a timezone, or a \DateTime instance or a \DateTimeZone instance for which you want the localized timezone name.
     * @param string $width The format name; it can be 'long' (eg 'Greenwich Mean Time') or 'short' (eg 'GMT').
     * @param string $kind Set to 'daylight' to retrieve the daylight saving time name, set to 'standard' to retrieve the standard time, set to 'generic' to retrieve the generic name, set to '' to determine automatically the dst (if $value is \DateTime) or the generic (otherwise).
     * @param string $locale The locale to use. If empty we'll use the default locale set with {@link \Punic\Data::setDefaultLocale()}.
     *
     * @return string Returns an empty string if the timezone has not been found (maybe we don't have the data in the specified $width), the timezone name otherwise.
     *
     * @throws \Punic\Exception Throws a generic exception in case of problems (for instance if you specify an invalid locale).
     */
    public static function getTimezoneNameNoLocationSpecific($value, $width = 'long', $kind = '', $locale = '')
    {
        $cacheKey = json_encode(array($value, $width, $kind, empty($locale) ? Data::getDefaultLocale() : $locale));
        if (isset(self::$timezoneCache[$cacheKey])) {
            return self::$timezoneCache[$cacheKey];
        }

        $result = '';
        if (!empty($value)) {
            $receivedPhpName = '';
            $date = '';
            if (is_string($value)) {
                $receivedPhpName = $value;
            } elseif (is_a($value, '\\DateTime')) {
                $receivedPhpName = static::getTimezoneNameFromDatetime($value);
                $date = $value->format('Y-m-d H:i');
                if (empty($kind)) {
                    if (intval($value->format('I')) === 1) {
                        $kind = 'daylight';
                    } else {
                        $kind = 'standard';
                    }
                }
            } elseif (is_a($value, '\\DateTimeZone')) {
                $receivedPhpName = static::getTimezoneNameFromTimezone($value);
            }
            if (isset($receivedPhpName[0])) {
                $metazoneCode = '';
                $data = Data::getGeneric('metaZones');
                $phpNames = static::getTimezonesAliases($receivedPhpName);
                if (!isset($metazoneCode[0])) {
                    foreach ($phpNames as $phpName) {
                        $path = array_merge(array('metazoneInfo'), explode('/', $phpName));
                        $tzInfo = $data;
                        foreach ($path as $chunk) {
                            if (isset($tzInfo[$chunk])) {
                                $tzInfo = $tzInfo[$chunk];
                            } else {
                                $tzInfo = null;
                                break;
                            }
                        }
                        if (is_array($tzInfo)) {
                            foreach ($tzInfo as $tz) {
                                if (is_array($tz) && isset($tz['mzone'])) {
                                    if (isset($date[0])) {
                                        if (isset($tz['from']) && (strcmp($date, $tz['from']) < 0)) {
                                            continue;
                                        }
                                        if (isset($tz['to']) && (strcmp($date, $tz['to']) >= 0)) {
                                            continue;
                                        }
                                    }
                                    $metazoneCode = $tz['mzone'];
                                    break;
                                }
                            }
                        }
                        if (isset($metazoneCode[0])) {
                            break;
                        }
                    }
                }
                if (!isset($metazoneCode[0])) {
                    foreach ($phpNames as $phpName) {
                        foreach ($data['metazones'] as $metazone) {
                            if (strcasecmp($phpName, $metazone['type']) === 0) {
                                $metazoneCode = $metazone['other'];
                                break;
                            }
                        }
                        if (isset($metazoneCode[0])) {
                            break;
                        }
                    }
                }
                if (!isset($metazoneCode[0])) {
                    $metazoneCode = $receivedPhpName;
                }
                if (isset($metazoneCode[0])) {
                    $data = Data::get('timeZoneNames', $locale);
                    if (isset($data['metazone'])) {
                        $data = $data['metazone'];
                        if (isset($data[$metazoneCode])) {
                            $data = $data[$metazoneCode];
                            if (isset($data[$width])) {
                                $data = $data[$width];
                                $lookFor = array();
                                if (!empty($kind)) {
                                    $lookFor[] = $kind;
                                }
                                $lookFor[] = 'generic';
                                $lookFor[] = 'standard';
                                $lookFor[] = 'daylight';
                                foreach ($lookFor as $lf) {
                                    if (isset($data[$lf])) {
                                        $result = $data[$lf];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        self::$timezoneCache[$cacheKey] = $result;

        return $result;
    }

    /** @todo I can't find data for this */
    public static function getTimezoneNameLocationSpecific($value, $width = 'long', $kind = '', $locale = '')
    {
        return '';
    }

    /**
     * Returns the localized name of an exemplar city for a specific timezone.
     *
     * @param string|\DateTime|\DateTimeZone $value The php name of a timezone, or a \DateTime instance or a \DateTimeZone instance
     * @param bool $returnUnknownIfNotFound true If the exemplar city is not found, shall we return the translation of 'Unknown City'?
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string Returns an empty string if the exemplar city hasn't been found and $returnUnknownIfNotFound is false
     */
    public static function getTimezoneExemplarCity($value, $returnUnknownIfNotFound = true, $locale = '')
    {
        $result = '';
        $locale = empty($locale) ? Data::getDefaultLocale() : $locale;
        if (!empty($value)) {
            $receivedPhpName = '';
            if (is_string($value)) {
                $receivedPhpName = $value;
            } elseif (is_a($value, '\\DateTime')) {
                $receivedPhpName = static::getTimezoneNameFromDatetime($value);
            } elseif (is_a($value, '\\DateTimeZone')) {
                $receivedPhpName = static::getTimezoneNameFromTimezone($value);
            }
            if (isset($receivedPhpName[0])) {
                $phpNames = static::getTimezonesAliases($receivedPhpName);
                $timeZoneNames = Data::get('timeZoneNames', $locale);
                foreach ($phpNames as $phpName) {
                    $chunks = array_merge(array('zone'), explode('/', $phpName));
                    $data = $timeZoneNames;
                    foreach ($chunks as $chunk) {
                        if (isset($data[$chunk])) {
                            $data = $data[$chunk];
                        } else {
                            $data = null;
                        }
                        if (!is_array($data)) {
                            break;
                        }
                    }
                    if (is_array($data) && isset($data['exemplarCity'])) {
                        $result = $data['exemplarCity'];
                        break;
                    }
                }
            }
        }
        if ((!isset($result[0])) && $returnUnknownIfNotFound) {
            $result = 'Unknown City';
            $s = static::getTimezoneExemplarCity('Etc/Unknown', false, $locale);
            if (isset($s[0])) {
                $result = $s;
            }
        }

        return $result;
    }

    /**
     * Returns true if a locale has a 12-hour clock, false if 24-hour clock.
     *
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return bool
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     */
    public static function has12HoursClock($locale = '')
    {
        static $cache = array();
        $locale = empty($locale) ? Data::getDefaultLocale() : $locale;
        if (!isset($cache[$locale])) {
            $format = static::getTimeFormat('short', $locale);
            $format = str_replace("''", '', $format);
            $cache[$locale] = (strpos($format, 'a') === false) ? false : true;
        }

        return $cache[$locale];
    }

    /**
     * Retrieve the first weekday for a specific locale (from 0-Sunday to 6-Saturnday).
     *
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return int Returns a number from 0 (Sunday) to 7 (Saturnday)
     */
    public static function getFirstWeekday($locale = '')
    {
        static $cache = array();
        $locale = empty($locale) ? Data::getDefaultLocale() : $locale;
        if (!isset($cache[$locale])) {
            $result = 0;
            $data = Data::getGeneric('weekData');
            $i = Data::getTerritoryNode($data['firstDay'], $locale);
            if (is_int($i)) {
                $result = $i;
            }
            $cache[$locale] = $result;
        }

        return $cache[$locale];
    }

    /**
     * Returns the sorted list of weekdays, starting from {@link getFirstWeekday}.
     *
     * @param string|false $namesWidth If false you'll get only the list of weekday identifiers (for instance: [0, 1, 2, 3, 4, 5, 6]),
     * If it's a string it must be one accepted by {@link getWeekdayName}, and you'll get an array like this: [{id: 0, name: 'Monday', ..., {id: 6, name: 'Sunday'}]
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return array
     */
    public static function getSortedWeekdays($namesWidth = false, $locale = '')
    {
        $codes = array();
        $code = static::getFirstWeekday($locale);
        for ($count = 0; $count < 7; ++$count) {
            $codes[] = $code;
            $code += 1;
            if ($code === 7) {
                $code = 0;
            }
        }
        if (empty($namesWidth)) {
            $result = $codes;
        } else {
            $result = array();
            foreach ($codes as $code) {
                $result[] = array('id' => $code, 'name' => static::getWeekdayName($code, $namesWidth, $locale, true));
            }
        }

        return $result;
    }

    /**
     * Get the ISO format for a date.
     *
     * @param string $width The format name; it can be 'full' (eg 'EEEE, MMMM d, y' - 'Wednesday, August 20, 2014'), 'long' (eg 'MMMM d, y' - 'August 20, 2014'), 'medium' (eg 'MMM d, y' - 'August 20, 2014') or 'short' (eg 'M/d/yy' - '8/20/14')
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string Returns the requested ISO format
     *
     * @throws Exception Throws an exception in case of problems
     *
     * @link http://cldr.unicode.org/translation/date-time-patterns
     * @link http://cldr.unicode.org/translation/date-time
     * @link http://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
     */
    public static function getDateFormat($width, $locale = '')
    {
        $data = Data::get('calendar', $locale);
        $data = $data['dateFormats'];
        if (!isset($data[$width])) {
            throw new Exception\ValueNotInList($width, array_keys($data));
        }

        return $data[$width];
    }

    /**
     * Get the ISO format for a time.
     *
     * @param string $width The format name; it can be 'full' (eg 'h:mm:ss a zzzz' - '11:42:13 AM GMT+2:00'), 'long' (eg 'h:mm:ss a z' - '11:42:13 AM GMT+2:00'), 'medium' (eg 'h:mm:ss a' - '11:42:13 AM') or 'short' (eg 'h:mm a' - '11:42 AM')
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string Returns the requested ISO format
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @link http://cldr.unicode.org/translation/date-time-patterns
     * @link http://cldr.unicode.org/translation/date-time
     * @link http://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
     */
    public static function getTimeFormat($width, $locale = '')
    {
        $data = Data::get('calendar', $locale);
        $data = $data['timeFormats'];
        if (!isset($data[$width])) {
            throw new Exception\ValueNotInList($width, array_keys($data));
        }

        return $data[$width];
    }

    /**
     * Get the ISO format for a date/time.
     *
     * @param string $width The format name; it can be 'full', 'long', 'medium', 'short' or a combination for date+time like 'full|short' or a combination for format+date+time like 'full|full|short'
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string Returns the requested ISO format
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @link http://cldr.unicode.org/translation/date-time-patterns
     * @link http://cldr.unicode.org/translation/date-time
     * @link http://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
     */
    public static function getDatetimeFormat($width, $locale = '')
    {
        return static::getDatetimeFormatReal($width, $locale);
    }

    protected static function getDatetimeFormatReal($width, $locale = '', $overrideDateFormat = '', $overrideTimeFormat = '')
    {
        $chunks = explode('|', @str_replace(array('*', '^'), '', $width));
        switch (count($chunks)) {
            case 1:
                $timeWidth = $dateWidth = $wholeWidth = $chunks[0];
                break;
            case 2:
                $sortedChunks = $chunks;
                usort($sortedChunks, function ($a, $b) {
                    $cmp = 0;
                    if ($a !== $b) {
                        foreach (array('full', 'long', 'medium', 'short') as $w) {
                            if ($a === $w) {
                                $cmp = -1;
                                break;
                            }
                            if ($b === $w) {
                                $cmp = 1;
                                break;
                            }
                        }
                    }

                    return $cmp;
                });
                $wholeWidth = $sortedChunks[0];
                $dateWidth = $chunks[0];
                $timeWidth = $chunks[1];
                break;
            case 3:
                $wholeWidth = $chunks[0];
                $dateWidth = $chunks[1];
                $timeWidth = $chunks[2];
                break;
            default:
                throw new Exception\BadArgumentType($width, 'pipe-separated list of strings (from 1 to 3 chunks)');
        }
        $data = Data::get('calendar', $locale);
        $data = $data['dateTimeFormats'];
        if (!isset($data[$wholeWidth])) {
            throw new Exception\ValueNotInList($wholeWidth, array_keys($data));
        }

        return sprintf(
            $data[$wholeWidth],
            isset($overrideTimeFormat[0]) ? $overrideTimeFormat : static::getTimeFormat($timeWidth, $locale),
            isset($overrideDateFormat[0]) ? $overrideDateFormat : static::getDateFormat($dateWidth, $locale)
        );
    }

    /**
     * Returns the difference in days between two dates (or between a date and today).
     *
     * @param \DateTime $dateEnd The first date
     * @param \DateTime|null $dateStart The final date (if it has a timezone different than $dateEnd, we'll use the one of $dateEnd)
     *
     * @return int Returns the diffence $dateEnd - $dateStart in days
     *
     * @throws Exception\BadArgumentType
     */
    public static function getDeltaDays($dateEnd, $dateStart = null)
    {
        if (!is_a($dateEnd, '\\DateTime')) {
            throw new Exception\BadArgumentType($dateEnd, '\\DateTime');
        }
        if (empty($dateStart) && ($dateStart !== 0) && ($dateStart !== '0')) {
            $dateStart = new \DateTime('now', $dateEnd->getTimezone());
        }
        if (!is_a($dateStart, '\\DateTime')) {
            throw new Exception\BadArgumentType($dateStart, '\\DateTime');
        }
        if ($dateStart->getOffset() !== $dateEnd->getOffset()) {
            $dateStart->setTimezone($dateEnd->getTimezone());
        }
        $utc = new \DateTimeZone('UTC');
        $dateEndUTC = new \DateTime($dateEnd->format('Y-m-d'), $utc);
        $dateStartUTC = new \DateTime($dateStart->format('Y-m-d'), $utc);
        $seconds = $dateEndUTC->getTimestamp() - $dateStartUTC->getTimestamp();

        return intval(round($seconds / 86400));
    }

    /**
     * Describe an interval between two dates (eg '2 days and 4 hours').
     *
     * @param \DateTime $dateEnd The first date
     * @param \DateTime|null $dateStart The final date (if it has a timezone different than $dateEnd, we'll use the one of $dateEnd)
     * @param int $maxParts The maximim parts (eg with 2 you may have '2 days and 4 hours', with 3 '2 days, 4 hours and 24 minutes')
     * @param string $width The format name; it can be 'long' (eg '3 seconds'), 'short' (eg '3 s') or 'narrow' (eg '3s')
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string
     *
     * @throws Exception\BadArgumentType
     */
    public static function describeInterval($dateEnd, $dateStart = null, $maxParts = 2, $width = 'short', $locale = '')
    {
        if (!is_a($dateEnd, '\\DateTime')) {
            throw new Exception\BadArgumentType($dateEnd, '\\DateTime');
        }
        if (empty($dateStart) && ($dateStart !== 0) && ($dateStart !== '0')) {
            $dateStart = new \DateTime('now', $dateEnd->getTimezone());
        }
        if (!is_a($dateStart, '\\DateTime')) {
            throw new Exception\BadArgumentType($dateStart, '\\DateTime');
        }
        if ($dateStart->getOffset() !== $dateEnd->getOffset()) {
            $dateStart->setTimezone($dateEnd->getTimezone());
        }
        $utc = new \DateTimeZone('UTC');
        $dateEndUTC = new \DateTime($dateEnd->format('Y-m-d H:i:s'), $utc);
        $dateStartUTC = new \DateTime($dateStart->format('Y-m-d H:i:s'), $utc);

        $parts = array();
        $data = Data::get('dateFields', $locale);
        if ($dateEndUTC->getTimestamp() == $dateStartUTC->getTimestamp()) {
            $parts[] = $data['second']['relative-type-0'];
        } else {
            $diff = $dateStartUTC->diff($dateEndUTC, true);
            $mostFar = 0;
            $maxDistance = 3;
            if (($mostFar < $maxDistance) && ($diff->y > 0)) {
                $parts[] = Unit::format($diff->y, 'duration/year', $width, $locale);
                $mostFar = 0;
            } elseif (!empty($parts)) {
                ++$mostFar;
            }
            if (($mostFar < $maxDistance) && ($diff->m > 0)) {
                $parts[] = Unit::format($diff->m, 'duration/month', $width, $locale);
                $mostFar = 0;
            } elseif (!empty($parts)) {
                ++$mostFar;
            }
            if (($mostFar < $maxDistance) && ($diff->d > 0)) {
                $parts[] = Unit::format($diff->d, 'duration/day', $width, $locale);
                $mostFar = 0;
            } elseif (!empty($parts)) {
                ++$mostFar;
            }
            if (($mostFar < $maxDistance) && ($diff->h > 0)) {
                $parts[] = Unit::format($diff->h, 'duration/hour', $width, $locale);
                $mostFar = 0;
            } elseif (!empty($parts)) {
                ++$mostFar;
            }
            if (($mostFar < $maxDistance) && ($diff->i > 0)) {
                $parts[] = Unit::format($diff->i, 'duration/minute', $width, $locale);
                $mostFar = 0;
            } elseif (!empty($parts)) {
                ++$mostFar;
            }
            if (empty($parts) || ($diff->s > 0)) {
                $parts[] = Unit::format($diff->s, 'duration/second', $width, $locale);
            }
            if (count($parts) > $maxParts) {
                $parts = array_slice($parts, 0, $maxParts);
            }
        }
        switch ($width) {
            case 'narrow':
            case 'short':
                $joined = Misc::joinUnits($parts, $width, $locale);
                break;
            default:
                $joined = Misc::join($parts, $locale);
                break;
        }

        return $joined;
    }

    /**
     * Format a date.
     *
     * @param \DateTime $value The \DateTime instance for which you want the localized textual representation
     * @param string $width The format name; it can be 'full' (eg 'EEEE, MMMM d, y' - 'Wednesday, August 20, 2014'), 'long' (eg 'MMMM d, y' - 'August 20, 2014'), 'medium' (eg 'MMM d, y' - 'August 20, 2014') or 'short' (eg 'M/d/yy' - '8/20/14').
     *                      You can also append a caret ('^') or an asterisk ('*') to $width. If so, special day names may be used (like 'Today', 'Yesterday', 'Tomorrow' with '^' and 'today', 'yesterday', 'tomorrow' width '*') instead of the date.
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string Returns an empty string if $value is empty, the localized textual representation otherwise
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @link http://cldr.unicode.org/translation/date-time-patterns
     * @link http://cldr.unicode.org/translation/date-time
     * @link http://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
     */
    public static function formatDate($value, $width, $locale = '')
    {
        $c = is_string($width) ? @substr($width, -1) : '';
        if (($c === '^') || ($c === '*')) {
            $dayName = static::getDateRelativeName($value, ($c === '^') ? true : false, $locale);
            if (isset($dayName[0])) {
                return $dayName;
            }
            $width = substr($width, 0, -1);
        }

        return static::format(
            $value,
            static::getDateFormat($width, $locale),
            $locale
        );
    }

    /**
     * Format a date (extended version: various date/time representations - see toDateTime()).
     *
     * @param number|\DateTime|string $value An Unix timestamp, a `\DateTime` instance or a string accepted by {@link http://php.net/manual/function.strtotime.php strtotime}.
     * @param string $width The format name; it can be 'full' (eg 'EEEE, MMMM d, y' - 'Wednesday, August 20, 2014'), 'long' (eg 'MMMM d, y' - 'August 20, 2014'), 'medium' (eg 'MMM d, y' - 'August 20, 2014') or 'short' (eg 'M/d/yy' - '8/20/14')
     *                      You can also append a caret ('^') or an asterisk ('*') to $width. If so, special day names may be used (like 'Today', 'Yesterday', 'Tomorrow' with '^' and 'today', 'yesterday', 'tomorrow' width '*') instead of the date.
     * @param string|\DateTimeZone $toTimezone The timezone to set; leave empty to use the default timezone (or the timezone associated to $value if it's already a \DateTime)
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string Returns an empty string if $value is empty, the localized textual representation otherwise
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @see toDateTime()
     * @link http://cldr.unicode.org/translation/date-time-patterns
     * @link http://cldr.unicode.org/translation/date-time
     * @link http://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
     */
    public static function formatDateEx($value, $width, $toTimezone = '', $locale = '')
    {
        return static::formatDate(
            static::toDateTime($value, $toTimezone),
            $width,
            $locale
        );
    }

    /**
     * Format a time.
     *
     * @param \DateTime $value The \DateTime instance for which you want the localized textual representation
     * @param string $width The format name; it can be 'full' (eg 'h:mm:ss a zzzz' - '11:42:13 AM GMT+2:00'), 'long' (eg 'h:mm:ss a z' - '11:42:13 AM GMT+2:00'), 'medium' (eg 'h:mm:ss a' - '11:42:13 AM') or 'short' (eg 'h:mm a' - '11:42 AM')
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string Returns an empty string if $value is empty, the localized textual representation otherwise
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @link http://cldr.unicode.org/translation/date-time-patterns
     * @link http://cldr.unicode.org/translation/date-time
     * @link http://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
     */
    public static function formatTime($value, $width, $locale = '')
    {
        return static::format(
            $value,
            static::getTimeFormat($width, $locale),
            $locale
        );
    }

    /**
     * Format a time (extended version: various date/time representations - see toDateTime()).
     *
     * @param number|\DateTime|string $value An Unix timestamp, a `\DateTime` instance or a string accepted by {@link http://php.net/manual/function.strtotime.php strtotime}.
     * @param string $width The format name; it can be 'full' (eg 'h:mm:ss a zzzz' - '11:42:13 AM GMT+2:00'), 'long' (eg 'h:mm:ss a z' - '11:42:13 AM GMT+2:00'), 'medium' (eg 'h:mm:ss a' - '11:42:13 AM') or 'short' (eg 'h:mm a' - '11:42 AM')
     * @param string|\DateTimeZone $toTimezone The timezone to set; leave empty to use the default timezone (or the timezone associated to $value if it's already a \DateTime)
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string Returns an empty string if $value is empty, the localized textual representation otherwise
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @see toDateTime()
     * @link http://cldr.unicode.org/translation/date-time-patterns
     * @link http://cldr.unicode.org/translation/date-time
     * @link http://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
     */
    public static function formatTimeEx($value, $width, $toTimezone = '', $locale = '')
    {
        return static::formatTime(
            static::toDateTime($value, $toTimezone),
            $width,
            $locale
        );
    }

    /**
     * Format a date/time.
     *
     * @param \DateTime $value The \DateTime instance for which you want the localized textual representation
     * @param string $width The format name; it can be 'full', 'long', 'medium', 'short' or a combination for date+time like 'full|short' or a combination for format+date+time like 'full|full|short'
     *                      You can also append an asterisk ('*') to the date parh of $width. If so, special day names may be used (like 'Today', 'Yesterday', 'Tomorrow') instead of the date part.
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string Returns an empty string if $value is empty, the localized textual representation otherwise
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @link http://cldr.unicode.org/translation/date-time-patterns
     * @link http://cldr.unicode.org/translation/date-time
     * @link http://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
     */
    public static function formatDatetime($value, $width, $locale = '')
    {
        $overrideDateFormat = '';
        if (is_string($width)) {
            $dateFormat = '';
            $chunks = explode('|', $width);
            switch (count($chunks)) {
                case 1:
                case 2:
                    $dateFormat = $chunks[0];
                    break;
                case 3:
                    $dateFormat = $chunks[1];
                    break;
            }
            $c = isset($dateFormat[0]) ? @substr($dateFormat, -1) : '';
            if (($c === '^') || ($c === '*')) {
                $dayName = static::getDateRelativeName($value, ($c === '^') ? true : false, $locale);
                if (isset($dayName[0])) {
                    $overrideDateFormat = "'$dayName'";
                }
            }
        }

        return static::format(
            $value,
            static::getDatetimeFormatReal($width, $locale, $overrideDateFormat),
            $locale
        );
    }

    /**
     * Format a date/time (extended version: various date/time representations - see toDateTime()).
     *
     * @param number|\DateTime|string $value An Unix timestamp, a `\DateTime` instance or a string accepted by {@link http://php.net/manual/function.strtotime.php strtotime}.
     * @param string $width The format name; it can be 'full', 'long', 'medium', 'short' or a combination for date+time like 'full|short' or a combination for format+date+time like 'full|full|short'
     *                      You can also append an asterisk ('*') to the date parh of $width. If so, special day names may be used (like 'Today', 'Yesterday', 'Tomorrow') instead of the date part.
     * @param string|\DateTimeZone $toTimezone The timezone to set; leave empty to use the default timezone (or the timezone associated to $value if it's already a \DateTime)
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string Returns an empty string if $value is empty, the localized textual representation otherwise
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @see toDateTime()
     * @link http://cldr.unicode.org/translation/date-time-patterns
     * @link http://cldr.unicode.org/translation/date-time
     * @link http://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
     */
    public static function formatDatetimeEx($value, $width, $toTimezone = '', $locale = '')
    {
        return static::formatDatetime(
            static::toDateTime($value, $toTimezone),
            $width,
            $locale
        );
    }

    /**
     * Format a date and/or time.
     *
     * @param \DateTime $value The \DateTime instance for which you want the localized textual representation
     * @param string $format The ISO format that specify how to render the date/time. The following extra format chunks are available:
     * - 'P': ISO-8601 numeric representation of the day of the week (same as 'e' but not locale dependent)
     * - 'PP': Numeric representation of the day of the week, from 0 (for Sunday) to 6 (for Saturday)
     * - 'PPP': English ordinal suffix for the day of the month
     * - 'PPPP': The day of the year (starting from 0)
     * - 'PPPPP': Number of days in the given month
     * - 'PPPPPP': Whether it's a leap year: 1 if it is a leap year, 0 otherwise.
     * - 'PPPPPPP': Lowercase Ante meridiem and Post meridiem (English only, for other locales it's the same as 'a')
     * - 'PPPPPPPP': Swatch Internet time
     * - 'PPPPPPPPP': Microseconds
     * - 'PPPPPPPPPP': Whether or not the date is in daylight saving time	1 if Daylight Saving Time, 0 otherwise.
     * - 'PPPPPPPPPPP': Timezone offset in seconds
     * - 'PPPPPPPPPPPP': RFC 2822 formatted date (Example: 'Thu, 21 Dec 2000 16:01:07 +0200')
     * - 'PPPPPPPPPPPPP': Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string Returns an empty string if $value is empty, the localized textual representation otherwise
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @link http://cldr.unicode.org/translation/date-time-patterns
     * @link http://cldr.unicode.org/translation/date-time
     * @link http://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
     */
    public static function format($value, $format, $locale = '')
    {
        static $decodeCache = array();
        static $decoderFunctions = array(
            'G' => 'decodeEra',
            'y' => 'decodeYear',
            'Y' => 'decodeYearWeekOfYear',
            'u' => 'decodeYearExtended',
            'U' => 'decodeYearCyclicName',
            'r' => 'decodeYearRelatedGregorian',
            'Q' => 'decodeQuarter',
            'q' => 'decodeQuarterAlone',
            'M' => 'decodeMonth',
            'L' => 'decodeMonthAlone',
            'w' => 'decodeWeekOfYear',
            'W' => 'decodeWeekOfMonth',
            'd' => 'decodeDayOfMonth',
            'D' => 'decodeDayOfYear',
            'F' => 'decodeWeekdayInMonth',
            'g' => 'decodeModifiedGiulianDay',
            'E' => 'decodeDayOfWeek',
            'e' => 'decodeDayOfWeekLocal',
            'c' => 'decodeDayOfWeekLocalAlone',
            'a' => 'decodeDayperiod',
            'h' => 'decodeHour12',
            'H' => 'decodeHour24',
            'K' => 'decodeHour12From0',
            'k' => 'decodeHour24From1',
            'm' => 'decodeMinute',
            's' => 'decodeSecond',
            'S' => 'decodeFranctionsOfSeconds',
            'A' => 'decodeMsecInDay',
            'z' => 'decodeTimezoneNoLocationSpecific',
            'Z' => 'decodeTimezoneDelta',
            'O' => 'decodeTimezoneShortGMT',
            'v' => 'decodeTimezoneNoLocationGeneric',
            'V' => 'decodeTimezoneID',
            'X' => 'decodeTimezoneWithTimeZ',
            'x' => 'decodeTimezoneWithTime',
            'P' => 'decodePunicExtension',
        );
        $result = '';
        if (!empty($value)) {
            if (!is_a($value, '\\DateTime')) {
                throw new Exception\BadArgumentType($value, '\\DateTime');
            }
            $length = is_string($format) ? strlen($format) : 0;
            if ($length === 0) {
                throw new Exception\BadArgumentType($format, 'date/time ISO format');
            }
            if (empty($locale)) {
                $locale = Data::getDefaultLocale();
            }
            if (!isset($decodeCache[$locale])) {
                $decodeCache[$locale] = array();
            }
            if (!isset($decodeCache[$locale][$format])) {
                $decoder = array();
                $lengthM1 = $length - 1;
                $quoted = false;
                for ($index = 0; $index < $length; ++$index) {
                    $char = $format[$index];
                    if ($char === "'") {
                        if ($quoted) {
                            $quoted = false;
                        } elseif (($index < $lengthM1) && ($format[$index + 1] === "'")) {
                            $decoder[] = "'";
                            ++$index;
                        } else {
                            $quoted = true;
                        }
                    } elseif ($quoted) {
                        $decoder[] = $char;
                    } else {
                        $count = 1;
                        for ($j = $index + 1; ($j < $length) && ($format[$j] === $char); ++$j) {
                            ++$count;
                            ++$index;
                        }
                        if (isset($decoderFunctions[$char])) {
                            $decoder[] = array($decoderFunctions[$char], $count);
                        } else {
                            $decoder[] = str_repeat($char, $count);
                        }
                    }
                }
                $decodeCache[$locale][$format] = $decoder;
            } else {
                $decoder = $decodeCache[$locale][$format];
            }
            foreach ($decoder as $chunk) {
                if (is_string($chunk)) {
                    $result .= $chunk;
                } else {
                    $functionName = $chunk[0];
                    $count = $chunk[1];
                    $result .= static::$functionName($value, $count, $locale);
                }
            }
        }

        return $result;
    }
    /**
     * Format a date and/or time (extended version: various date/time representations - see toDateTime()).
     *
     * @param number|\DateTime|string $value An Unix timestamp, a `\DateTime` instance or a string accepted by {@link http://php.net/manual/function.strtotime.php strtotime}.
     * @param string $format The ISO format that specify how to render the date/time. The following extra format chunks are valid:
     * - 'P': ISO-8601 numeric representation of the day of the week (same as 'e' but not locale dependent)
     * - 'PP': Numeric representation of the day of the week, from 0 (for Sunday) to 6 (for Saturday)
     * - 'PPP': English ordinal suffix for the day of the month
     * - 'PPPP': The day of the year (starting from 0)
     * - 'PPPPP': Number of days in the given month
     * - 'PPPPPP': Whether it's a leap year: 1 if it is a leap year, 0 otherwise.
     * - 'PPPPPPP': Lowercase Ante meridiem and Post meridiem (English only, for other locales it's the same as 'a')
     * - 'PPPPPPPP': Swatch Internet time
     * - 'PPPPPPPPP': Microseconds
     * - 'PPPPPPPPPP': Whether or not the date is in daylight saving time	1 if Daylight Saving Time, 0 otherwise.
     * - 'PPPPPPPPPPP': Timezone offset in seconds
     * - 'PPPPPPPPPPPP': RFC 2822 formatted date (Example: 'Thu, 21 Dec 2000 16:01:07 +0200')
     * - 'PPPPPPPPPPPPP': Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)
     * @param string|\DateTimeZone $toTimezone The timezone to set; leave empty to use the default timezone (or the timezone associated to $value if it's already a \DateTime)
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string Returns an empty string if $value is empty, the localized textual representation otherwise
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @link http://cldr.unicode.org/translation/date-time-patterns
     * @link http://cldr.unicode.org/translation/date-time
     * @link http://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
     */
    public static function formatEx($value, $format, $toTimezone = '', $locale = '')
    {
        return static::format(
            static::toDateTime($value, $toTimezone),
            $format,
            $locale
        );
    }

    /**
     * Retrieve the relative day name (eg 'yesterday', 'tomorrow'), if available.
     *
     * @param \DateTime $datetime The date for which you want the relative day name
     * @param bool $ucFirst Force first letter to be upper case?
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return string Returns the relative name if available, otherwise returns an empty string
     */
    public static function getDateRelativeName($datetime, $ucFirst = false, $locale = '')
    {
        $result = '';
        $deltaDays = static::getDeltaDays($datetime);
        $data = Data::get('dateFields', $locale);
        if (isset($data['day'])) {
            $data = $data['day'];
            $key = "relative-type-$deltaDays";
            if (isset($data[$key])) {
                $result = $data[$key];
                if ($ucFirst) {
                    $result = Misc::fixCase($result, 'titlecase-firstword');
                }
            }
        }

        return $result;
    }

    protected static function decodeDayOfWeek(\DateTime $value, $count, $locale, $standAlone = false)
    {
        switch ($count) {
            case 1:
            case 2:
            case 3:
                return static::getWeekdayName($value, 'abbreviated', $locale, $standAlone);
            case 4:
                return static::getWeekdayName($value, 'wide', $locale, $standAlone);
            case 5:
                return static::getWeekdayName($value, 'narrow', $locale, $standAlone);
            case 6:
                return static::getWeekdayName($value, 'short', $locale, $standAlone);
            default:
                throw new Exception\ValueNotInList($count, array(1, 2, 3, 4, 5, 6));
        }
    }

    protected static function decodeDayOfWeekLocal(\DateTime $value, $count, $locale, $standAlone = false)
    {
        switch ($count) {
            case 1:
            case 2:
                $weekDay = intval($value->format('w'));
                $firstWeekdayForCountry = static::getFirstWeekday($locale);
                $localWeekday = 1 + ((7 + $weekDay - $firstWeekdayForCountry) % 7);

                return str_pad(strval($localWeekday), $count, '0', STR_PAD_LEFT);
            default:
                return static::decodeDayOfWeek($value, $count, $locale, $standAlone);
        }
    }

    protected static function decodeDayOfWeekLocalAlone(\DateTime $value, $count, $locale)
    {
        return static::decodeDayOfWeekLocal($value, $count, $locale, true);
    }

    protected static function decodeDayOfMonth(\DateTime $value, $count, $locale)
    {
        switch ($count) {
            case 1:
                return $value->format('j');
            case 2:
                return $value->format('d');
            default:
                throw new Exception\ValueNotInList($count, array(1, 2));
        }
    }

    protected static function decodeMonth(\DateTime $value, $count, $locale, $standAlone = false)
    {
        switch ($count) {
            case 1:
                return $value->format('n');
            case 2:
                return $value->format('m');
            case 3:
                return static::getMonthName($value, 'abbreviated', $locale, $standAlone);
            case 4:
                return static::getMonthName($value, 'wide', $locale, $standAlone);
            case 5:
                return static::getMonthName($value, 'narrow', $locale, $standAlone);
            default:
                throw new Exception\ValueNotInList($count, array(1, 2, 3, 4, 5));
        }
    }

    protected static function decodeMonthAlone(\DateTime $value, $count, $locale)
    {
        return static::decodeMonth($value, $count, $locale, true);
    }

    protected static function decodeYear(\DateTime $value, $count, $locale)
    {
        switch ($count) {
            case 1:
                return strval(intval($value->format('Y')));
            case 2:
                return $value->format('y');
            default:
                $s = $value->format('Y');
                if (!isset($s[$count])) {
                    $s = str_pad($s, $count, '0', STR_PAD_LEFT);
                }

                return $s;
        }
    }

    protected static function decodeHour12(\DateTime $value, $count, $locale)
    {
        switch ($count) {
            case 1:
                return $value->format('g');
            case 2:
                return $value->format('h');
            default:
                throw new Exception\ValueNotInList($count, array(1, 2));
        }
    }

    protected static function decodeDayperiod(\DateTime $value, $count, $locale)
    {
        switch ($count) {
            case 1:
                return static::getDayperiodName($value, 'abbreviated', $locale);
            default:
                throw new Exception\ValueNotInList($count, array(1));
        }
    }

    protected static function decodeHour24(\DateTime $value, $count, $locale)
    {
        switch ($count) {
            case 1:
                return $value->format('G');
            case 2:
                return $value->format('H');
            default:
                throw new Exception\ValueNotInList($count, array(1, 2));
        }
    }

    protected static function decodeHour12From0(\DateTime $value, $count, $locale)
    {
        switch ($count) {
            case 1:
            case 2:
                return str_pad(strval(intval($value->format('G')) % 12), $count, '0', STR_PAD_LEFT);
            default:
                throw new Exception\ValueNotInList($count, array(1, 2));
        }
    }

    protected static function decodeHour24From1(\DateTime $value, $count, $locale)
    {
        switch ($count) {
            case 1:
            case 2:
                return str_pad(strval(1 + intval($value->format('G'))), $count, '0', STR_PAD_LEFT);
            default:
                throw new Exception\ValueNotInList($count, array(1, 2));
        }
    }

    protected static function decodeMinute(\DateTime $value, $count, $locale)
    {
        switch ($count) {
            case 1:
                return strval(intval($value->format('i')));
            case 2:
                return $value->format('i');
            default:
                throw new Exception\ValueNotInList($count, array(1, 2));
        }
    }

    protected static function decodeSecond(\DateTime $value, $count, $locale)
    {
        switch ($count) {
            case 1:
                return strval(intval($value->format('s')));
            case 2:
                return $value->format('s');
            default:
                throw new Exception\ValueNotInList($count, array(1, 2));
        }
    }

    protected static function decodeTimezoneNoLocationSpecific(\DateTime $value, $count, $locale)
    {
        switch ($count) {
            case 1:
            case 2:
            case 3:
                $tz = static::getTimezoneNameNoLocationSpecific($value, 'short', '', $locale);
                if (!isset($tz[0])) {
                    $tz = static::decodeTimezoneShortGMT($value, 1, $locale);
                }
                break;
            case 4:
                $tz = static::getTimezoneNameNoLocationSpecific($value, 'long', '', $locale);
                if (!isset($tz[0])) {
                    $tz = static::decodeTimezoneShortGMT($value, 4, $locale);
                }
                break;
            default:
                throw new Exception\ValueNotInList($count, array(1, 2, 3, 4));
        }

        return $tz;
    }

    protected static function decodeTimezoneShortGMT(\DateTime $value, $count, $locale)
    {
        $offset = $value->getOffset();
        $sign = ($offset < 0) ? '-' : '+';
        $seconds = abs($offset);
        $hours = intval(floor($seconds / 3600));
        $seconds -= $hours * 3600;
        $minutes = intval(floor($seconds / 60));
        $data = Data::get('timeZoneNames', $locale);
        $format = isset($data['gmtFormat']) ? $data['gmtFormat'] : 'GMT%1$s';
        switch ($count) {
            case 1:
                return sprintf($format, $sign.$hours.(($minutes === 0) ? '' : (':'.substr('0'.$minutes, -2))));
            case 4:
                return sprintf($format, $sign.$hours.':'.substr('0'.$minutes, -2));
            default:
                throw new Exception\ValueNotInList($count, array(1, 4));
        }
    }

    protected static function decodeEra(\DateTime $value, $count, $locale)
    {
        switch ($count) {
            case 1:
            case 2:
            case 3:
                return static::getEraName($value, 'abbreviated', $locale);
            case 4:
                return static::getEraName($value, 'wide', $locale);
            case 5:
                return static::getEraName($value, 'narrow', $locale);
            default:
                throw new Exception\ValueNotInList($count, array(1, 2, 3, 4, 5));
        }
    }

    protected static function decodeYearWeekOfYear(\DateTime $value, $count, $locale)
    {
        $y = $value->format('o');
        if ($count === 2) {
            $y = substr('0'.$y, -2);
        } else {
            if (!isset($y[$count])) {
                $y = str_pad($y, $count, '0', STR_PAD_LEFT);
            }
        }

        return $y;
    }

    /**
     * Note: we assume Gregorian calendar here.
     */
    protected static function decodeYearExtended(\DateTime $value, $count, $locale)
    {
        return static::decodeYear($value, $count, $locale);
    }

    /**
     * Note: we assume Gregorian calendar here.
     */
    protected static function decodeYearRelatedGregorian(\DateTime $value, $count, $locale)
    {
        return static::decodeYearExtended($value, $count, $locale);
    }

    protected static function decodeQuarter(\DateTime $value, $count, $locale, $standAlone = false)
    {
        $quarter = 1 + intval(floor((intval($value->format('n')) - 1) / 3));
        switch ($count) {
            case 1:
                return strval($quarter);
            case 2:
                return '0'.strval($quarter);
            case 3:
                return static::getQuarterName($quarter, 'abbreviated', $locale, $standAlone);
            case 4:
                return static::getQuarterName($quarter, 'wide', $locale, $standAlone);
            case 5:
                return static::getQuarterName($quarter, 'narrow', $locale, $standAlone);
            default:
                throw new Exception\ValueNotInList($count, array(1, 2, 3, 4, 5));
        }
    }

    protected static function decodeQuarterAlone(\DateTime $value, $count, $locale)
    {
        return static::decodeQuarter($value, $count, $locale, true);
    }

    protected static function decodeWeekOfYear(\DateTime $value, $count, $locale)
    {
        switch ($count) {
            case 1:
                return strval(intval($value->format('W')));
            case 2:
                return $value->format('W');
            default:
                throw new Exception\ValueNotInList($count, array(1, 2));
        }
    }

    protected static function decodeDayOfYear(\DateTime $value, $count, $locale)
    {
        switch ($count) {
            case 1:
            case 2:
            case 3:
                return str_pad(strval(1 + $value->format('z')), $count, '0', STR_PAD_LEFT);
            default:
                throw new Exception\ValueNotInList($count, array(1, 2, 3));
        }
    }

    protected static function decodeWeekdayInMonth(\DateTime $value, $count, $locale)
    {
        switch ($count) {
            case 1:
            case 2:
            case 3:
                $dom = intval($value->format('j'));
                $wim = 1 + intval(floor(($dom - 1) / 7));

                return str_pad(strval($wim), $count, '0', STR_PAD_LEFT);
            default:
                throw new Exception\ValueNotInList($count, array(1, 2, 3));
        }
    }

    protected static function decodeFranctionsOfSeconds(\DateTime $value, $count, $locale)
    {
        $us = intval($value->format('u'));
        if ($count >= 6) {
            $result = str_pad(strval($us), $count, '0', STR_PAD_RIGHT);
        } elseif ($count >= 1) {
            $v = intval(floor($us / pow(10, 6 - $count)));
            $result = str_pad(strval($v), $count, '0', STR_PAD_LEFT);
        } else {
            $result = '';
        }

        return $result;
    }

    protected static function decodeMsecInDay(\DateTime $value, $count, $locale)
    {
        $hours = intval($value->format('G'));
        $minutes = $hours * 60 + intval($value->format('i'));
        $seconds = $minutes * 60 + intval($value->format('s'));
        $milliseconds = $seconds * 1000 + intval(floor(intval($value->format('u')) / 1000));

        return str_pad(strval($milliseconds), $count, '0', STR_PAD_LEFT);
    }

    protected static function decodeTimezoneDelta(\DateTime $value, $count, $locale)
    {
        $offset = $value->getOffset();
        $sign = ($offset < 0) ? '-' : '+';
        $seconds = abs($offset);
        $hours = intval(floor($seconds / 3600));
        $seconds -= $hours * 3600;
        $minutes = intval(floor($seconds / 60));
        $seconds -= $minutes * 60;
        $partsWithoutSeconds = array();
        $partsWithoutSeconds[] = $sign.substr('0'.strval($hours), -2);
        $partsWithoutSeconds[] = substr('0'.strval($minutes), -2);
        $partsMaybeWithSeconds = $partsWithoutSeconds;
        /* @TZWS
        if ($seconds > 0) {
            $partsMaybeWithSeconds[] = substr('0' . strval($seconds), -2);
        }
        */
        switch ($count) {
            case 1:
            case 2:
            case 3:
                return implode('', $partsMaybeWithSeconds);
            case 4:
                $data = Data::get('timeZoneNames', $locale);
                $format = isset($data['gmtFormat']) ? $data['gmtFormat'] : 'GMT%1$s';

                return sprintf($format, implode(':', $partsWithoutSeconds));
            case 5:
                return implode(':', $partsMaybeWithSeconds);
            default:
                throw new Exception\ValueNotInList($count, array(1, 2, 3, 4, 5));
        }
    }

    protected static function decodeTimezoneNoLocationGeneric(\DateTime $value, $count, $locale)
    {
        switch ($count) {
            case 1:
                $tz = static::getTimezoneNameNoLocationSpecific($value, 'short', 'generic', $locale);
                if (!isset($tz[0])) {
                    $tz = static::decodeTimezoneID($value, 4, $locale);
                }
                break;
            case 4:
                $tz = static::getTimezoneNameNoLocationSpecific($value, 'long', 'generic', $locale);
                if (!isset($tz[0])) {
                    $tz = static::decodeTimezoneID($value, 4, $locale);
                }
                break;
            default:
                throw new Exception\ValueNotInList($count, array(1, 4));
        }

        return $tz;
    }

    protected static function decodeTimezoneID(\DateTime $value, $count, $locale)
    {
        switch ($count) {
            case 1:
                $result = 'unk';
                break;
            case 2:
                $result = static::getTimezoneNameFromDatetime($value);
                break;
            case 3:
                $result = static::getTimezoneExemplarCity($value, true, $locale);
                break;
            case 4:
                $result = static::getTimezoneNameLocationSpecific($value, 'short', 'generic', $locale);
                if (!isset($result[0])) {
                    $result = static::decodeTimezoneShortGMT($value, 4, $locale);
                }
                break;
            default:
                throw new Exception\ValueNotInList($count, array(1, 2, 3, 4));
        }

        return $result;
    }

    protected static function decodeTimezoneWithTime(\DateTime $value, $count, $locale, $zForZero = false)
    {
        $offset = $value->getOffset();
        $useZ = ($zForZero && ($offset === 0)) ? true : false;
        $sign = ($offset < 0) ? '-' : '+';
        $seconds = abs($offset);
        $hours = intval(floor($seconds / 3600));
        $seconds -= $hours * 3600;
        $minutes = intval(floor($seconds / 60));
        $seconds -= $minutes * 60;
        $hours2 = $sign.substr('0'.strval($hours), -2);
        $minutes2 = substr('0'.strval($minutes), -2);
        /* @TZWS
        $seconds2 = substr('0' . strval($seconds), -2);
        */
        $hmMaybe = array($hours2);
        if ($minutes > 0) {
            $hmMaybe[] = $minutes2;
        }
        $hmsMaybe = array($hours2, $minutes2);
        /* @TZWS
        if ($seconds > 0) {
            $hmsMaybe[] = $seconds2;
        }
        */
        switch ($count) {
            case 1:
                $result = $useZ ? 'Z' : implode('', $hmMaybe);
                break;
            case 2:
                $result = $useZ ? 'Z' : "$hours2$minutes2";
                break;
            case 3:
                $result = $useZ ? 'Z' : "$hours2:$minutes2";
                break;
            case 4:
                $result = $useZ ? 'Z' : implode('', $hmsMaybe);
                break;
            case 5:
                $result = $useZ ? 'Z' : implode(':', $hmsMaybe);
                break;
            default:
                throw new Exception\ValueNotInList($count, array(1, 2, 3, 4, 5));
        }

        return $result;
    }

    protected static function decodeTimezoneWithTimeZ(\DateTime $value, $count, $locale)
    {
        return static::decodeTimezoneWithTime($value, $count, $locale, true);
    }

    /** @todo */
    protected static function decodeWeekOfMonth(\DateTime $value, $count, $locale)
    {
        throw new Exception\NotImplemented(__METHOD__);
    }
    /** @todo */
    protected static function decodeYearCyclicName()
    {
        throw new Exception\NotImplemented(__METHOD__);
    }
    /** @todo */
    protected static function decodeModifiedGiulianDay()
    {
        throw new Exception\NotImplemented(__METHOD__);
    }

    protected static function getTimezonesAliases($phpTimezoneName)
    {
        $result = array($phpTimezoneName);
        switch ($phpTimezoneName) {
            case 'Africa/Asmara':
                $result[] = 'Africa/Asmera';
                break;
            case 'America/Atikokan':
                $result[] = 'America/Coral_Harbour';
                break;
            case 'Asia/Ho_Chi_Minh':
                $result[] = 'Asia/Saigon';
                break;
            case 'Asia/Kathmandu':
                $result[] = 'Asia/Katmandu';
                break;
            case 'Asia/Kolkata':
                $result[] = 'Asia/Calcutta';
                break;
            case 'Atlantic/Faroe':
                $result[] = 'Atlantic/Faeroe';
                break;
            case 'Pacific/Chuuk':
                $result[] = 'Pacific/Truk';
                break;
            case 'Pacific/Pohnpei':
                $result[] = 'Pacific/Ponape';
                break;
            case 'America/Argentina/Buenos_Aires':
                $result[] = 'America/Buenos_Aires';
                break;
            case 'America/Argentina/Catamarca':
                $result[] = 'America/Catamarca';
                break;
            case 'America/Argentina/Cordoba':
                $result[] = 'America/Cordoba';
                break;
            case 'America/Argentina/Jujuy':
                $result[] = 'America/Jujuy';
                break;
            case 'America/Argentina/Mendoza':
                $result[] = 'America/Mendoza';
                break;
            case 'America/Indiana/Indianapolis':
                $result[] = 'America/Indianapolis';
                break;
            case 'America/Kentucky/Louisville':
                $result[] = 'America/Louisville';
                break;
        }

        return $result;
    }

    protected static function decodePunicExtension(\DateTime $value, $count, $locale)
    {
        switch ($count) {
            case 1:
                return $value->format('N');
            case 2:
                return $value->format('w');
            case 3:
                return (stripos($locale, 'en') === 0) ? $value->format('S') : '';
            case 4:
                return $value->format('z');
            case 5:
                return $value->format('t');
            case 6:
                return $value->format('L');
            case 7:
                $result = self::decodeDayperiod($value, 1, $locale);
                if (stripos($locale, 'en') === 0) {
                    $result = strtolower($result);
                }

                return $result;
            case 8:
                return $value->format('B');
            case 9:
                return $value->format('u');
            case 10:
                return $value->format('I');
            case 11:
                return $value->format('Z');
            case 12:
                return $value->format('r');
            case 13:
                return $value->format('U');
            default:
                throw new Exception\ValueNotInList($count, array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13));
        }
    }

    protected static function getTimezoneNameFromDatetime(\DateTime $dt)
    {
        if (defined('\HHVM_VERSION')) {
            $result = $dt->format('e');
            if (!preg_match('/[0-9][0-9]/', $result)) {
                $result = $dt->getTimezone()->getName();
            }
        } else {
            $result = $dt->getTimezone()->getName();
        }

        return $result;
    }

    protected static function getTimezoneNameFromTimezone(\DateTimeZone $tz)
    {
        if (defined('\HHVM_VERSION')) {
            $testDT = new \DateTime('now', $tz);
            $result = $testDT->format('e');
            if (!preg_match('/[0-9][0-9]/', $result)) {
                $result = $tz->getName();
            }
        } else {
            $result = $tz->getName();
        }

        return $result;
    }
}
