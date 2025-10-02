<?php

namespace Punic;

use DateTime;
use DateTimeInterface;
use DateTimeZone;

/*
 * Comments marked as @TZWS have been added because it seems than PHP does
 * not support timezones with seconds.
 * Furthermore: the Unicode specs (https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Field_Symbol_Table) says the following:
 * "The ISO8601 basic format with hours, minutes and optional seconds fields. Note: The seconds field is not supported by the
 * ISO8601 specification."
 */

/**
 * Date and time related functions.
 */
class Calendar
{
    /**
     * Cache for getTimezoneNameNoLocationSpecific method.
     *
     * @var array
     */
    protected static $timezoneCache;

    /**
     * Map between English 3-letter weekday names and standard PHP weekday numbers.
     *
     * @var string[]
     */
    protected static $weekdayDictionary = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');

    /**
     * Fields used to calculate the greatest difference ordered by significance (from era to second).
     *
     * @var string
     */
    protected static $differenceFields = 'GyQMdaHmsS';

    /**
     * Fields representing date symbols.
     *
     * @var string
     */
    protected static $dateFields = 'GyYurUQqMLlwWEcedDFg';

    /**
     * Map between tokens and decoder functions.
     *
     * @var array
     */
    protected static $decoderFunctions = array(
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
        'b' => 'decodeDayperiod',
        'B' => 'decodeVariableDayperiod',
        'h' => 'decodeHour12',
        'H' => 'decodeHour24',
        'K' => 'decodeHour12From0',
        'k' => 'decodeHour24From1',
        'm' => 'decodeMinute',
        's' => 'decodeSecond',
        'S' => 'decodeFractionsOfSeconds',
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

    /**
     * Cache for the tokenizeFormat method.
     *
     * @var array
     */
    private static $tokenizerCache = array();

    /**
     * Convert a date/time representation to a {@link https://www.php.net/manual/class.datetime.php \DateTime} instance.
     *
     * @param number|\DateTime|\DateTimeInterface|string $value A Unix timestamp, a `\DateTimeInterface` instance or a string accepted by {@link https://www.php.net/manual/function.strtotime.php strtotime}.
     * @param string|\DateTimeZone $toTimezone the timezone to set; leave empty to use the value of $fromTimezone (if it's empty we'll use the default timezone or the timezone associated to $value if it's already a `\DateTimeInterface`)
     * @param string|\DateTimeZone $fromTimezone the original timezone of $value; leave empty to use the default timezone (or the timezone associated to $value if it's already a `\DateTimeInterface`)
     *
     * @throws \Punic\Exception\BadArgumentType throws an exception if $value is not empty and can't be converted to a `\DateTime` instance or if $toTimezone is not empty and is not valid
     *
     * @return \DateTime|null returns null if $value is empty, a `\DateTime` instance otherwise
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
                        $tzFrom = new DateTimeZone($fromTimezone);
                    } catch (\Exception $x) {
                        throw new Exception\BadArgumentType($fromTimezone, '\\DateTimeZone', $x);
                    }
                } elseif ($fromTimezone instanceof DateTimeZone) {
                    $tzFrom = $fromTimezone;
                } else {
                    throw new Exception\BadArgumentType($fromTimezone, '\\DateTimeZone');
                }
            }
            if (is_numeric($value)) {
                $result = new DateTime();
                $result->setTimestamp($value);
                if ($tzFrom !== null) {
                    $result->setTimezone($tzFrom);
                }
            } elseif ($value instanceof DateTimeInterface || $value instanceof DateTime) {
                $result = new DateTime('now', $value->getTimezone());
                $result->setTimestamp($value->getTimestamp());
                if ($tzFrom !== null) {
                    $result->setTimezone($tzFrom);
                }
            } elseif (is_string($value)) {
                try {
                    if ($tzFrom === null) {
                        $result = new DateTime($value);
                    } else {
                        $result = new DateTime($value, $tzFrom);
                    }
                } catch (\Exception $x) {
                    throw new Exception\BadArgumentType($value, '\\DateTime', $x);
                }
            } else {
                throw new Exception\BadArgumentType($value, '\\DateTime');
            }
            if ($result) {
                if (!empty($toTimezone)) {
                    if (is_string($toTimezone)) {
                        try {
                            $result->setTimezone(new DateTimeZone($toTimezone));
                        } catch (\Exception $x) {
                            throw new Exception\BadArgumentType($toTimezone, '\\DateTimeZone', $x);
                        }
                    } elseif ($toTimezone instanceof DateTimeZone) {
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
     * Converts a format string from {@link https://www.php.net/manual/function.date.php#refsect1-function.date-parameters PHP's date format} to {@link https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Field_Symbol_Table ISO format}.
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
     * @param string $format the PHP date/time format string to convert
     *
     * @return string returns the ISO date/time format corresponding to the specified PHP date/time format
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
            'b' => 'PPPPPPP',
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
            $cache[$format] = implode('', $converted);
        }

        return $cache[$format];
    }

    /**
     * Try to convert a date, time or date/time {@link https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Field_Symbol_Table ISO format string} to a {@link https://www.php.net/manual/function.date.php#refsect1-function.date-parameters PHP date/time format}.
     *
     * @param string $isoDateTimeFormat The PHP date/time format
     *
     * @return string|null if the format is not possible (the ISO placeholders are much more than the PHP ones), null will be returned
     */
    public static function tryConvertIsoToPhpFormat($isoDateTimeFormat)
    {
        $result = null;
        if (is_string($isoDateTimeFormat)) {
            $result = '';
            if ($isoDateTimeFormat !== '') {
                $tokens = self::tokenizeFormat($isoDateTimeFormat);
                foreach ($tokens as $token) {
                    $chunk = null;
                    if (is_string($token)) {
                        $chunk = preg_replace('/([a-zA-Z\\\\])/', '\\\\\\1', $token);
                    } else {
                        switch ($token[0]) {
                            case 'decodeYear':
                                switch ($token[1]) {
                                    case 2:
                                        $chunk .= 'y';
                                        break;
                                    default:
                                        $chunk .= 'Y';
                                        break;
                                }
                                break;
                            case 'decodeYearWeekOfYear':
                                $chunk = 'o';
                                break;
                            case 'decodeYearExtended':
                            case 'decodeYearRelatedGregorian':
                                $chunk = 'Y';
                                break;
                            case 'decodeMonth':
                            case 'decodeMonthAlone':
                                switch ($token[1]) {
                                    case 1:
                                        $chunk = 'n';
                                        break;
                                    case 2:
                                        $chunk = 'm';
                                        break;
                                    case 3:
                                        $chunk = 'M';
                                        break;
                                    case 4:
                                        $chunk = 'F';
                                        break;
                                }
                                break;
                            case 'decodeWeekOfYear':
                                switch ($token[1]) {
                                    case 1:
                                    case 2:
                                        $chunk = 'W';
                                        break;
                                }
                                break;
                            case 'decodeDayOfMonth':
                                switch ($token[1]) {
                                    case 1:
                                        $chunk = 'j';
                                        break;
                                    case 2:
                                        $chunk = 'd';
                                        break;
                                }
                                break;
                            case 'decodeDayOfYear':
                                switch ($token[1]) {
                                    case 1:
                                    case 2:
                                    case 3:
                                        $chunk = 'z';
                                        break;
                                }
                                break;
                            case 'decodeDayOfWeek':
                                switch ($token[1]) {
                                    case 1:
                                    case 2:
                                    case 3:
                                        $chunk = 'D';
                                        break;
                                    case 4:
                                        $chunk = 'l';
                                        break;
                                }
                                break;
                            case 'decodeDayOfWeekLocal':
                            case 'decodeDayOfWeekLocalAlone':
                                switch ($token[1]) {
                                    case 1:
                                    case 2:
                                        $chunk = 'N';
                                        break;
                                    case 3:
                                        $chunk = 'D';
                                        break;
                                    case 4:
                                        $chunk = 'l';
                                        break;
                                }
                                break;
                            case 'decodeDayperiod':
                            case 'decodeVariableDayperiod':
                                if ($token[1] <= 4) {
                                    $chunk = 'A';
                                }
                                break;
                            case 'decodeHour12':
                                switch ($token[1]) {
                                    case 1:
                                        $chunk = 'g';
                                        break;
                                    case 2:
                                        $chunk = 'h';
                                        break;
                                }
                                break;
                            case 'decodeHour24':
                                switch ($token[1]) {
                                    case 1:
                                        $chunk = 'G';
                                        break;
                                    case 2:
                                        $chunk = 'H';
                                        break;
                                }
                                break;
                            case 'decodeMinute':
                                switch ($token[1]) {
                                    case 1:
                                    case 2:
                                        $chunk = 'i';
                                        break;
                                }
                                break;
                            case 'decodeSecond':
                                switch ($token[1]) {
                                    case 1:
                                    case 2:
                                        $chunk = 's';
                                        break;
                                }
                                break;
                            case 'decodeFractionsOfSeconds':
                                switch ($token[1]) {
                                    case 3:
                                        if (version_compare(PHP_VERSION, '7') >= 0) {
                                            $chunk = 'v';
                                        }
                                        break;
                                    case 6:
                                        $chunk = 'u';
                                        break;
                                }
                                break;
                            case 'decodeTimezoneNoLocationSpecific':
                                switch ($token[1]) {
                                    case 1:
                                    case 2:
                                    case 3:
                                        $chunk = 'T';
                                        break;
                                    case 4:
                                        $chunk = '\\G\\M\\TP';
                                        break;
                                }
                                break;
                            case 'decodeTimezoneDelta':
                                switch ($token[1]) {
                                    case 1:
                                    case 2:
                                    case 3:
                                        $chunk = 'O';
                                        break;
                                    case 4:
                                        $chunk = '\\G\\M\\TP';
                                        break;
                                    case 5:
                                        $chunk = 'P';
                                        break;
                                }
                                break;
                            case 'decodeTimezoneShortGMT':
                                switch ($token[1]) {
                                    case 1:
                                    case 4:
                                        $chunk = '\\G\\M\\TP';
                                        break;
                                }
                                break;
                            case 'decodeTimezoneID':
                                switch ($token[1]) {
                                    case 2:
                                        $chunk = 'e';
                                        break;
                                }
                                break;
                            case 'decodeTimezoneWithTimeZ':
                            case 'decodeTimezoneWithTime':
                                switch ($token[1]) {
                                    case 1:
                                    case 2:
                                    case 4:
                                        $chunk = 'O';
                                        break;
                                    case 3:
                                    case 5:
                                        $chunk = 'P';
                                        break;
                                }
                                break;
                            case 'decodePunicExtension':
                                switch ($token[1]) {
                                    case 1:
                                        $chunk = 'N';
                                        break;
                                    case 2:
                                        $chunk = 'w';
                                        break;
                                    case 3:
                                        $chunk = 'S';
                                        break;
                                    case 4:
                                        $chunk = 'z';
                                        break;
                                    case 5:
                                        $chunk = 't';
                                        break;
                                    case 6:
                                        $chunk = 'L';
                                        break;
                                    case 7:
                                        $chunk = 'a';
                                        break;
                                    case 8:
                                        $chunk = 'B';
                                        break;
                                    case 9:
                                        $chunk = 'u';
                                        break;
                                    case 10:
                                        $chunk = 'I';
                                        break;
                                    case 11:
                                        $chunk = 'Z';
                                        break;
                                    case 12:
                                        $chunk = 'r';
                                        break;
                                    case 13:
                                        $chunk = 'U';
                                        break;
                                }
                                break;
                        }
                    }
                    if ($chunk === null) {
                        $result = null;
                        break;
                    }
                    $result .= $chunk;
                }
            }
        }

        return $result;
    }

    /**
     * Get the name of an era.
     *
     * @param number|\DateTime|\DateTimeInterface $value the year number or the \DateTimeInterface instance for which you want the name of the era
     * @param string $width the format name; it can be 'wide' (eg 'Before Christ'), 'abbreviated' (eg 'BC') or 'narrow' (eg 'B')
     * @param string $locale The locale to use. If empty we'll use the default locale set with {@link \Punic\Data::setDefaultLocale()}.
     *
     * @throws \Punic\Exception\BadArgumentType throws a BadArgumentType exception if $value is not valid
     * @throws \Punic\Exception\ValueNotInList throws a ValueNotInList exception if $width is not valid
     * @throws \Punic\Exception throws a generic exception in case of other problems (for instance if you specify an invalid locale)
     *
     * @return string returns an empty string if $value is empty, the name of the era otherwise
     */
    public static function getEraName($value, $width = 'abbreviated', $locale = '')
    {
        $result = '';
        if ((!empty($value)) || ($value === 0) || ($value === '0')) {
            $year = null;
            if (is_numeric($value)) {
                $year = (int) $value;
            } elseif ($value instanceof DateTimeInterface || $value instanceof DateTime) {
                $year = (int) $value->format('Y');
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
     * @param number|\DateTime|\DateTimeInterface $value the month number (1-12) or a \DateTimeInterface instance for which you want the name of the month
     * @param string $width the format name; it can be 'wide' (eg 'January'), 'abbreviated' (eg 'Jan') or 'narrow' (eg 'J')
     * @param string $locale The locale to use. If empty we'll use the default locale set with {@link \Punic\Data::setDefaultLocale()}.
     * @param bool $standAlone set to true to return the form used independently (such as in calendar header), set to false if the month name will be part of a date
     *
     * @throws \Punic\Exception\BadArgumentType throws a BadArgumentType exception if $value is not valid
     * @throws \Punic\Exception\ValueNotInList throws a ValueNotInList exception if $width is not valid
     * @throws \Punic\Exception throws a generic exception in case of other problems (for instance if you specify an invalid locale)
     *
     * @return string returns an empty string if $value is empty, the name of the month otherwise
     */
    public static function getMonthName($value, $width = 'wide', $locale = '', $standAlone = false)
    {
        $result = '';
        if ((!empty($value)) || ($value === 0) || ($value === '0')) {
            $month = null;
            if (is_numeric($value)) {
                $month = (int) $value;
            } elseif ($value instanceof DateTimeInterface || $value instanceof DateTime) {
                $month = (int) $value->format('n');
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
     * @param number|\DateTime|\DateTimeInterface $value a week day number (from 0-Sunday to 6-Saturday) or a \DateTimeInterface instance for which you want the name of the day of the week
     * @param string $width the format name; it can be 'wide' (eg 'Sunday'), 'abbreviated' (eg 'Sun'), 'short' (eg 'Su') or 'narrow' (eg 'S')
     * @param string $locale The locale to use. If empty we'll use the default locale set with {@link \Punic\Data::setDefaultLocale()}.
     * @param bool $standAlone set to true to return the form used independently (such as in calendar header), set to false if the week day name will be part of a date
     *
     * @throws \Punic\Exception\BadArgumentType throws a BadArgumentType exception if $value is not valid
     * @throws \Punic\Exception\ValueNotInList throws a ValueNotInList exception if $width is not valid
     * @throws \Punic\Exception throws a generic exception in case of other problems (for instance if you specify an invalid locale)
     *
     * @return string returns an empty string if $value is empty, the name of the week day name otherwise
     */
    public static function getWeekdayName($value, $width = 'wide', $locale = '', $standAlone = false)
    {
        $result = '';
        if ((!empty($value)) || ($value === 0) || ($value === '0')) {
            $weekday = null;
            if (is_numeric($value)) {
                $weekday = (int) $value;
            } elseif ($value instanceof DateTimeInterface || $value instanceof DateTime) {
                $weekday = (int) $value->format('w');
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
     * @param number|\DateTime|\DateTimeInterface $value a quarter number (from 1 to 4) or a \DateTimeInterface instance for which you want the name of the day of the quarter
     * @param string $width the format name; it can be 'wide' (eg '1st quarter'), 'abbreviated' (eg 'Q1') or 'narrow' (eg '1')
     * @param string $locale The locale to use. If empty we'll use the default locale set with {@link \Punic\Data::setDefaultLocale()}.
     * @param bool $standAlone set to true to return the form used independently (such as in calendar header), set to false if the quarter name will be part of a date
     *
     * @throws \Punic\Exception\BadArgumentType throws a BadArgumentType exception if $value is not valid
     * @throws \Punic\Exception\ValueNotInList throws a ValueNotInList exception if $width is not valid
     * @throws \Punic\Exception throws a generic exception in case of other problems (for instance if you specify an invalid locale)
     *
     * @return string returns an empty string if $value is empty, the name of the quarter name otherwise
     */
    public static function getQuarterName($value, $width = 'wide', $locale = '', $standAlone = false)
    {
        $result = '';
        if ((!empty($value)) || ($value === 0) || ($value === '0')) {
            $quarter = null;
            if (is_numeric($value)) {
                $quarter = (int) $value;
            } elseif ($value instanceof DateTimeInterface || $value instanceof DateTime) {
                $quarter = 1 + (int) floor(((int) $value->format('n') - 1) / 3);
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
     * @param number|string|\DateTime|\DateTimeInterface $value an hour (from 0 to 23), a standard period name ('am' or 'pm', lower or upper case) a \DateTimeInterface instance for which you want the name of the day period
     * @param string $width the format name; it can be 'wide' (eg 'AM'), 'abbreviated' (eg 'AM') or 'narrow' (eg 'a')
     * @param string $locale The locale to use. If empty we'll use the default locale set with {@link \Punic\Data::setDefaultLocale()}.
     * @param bool $standAlone set to true to return the form used independently (such as in calendar header), set to false if the day period name will be part of a date
     *
     * @throws \Punic\Exception\BadArgumentType throws a BadArgumentType exception if $value is not valid
     * @throws \Punic\Exception\ValueNotInList throws a ValueNotInList exception if $width is not valid
     * @throws \Punic\Exception throws a generic exception in case of other problems (for instance if you specify an invalid locale)
     *
     * @return string returns an empty string if $value is empty, the name of the day period name otherwise
     */
    public static function getDayperiodName($value, $width = 'wide', $locale = '', $standAlone = false)
    {
        static $dictionary = array('am', 'pm');
        $result = '';
        if ((!empty($value)) || ($value === 0) || ($value === '0')) {
            $dayperiod = null;
            $hours = null;
            if (is_numeric($value)) {
                $hours = (int) $value;
            } elseif (is_string($value)) {
                $s = strtolower($value);
                if (in_array($s, $dictionary, true)) {
                    $dayperiod = $s;
                }
            } elseif ($value instanceof DateTimeInterface || $value instanceof DateTime) {
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
     * Get the name of a variable day period ("morning", "afternoon", etc.).
     *
     * The available periods, their start/end time and their names are locale-specific.
     *
     * @param number|string|\DateTime|\DateTimeInterface $value an hour (from 0 to 23), a \DateTimeInterface instance for which you want the name of the day period
     * @param string $width the format name; it can be 'wide', 'abbreviated' or 'narrow'
     * @param string $locale The locale to use. If empty we'll use the default locale set with {@link \Punic\Data::setDefaultLocale()}.
     * @param bool $standAlone set to true to return the form used independently (e.g. "morning"), set to false if the day period name will be part of a date (e.g. "in the morning")
     *
     * @throws \Punic\Exception\BadArgumentType throws a BadArgumentType exception if $value is not valid
     * @throws \Punic\Exception\ValueNotInList throws a ValueNotInList exception if $width is not valid
     * @throws \Punic\Exception throws a generic exception in case of other problems (for instance if you specify an invalid locale)
     *
     * @return string returns an empty string if $value is empty, the name of the day period name otherwise
     *
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#Variable_periods
     */
    public static function getVariableDayperiodName($value, $width = 'wide', $locale = '', $standAlone = false)
    {
        $result = '';
        if ((!empty($value)) || ($value === 0) || ($value === '0')) {
            $data = Data::get('calendar', $locale);
            $data = $data['dayPeriods'][$standAlone ? 'stand-alone' : 'format'];
            if (!isset($data[$width])) {
                throw new Exception\ValueNotInList($width, array_keys($data));
            }
            $data = $data[$width];

            $hours = null;
            $dayperiod = null;
            if (is_numeric($value)) {
                $hours = (int) $value;
            } elseif ($value instanceof DateTimeInterface || $value instanceof DateTime) {
                $hours = (int) $value->format('G');
            }

            if (($hours !== null) && ($hours >= 0) && ($hours <= 23)) {
                $dayperiods = Data::getLanguageNode(Data::getGeneric('dayPeriods'), $locale);
                $time = sprintf('%02d:00', $hours);
                foreach ($dayperiods as $dayperiod => $rule) {
                    if ($time < $rule['before']) {
                        break;
                    }
                }
            }
            if ($dayperiod === null) {
                throw new Exception\BadArgumentType($value, 'day period');
            }

            $result = $data[$dayperiod];
        }

        return $result;
    }

    /**
     * Returns the localized name of a timezone, no location-specific.
     *
     * @param string|\DateTime|\DateTimeInterface|\DateTimeZone $value the PHP name of a timezone, a `\DateTimeInterface` instance or a `\DateTimeZone` instance for which you want the localized timezone name
     * @param string $width the format name; it can be 'long' (eg 'Greenwich Mean Time') or 'short' (eg 'GMT')
     * @param string $kind set to 'daylight' to retrieve the daylight saving time name, set to 'standard' to retrieve the standard time, set to 'generic' to retrieve the generic name, set to '' to determine automatically the dst (if $value is \DateTime) or the generic (otherwise)
     * @param string $locale The locale to use. If empty we'll use the default locale set with {@link \Punic\Data::setDefaultLocale()}.
     *
     * @throws \Punic\Exception throws a generic exception in case of problems (for instance if you specify an invalid locale)
     *
     * @return string returns an empty string if the timezone has not been found (maybe we don't have the data in the specified $width), the timezone name otherwise
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
            $date = '9999-12-31';
            if (is_string($value)) {
                $receivedPhpName = $value;
            } elseif ($value instanceof DateTimeInterface || $value instanceof DateTime) {
                $receivedPhpName = static::getTimezoneNameFromDatetime($value);
                $date = $value->format('Y-m-d H:i');
                if (empty($kind)) {
                    if ((int) $value->format('I') === 1) {
                        $kind = 'daylight';
                    } else {
                        $kind = 'standard';
                    }
                }
            } elseif ($value instanceof DateTimeZone) {
                $receivedPhpName = static::getTimezoneNameFromTimezone($value);
            }
            if ($receivedPhpName !== '') {
                $timezoneID = static::getTimezoneCanonicalID($receivedPhpName);
                $timeZoneNames = Data::get('timeZoneNames', $locale);
                $path = array_merge(array('zone'), explode('/', $timezoneID), array($width, array($kind, 'generic', 'standard')));
                $name = Data::getArrayValue($timeZoneNames, $path);
                if ($name !== null) {
                    $result = $name;
                } else {
                    $metaZones = Data::getGeneric('metaZones');
                    $metazoneCode = '';
                    $path = array_merge(array('metazoneInfo'), explode('/', $timezoneID));
                    $tzInfo = Data::getArrayValue($metaZones, $path);
                    if (is_array($tzInfo)) {
                        foreach ($tzInfo as $tz) {
                            if (is_array($tz) && isset($tz['mzone'])) {
                                if (isset($tz['from']) && (strcmp($date, $tz['from']) < 0)) {
                                    continue;
                                }
                                if (isset($tz['to']) && (strcmp($date, $tz['to']) >= 0)) {
                                    continue;
                                }
                                $metazoneCode = $tz['mzone'];
                                break;
                            }
                        }
                    }
                    if ($metazoneCode === '') {
                        foreach ($metaZones['metazones'] as $metazone) {
                            if (strcasecmp($timezoneID, $metazone['type']) === 0) {
                                $metazoneCode = $metazone['other'];
                            }
                        }
                    }
                    if ($metazoneCode !== '') {
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
        }

        self::$timezoneCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Returns the localized name of a timezone, location-specific.
     *
     * @param string|\DateTime|\DateTimeInterface|\DateTimeZone $value the php name of a timezone, or a \DateTime instance or a \DateTimeZone instance for which you want the localized timezone name
     * @param string $locale The locale to use. If empty we'll use the default locale set with {@link \Punic\Data::setDefaultLocale()}.
     *
     * @return string returns an empty string if the timezone has not been found, the timezone name otherwise
     *
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#Time_Zone_Goals
     */
    public static function getTimezoneNameLocationSpecific($value, $locale = '')
    {
        $result = '';
        if (!empty($value)) {
            if (is_string($value)) {
                $timezoneID = static::getTimezoneCanonicalID($value);
                $timezone = null;
                try {
                    $timezone = new DateTimeZone($timezoneID);
                } catch (\Exception $x) {
                    return '';
                }
                $location = $timezone->getLocation();
            } elseif ($value instanceof DateTimeInterface || $value instanceof DateTime) {
                $timezone = $value->getTimezone();
                $location = self::getTimezoneLocationFromDatetime($value);
            } elseif ($value instanceof DateTimeZone) {
                $timezone = $value;
                $location = $timezone->getLocation();
            } else {
                throw new Exception\BadArgumentType($value, 'string, DateTime, or DateTimeZone');
            }

            $name = '';
            if (isset($location['country_code']) && $location['country_code'] !== '??') {
                $data = Data::getGeneric('primaryZones');
                if (isset($data[$location['country_code']])
                    || count(DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $location['country_code'])) === 1) {
                    $name = Territory::getName($location['country_code'], $locale);
                }
            }

            if ($name === '' && substr($timezone->getName(), 0, 7) !== 'Etc/GMT') {
                $name = static::getTimezoneExemplarCity($value, false, $locale);
            }

            if ($name !== '') {
                $data = Data::get('timeZoneNames', $locale);
                $result = sprintf($data['regionFormat'], $name);
            }
        }

        return $result;
    }

    /**
     * Returns the localized name of an exemplar city for a specific timezone.
     *
     * @param string|\DateTime|\DateTimeInterface|\DateTimeZone $value The PHP name of a timezone, a `\DateTimeInterface` instance or a `\DateTimeZone` instance
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
            if (is_string($value)) {
                $receivedPhpName = $value;
            } elseif ($value instanceof DateTimeInterface || $value instanceof DateTime) {
                $receivedPhpName = static::getTimezoneNameFromDatetime($value);
            } elseif ($value instanceof DateTimeZone) {
                $receivedPhpName = static::getTimezoneNameFromTimezone($value);
            } else {
                $receivedPhpName = '';
            }
            if ($receivedPhpName !== '') {
                $timezoneID = static::getTimezoneCanonicalID($receivedPhpName);
                $timeZoneNames = Data::get('timeZoneNames', $locale);
                $path = array_merge(array('zone'), explode('/', $timezoneID), array('exemplarCity'));
                $exemplarCity = Data::getArrayValue($timeZoneNames, $path);
                if ($exemplarCity !== null) {
                    $result = $exemplarCity;
                }
            }
        }
        if ($result === '' && $returnUnknownIfNotFound) {
            $result = 'Unknown City';
            $s = static::getTimezoneExemplarCity('Etc/Unknown', false, $locale);
            if ($s !== '') {
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
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @return bool
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
     * Retrieve the first weekday for a specific locale (from 0-Sunday to 6-Saturday).
     *
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return int Returns a number from 0 (Sunday) to 7 (Saturday)
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
     *                                 If it's a string it must be one accepted by {@link getWeekdayName}, and you'll get an array like this: [{id: 0, name: 'Monday', ..., {id: 6, name: 'Sunday'}]
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @return array
     */
    public static function getSortedWeekdays($namesWidth = false, $locale = '')
    {
        $codes = array();
        $code = static::getFirstWeekday($locale);
        for ($count = 0; $count < 7; $count++) {
            $codes[] = $code;
            $code++;
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
     * @param string $width The format name; it can be 'full' (eg 'EEEE, MMMM d, y' - 'Wednesday, August 20, 2014'), 'long' (eg 'MMMM d, y' - 'August 20, 2014'), 'medium' (eg 'MMM d, y' - 'August 20, 2014') or 'short' (eg 'M/d/yy' - '8/20/14'),
     *                      or a skeleton pattern prefixed by '~', e.g. '~yMd'.
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws Exception Throws an exception in case of problems
     *
     * @return string Returns the requested ISO format
     *
     * @see https://cldr.unicode.org/translation/date-time/datetime-patterns
     * @see https://cldr.unicode.org/translation/date-time
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
     */
    public static function getDateFormat($width, $locale = '')
    {
        if ($width !== '' && $width[0] === '~') {
            return self::getSkeletonFormat(substr($width, 1), $locale);
        }

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
     * @param string $width The format name; it can be 'full' (eg 'h:mm:ss a zzzz' - '11:42:13 AM GMT+2:00'), 'long' (eg 'h:mm:ss a z' - '11:42:13 AM GMT+2:00'), 'medium' (eg 'h:mm:ss a' - '11:42:13 AM') or 'short' (eg 'h:mm a' - '11:42 AM'),
     *                      or a skeleton pattern prefixed by '~', e.g. '~Hm'.
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @return string Returns the requested ISO format
     *
     * @see https://cldr.unicode.org/translation/date-time/datetime-patterns
     * @see https://cldr.unicode.org/translation/date-time
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
     */
    public static function getTimeFormat($width, $locale = '')
    {
        if ($width !== '' && $width[0] === '~') {
            return self::getSkeletonFormat(substr($width, 1), $locale);
        }

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
     * @param string $width The format name; it can be 'full', 'long', 'medium', 'short' or a combination for date+time like 'full|short' or a combination for format+date+time like 'full|full|short',
     *                      or a skeleton pattern prefixed by '~', e.g. '~yMd'.
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @return string Returns the requested ISO format
     *
     * @see https://cldr.unicode.org/translation/date-time/datetime-patterns
     * @see https://cldr.unicode.org/translation/date-time
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
     */
    public static function getDatetimeFormat($width, $locale = '')
    {
        return static::getDatetimeFormatReal($width, $locale);
    }

    /**
     * Get the ISO format based on a skeleton.
     *
     * @param string $skeleton The locale-independent skeleton, e.g. "yMMMd" or "Hm".
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @return string Returns the requested ISO format
     *
     * @see https://cldr.unicode.org/translation/date-time/datetime-patterns#h.j31ghafvbgku
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#availableFormats_appendItems
     */
    public static function getSkeletonFormat($skeleton, $locale = '')
    {
        static $cache = array();
        if (empty($locale)) {
            $locale = Data::getDefaultLocale();
        }
        if (isset($cache[$locale][$skeleton])) {
            return $cache[$locale][$skeleton];
        }
        $data = Data::get('calendar', $locale);
        $data = $data['dateTimeFormats']['availableFormats'];
        if (isset($data[$skeleton])) {
            $format = $data[$skeleton];
        } else {
            list($preprocessedSkeleton, $replacements) = self::preprocessSkeleton($skeleton, $locale);

            $match = self::getBestMatchingSkeleton($preprocessedSkeleton, array_keys($data));

            if (!$match) {
                // If skeleton contains both date and time fields, try matching date and time separately.
                $dateLength = strspn($preprocessedSkeleton, 'GyYurUQqMLlwWEcedDFg');
                if ($dateLength > 0 && $dateLength < strlen($preprocessedSkeleton)) {
                    $dateSkeleton = substr($preprocessedSkeleton, 0, $dateLength);
                    $timeSkeleton = substr($preprocessedSkeleton, $dateLength);

                    return self::getDatetimeFormat('~' . $dateSkeleton . '|~' . $timeSkeleton, $locale);
                }

                throw new Exception('Matching skeleton not found: ' . $skeleton);
            }

            list($matchSkeleton, $countAdjustments) = $match;

            $format = self::postprocessSkeletonFormat($data[$matchSkeleton], $countAdjustments, $replacements, $locale);
        }

        $cache[$locale][$skeleton] = $format;

        return $format;
    }

    /**
     * Get the ISO format for a date/time interval.
     *
     * @param string $skeleton The locale-independent skeleton, e.g. "yMMMd" or "Hm".
     * @param string $greatestDifference The calendar field with the greatest distance between the two dates. Must be one of the fields mentioned in $differenceFields.
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data.
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @return array an array with two entries:
     *               - string: The ISO interval format
     *               - bool|null: Whether the earliest date is the first of the two dates in the pattern,
     *               or null if the dates are identical within the granularity specified by the skeleton
     *
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#intervalFormats
     */
    public static function getIntervalFormat($skeleton, $greatestDifference, $locale = '')
    {
        static $cache = array();
        if (empty($locale)) {
            $locale = Data::getDefaultLocale();
        }
        if (isset($cache[$locale][$skeleton][$greatestDifference])) {
            return $cache[$locale][$skeleton][$greatestDifference];
        }

        $data = Data::get('calendar', $locale);
        $data = $data['dateTimeFormats']['intervalFormats'];

        if (isset($data[$skeleton])) {
            $preprocessedSkeleton = $skeleton;
            $replacements = array();
            $match = array($skeleton, 0);
        } else {
            list($preprocessedSkeleton, $replacements) = self::preprocessSkeleton($skeleton, $locale);

            $match = self::getBestMatchingSkeleton($preprocessedSkeleton, array_keys($data));
        }

        if ($match) {
            list($matchSkeleton, $sWidth) = $match;
        }

        // The spec does not unambiguously define "greatest difference".
        $adjustedGreatestDifference = self::adjustGreatestDifference($greatestDifference, $preprocessedSkeleton);

        if ($adjustedGreatestDifference === '') {
            // Greatest difference is less than skeleton granularity, so we display the
            // interval as a single date.
            return array(self::getSkeletonFormat($skeleton, $locale), null);
        }

        $earliestFirst = null;
        if ($match && isset($data[$matchSkeleton][$adjustedGreatestDifference])) {
            $format = $data[$matchSkeleton][$adjustedGreatestDifference];

            if (substr($format, 0, 12) === 'latestFirst:') {
                $format = substr($format, 12);
                $earliestFirst = false;
            } elseif (substr($format, 0, 14) === 'earliestFirst:') {
                $format = substr($format, 14);
                $earliestFirst = true;
            }

            $format = self::postprocessSkeletonFormat($format, $sWidth, $replacements, $locale);
        } else {
            // Either there was no matching skeleton, or there was no pattern matching
            // the specific greatest difference, so format using the fallback format.
            // If skeleton contains both date and time fields, and the difference is les
            // than a day, format using "date - date time", otherwise use "date time -
            // date time". This is not mandated by UTS #35, but ICU4J does this.
            $dateLength = strspn($preprocessedSkeleton, self::$dateFields);
            if ($dateLength > 0 && $dateLength < strlen($preprocessedSkeleton) && strspn($adjustedGreatestDifference, self::$dateFields) === 0) {
                $timeSkeleton = substr($preprocessedSkeleton, $dateLength);

                $wholeFormat = self::getSkeletonFormat($preprocessedSkeleton, $locale);
                $timeFormat = self::getSkeletonFormat($timeSkeleton, $locale);

                $format = sprintf($data['intervalFormatFallback'], $wholeFormat, $timeFormat);
            } else {
                $wholeFormat = self::getSkeletonFormat($preprocessedSkeleton, $locale);
                $format = sprintf($data['intervalFormatFallback'], $wholeFormat, $wholeFormat);
            }
        }

        // If pattern does not declare an order, use the order define by the fallback pattern.
        if ($earliestFirst === null) {
            $earliestFirst = strpos($data['intervalFormatFallback'], '%1') < strpos($data['intervalFormatFallback'], '%2');
        }

        $result = array($format, $earliestFirst);

        $cache[$locale][$skeleton][$greatestDifference] = $result;

        return $result;
    }

    /**
     * Returns the difference in days between two dates (or between a date and today).
     *
     * @param \DateTime|\DateTimeInterface $dateEnd The first date
     * @param \DateTime|\DateTimeInterface|null $dateStart The final date (if it has a timezone different than $dateEnd, we'll use the one of $dateEnd)
     *
     * @throws Exception\BadArgumentType
     *
     * @return int Returns the difference $dateEnd - $dateStart in days
     */
    public static function getDeltaDays($dateEnd, $dateStart = null)
    {
        if (!($dateEnd instanceof DateTimeInterface || $dateEnd instanceof DateTime)) {
            throw new Exception\BadArgumentType($dateEnd, '\\DateTime');
        }
        if (empty($dateStart) && ($dateStart !== 0) && ($dateStart !== '0')) {
            $dateStart = new DateTime('now', $dateEnd->getTimezone());
        }
        if (!($dateStart instanceof DateTimeInterface || $dateStart instanceof DateTime)) {
            throw new Exception\BadArgumentType($dateStart, '\\DateTime');
        }
        if ($dateStart->getOffset() !== $dateEnd->getOffset()) {
            $dateStart = new DateTime('@' . $dateStart->getTimestamp());
            $dateStart->setTimezone($dateEnd->getTimezone());
        }
        $utc = new DateTimeZone('UTC');
        $dateEndUTC = new DateTime($dateEnd->format('Y-m-d'), $utc);
        $dateStartUTC = new DateTime($dateStart->format('Y-m-d'), $utc);
        $seconds = $dateEndUTC->getTimestamp() - $dateStartUTC->getTimestamp();

        return (int) (round($seconds / 86400));
    }

    /**
     * Describe an interval between two dates (eg '2 days and 4 hours').
     *
     * @param \DateTime|\DateTimeInterface $dateEnd The first date
     * @param \DateTime|\DateTimeInterface|null $dateStart The final date (if it has a timezone different than $dateEnd, we'll use the one of $dateEnd)
     * @param int $maxParts The maximum parts (eg with 2 you may have '2 days and 4 hours', with 3 '2 days, 4 hours and 24 minutes')
     * @param string $width The format name; it can be 'long' (eg '3 seconds'), 'short' (eg '3 s') or 'narrow' (eg '3s')
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws Exception\BadArgumentType
     *
     * @return string
     */
    public static function describeInterval($dateEnd, $dateStart = null, $maxParts = 2, $width = 'short', $locale = '')
    {
        if (!($dateEnd instanceof DateTimeInterface || $dateEnd instanceof DateTime)) {
            throw new Exception\BadArgumentType($dateEnd, '\\DateTime');
        }
        if (empty($dateStart) && ($dateStart !== 0) && ($dateStart !== '0')) {
            $dateStart = new DateTime('now', $dateEnd->getTimezone());
        }
        if (!($dateStart instanceof DateTimeInterface || $dateStart instanceof DateTime)) {
            throw new Exception\BadArgumentType($dateStart, '\\DateTime');
        }
        if ($dateStart->getOffset() !== $dateEnd->getOffset()) {
            $dateStart = new DateTime('@' . $dateStart->getTimestamp());
            $dateStart->setTimezone($dateEnd->getTimezone());
        }
        $utc = new DateTimeZone('UTC');
        $dateEndUTC = new DateTime($dateEnd->format('Y-m-d H:i:s'), $utc);
        $dateStartUTC = new DateTime($dateStart->format('Y-m-d H:i:s'), $utc);

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
                $mostFar++;
            }
            if (($mostFar < $maxDistance) && ($diff->m > 0)) {
                $parts[] = Unit::format($diff->m, 'duration/month', $width, $locale);
                $mostFar = 0;
            } elseif (!empty($parts)) {
                $mostFar++;
            }
            if (($mostFar < $maxDistance) && ($diff->d > 0)) {
                $parts[] = Unit::format($diff->d, 'duration/day', $width, $locale);
                $mostFar = 0;
            } elseif (!empty($parts)) {
                $mostFar++;
            }
            if (($mostFar < $maxDistance) && ($diff->h > 0)) {
                $parts[] = Unit::format($diff->h, 'duration/hour', $width, $locale);
                $mostFar = 0;
            } elseif (!empty($parts)) {
                $mostFar++;
            }
            if (($mostFar < $maxDistance) && ($diff->i > 0)) {
                $parts[] = Unit::format($diff->i, 'duration/minute', $width, $locale);
                $mostFar = 0;
            } elseif (!empty($parts)) {
                $mostFar++;
            }
            if (empty($parts) || ($diff->s > 0)) {
                $parts[] = Unit::format($diff->s, 'duration/second', $width, $locale);
            }
            if ($maxParts < count($parts)) {
                $parts = array_slice($parts, 0, $maxParts);
            }
        }
        switch ($width) {
            case 'narrow':
            case 'short':
                $joined = Misc::joinUnits($parts, $width, $locale);
                break;
            default:
                $joined = Misc::joinAnd($parts, '', $locale);
                break;
        }

        return $joined;
    }

    /**
     * Format a date.
     *
     * @param \DateTime|\DateTimeInterface $value The \DateTimeInterface instance for which you want the localized textual representation
     * @param string $width The format name; it can be 'full' (eg 'EEEE, MMMM d, y' - 'Wednesday, August 20, 2014'), 'long' (eg 'MMMM d, y' - 'August 20, 2014'), 'medium' (eg 'MMM d, y' - 'August 20, 2014') or 'short' (eg 'M/d/yy' - '8/20/14'),
     *                      or a skeleton pattern prefixed by '~', e.g. '~yMd'.
     *                      You can also append a caret ('^') or an asterisk ('*') to $width. If so, special day names may be used (like 'Today', 'Yesterday', 'Tomorrow' with '^' and 'today', 'yesterday', 'tomorrow' width '*') instead of the date.
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @return string Returns an empty string if $value is empty, the localized textual representation otherwise
     *
     * @see https://cldr.unicode.org/translation/date-time/datetime-patterns
     * @see https://cldr.unicode.org/translation/date-time
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
     */
    public static function formatDate($value, $width, $locale = '')
    {
        $c = is_string($width) ? @substr($width, -1) : '';
        if (($c === '^') || ($c === '*')) {
            $dayName = static::getDateRelativeName($value, ($c === '^') ? true : false, $locale);
            if ($dayName !== '') {
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
     * @param number|\DateTime|\DateTimeInterface|string $value A Unix timestamp, a `\DateTimeInterface` instance or a string accepted by {@link https://www.php.net/manual/function.strtotime.php strtotime}.
     * @param string $width The format name; it can be 'full' (eg 'EEEE, MMMM d, y' - 'Wednesday, August 20, 2014'), 'long' (eg 'MMMM d, y' - 'August 20, 2014'), 'medium' (eg 'MMM d, y' - 'August 20, 2014') or 'short' (eg 'M/d/yy' - '8/20/14'),
     *                      or a skeleton pattern prefixed by '~', e.g. '~yMd'.
     *                      You can also append a caret ('^') or an asterisk ('*') to $width. If so, special day names may be used (like 'Today', 'Yesterday', 'Tomorrow' with '^' and 'today', 'yesterday', 'tomorrow' width '*') instead of the date.
     * @param string|\DateTimeZone $toTimezone The timezone to set; leave empty to use the default timezone (or the timezone associated to $value if it's already a \DateTime)
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @return string Returns an empty string if $value is empty, the localized textual representation otherwise
     *
     * @see toDateTime()
     * @see https://cldr.unicode.org/translation/date-time/datetime-patterns
     * @see https://cldr.unicode.org/translation/date-time
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
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
     * @param \DateTime|\DateTimeInterface $value The \DateTimeInterface instance for which you want the localized textual representation
     * @param string $width The format name; it can be 'full' (eg 'h:mm:ss a zzzz' - '11:42:13 AM GMT+2:00'), 'long' (eg 'h:mm:ss a z' - '11:42:13 AM GMT+2:00'), 'medium' (eg 'h:mm:ss a' - '11:42:13 AM') or 'short' (eg 'h:mm a' - '11:42 AM'),
     *                      or a skeleton pattern prefixed by '~', e.g. '~Hm'.
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @return string Returns an empty string if $value is empty, the localized textual representation otherwise
     *
     * @see https://cldr.unicode.org/translation/date-time/datetime-patterns
     * @see https://cldr.unicode.org/translation/date-time
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
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
     * @param number|\DateTime|\DateTimeInterface|string $value A Unix timestamp, a `\DateTimeInterface` instance or a string accepted by {@link https://www.php.net/manual/function.strtotime.php strtotime}.
     * @param string $width The format name; it can be 'full' (eg 'h:mm:ss a zzzz' - '11:42:13 AM GMT+2:00'), 'long' (eg 'h:mm:ss a z' - '11:42:13 AM GMT+2:00'), 'medium' (eg 'h:mm:ss a' - '11:42:13 AM') or 'short' (eg 'h:mm a' - '11:42 AM'),
     *                      or a skeleton pattern prefixed by '~', e.g. '~Hm'.
     * @param string|\DateTimeZone $toTimezone The timezone to set; leave empty to use the default timezone (or the timezone associated to $value if it's already a \DateTime)
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @return string Returns an empty string if $value is empty, the localized textual representation otherwise
     *
     * @see toDateTime()
     * @see https://cldr.unicode.org/translation/date-time/datetime-patterns
     * @see https://cldr.unicode.org/translation/date-time
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
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
     * @param \DateTime|\DateTimeInterface $value The \DateTimeInterface instance for which you want the localized textual representation
     * @param string $width The format name; it can be 'full', 'long', 'medium', 'short' or a skeleton pattern prefixed by '~',
     *                      or a combination for date+time like 'full|short' or a combination for format+date+time like 'full|full|short'
     *                      You can also append an asterisk ('*') to the date part of $width. If so, special day names may be used (like 'Today', 'Yesterday', 'Tomorrow') instead of the date part.
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @return string Returns an empty string if $value is empty, the localized textual representation otherwise
     *
     * @see https://cldr.unicode.org/translation/date-time/datetime-patterns
     * @see https://cldr.unicode.org/translation/date-time
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
     */
    public static function formatDatetime($value, $width, $locale = '')
    {
        $overrideDateFormat = '';
        if (is_string($width)) {
            $chunks = explode('|', $width);
            switch (count($chunks)) {
                case 1:
                case 2:
                    $dateFormat = $chunks[0];
                    break;
                case 3:
                    $dateFormat = $chunks[1];
                    break;
                default:
                    $dateFormat = '';
                    break;
            }
            $c = $dateFormat !== '' ? @substr($dateFormat, -1) : '';
            if (($c === '^') || ($c === '*')) {
                $dayName = static::getDateRelativeName($value, ($c === '^') ? true : false, $locale);
                if ($dayName !== '') {
                    $overrideDateFormat = "'{$dayName}'";
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
     * @param number|\DateTime|\DateTimeInterface|string $value A Unix timestamp, a `\DateTimeInterface` instance or a string accepted by {@link https://www.php.net/manual/function.strtotime.php strtotime}.
     * @param string $width The format name; it can be 'full', 'long', 'medium', 'short' or a combination for date+time like 'full|short' or a combination for format+date+time like 'full|full|short'
     *                      You can also append an asterisk ('*') to the date part of $width. If so, special day names may be used (like 'Today', 'Yesterday', 'Tomorrow') instead of the date part.
     * @param string|\DateTimeZone $toTimezone The timezone to set; leave empty to use the default timezone (or the timezone associated to $value if it's already a \DateTime)
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @return string Returns an empty string if $value is empty, the localized textual representation otherwise
     *
     * @see toDateTime()
     * @see https://cldr.unicode.org/translation/date-time/datetime-patterns
     * @see https://cldr.unicode.org/translation/date-time
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
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
     * Format a date/time interval.
     *
     * @param \DateTime|\DateTimeInterface $earliest the first date of the interval
     * @param \DateTime|\DateTimeInterface $latest The last date of the
     * @param string $skeleton The locale-independent skeleton, e.g. "yMMMd" or "Hm".
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data.
     *
     * @return string Returns the localized textual representation of the interval
     *
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#intervalFormats
     */
    public static function formatInterval($earliest, $latest, $skeleton, $locale = '')
    {
        $greatestDifference = self::getGreatestDifference($earliest, $latest);

        list($format, $earliestFirst) = static::getIntervalFormat($skeleton, $greatestDifference, $locale);

        if ($earliestFirst === null) {
            return static::format($earliest, $format, $locale);
        }
        list($format1, $format2) = static::splitIntervalFormat($format);

        return static::format(
            $earliestFirst ? $earliest : $latest,
            $format1,
            $locale
        ) . static::format(
            $earliestFirst ? $latest : $earliest,
            $format2,
            $locale
        );
    }

    /**
     * Format a date/time interval (extended version: various date/time representations - see toDateTime()).
     *
     * @param number|\DateTime|\DateTimeInterface|string $earliest An Unix timestamp, a `\DateTime` instance or a string accepted by {@link https://www.php.net/manual/function.strtotime.php strtotime}.
     * @param number|\DateTime|\DateTimeInterface|string $latest An Unix timestamp, a `\DateTime` instance or a string accepted by {@link https://www.php.net/manual/function.strtotime.php strtotime}.
     * @param string $skeleton The locale-independent skeleton, e.g. "yMMMd" or "Hm".
     * @param string|\DateTimeZone $toTimezone The timezone to set; leave empty to use the default timezone (or the timezone associated to $value if it's already a \DateTime)
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data.
     *
     * @return string Returns the localized textual representation of the interval
     */
    public static function formatIntervalEx($earliest, $latest, $skeleton, $toTimezone = '', $locale = '')
    {
        return static::formatInterval(
            static::toDateTime($earliest, $toTimezone),
            static::toDateTime($latest, $toTimezone),
            $skeleton,
            $locale
        );
    }

    /**
     * Format a date and/or time.
     *
     * @param \DateTime|\DateTimeInterface $value The \DateTimeInterface instance for which you want the localized textual representation
     * @param string $format The ISO format that specify how to render the date/time. The following extra format chunks are available:
     *                       - 'P': ISO-8601 numeric representation of the day of the week (same as 'e' but not locale dependent)
     *                       - 'PP': Numeric representation of the day of the week, from 0 (for Sunday) to 6 (for Saturday)
     *                       - 'PPP': English ordinal suffix for the day of the month
     *                       - 'PPPP': The day of the year (starting from 0)
     *                       - 'PPPPP': Number of days in the given month
     *                       - 'PPPPPP': Whether it's a leap year: 1 if it is a leap year, 0 otherwise.
     *                       - 'PPPPPPP': Lowercase Ante meridiem and Post meridiem (English only, for other locales it's the same as 'a')
     *                       - 'PPPPPPPP': Swatch Internet time
     *                       - 'PPPPPPPPP': Microseconds
     *                       - 'PPPPPPPPPP': Whether or not the date is in daylight saving time	1 if Daylight Saving Time, 0 otherwise.
     *                       - 'PPPPPPPPPPP': Timezone offset in seconds
     *                       - 'PPPPPPPPPPPP': RFC 2822 formatted date (Example: 'Thu, 21 Dec 2000 16:01:07 +0200')
     *                       - 'PPPPPPPPPPPPP': Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @return string Returns an empty string if $value is empty, the localized textual representation otherwise
     *
     * @see https://cldr.unicode.org/translation/date-time/datetime-patterns
     * @see https://cldr.unicode.org/translation/date-time
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
     */
    public static function format($value, $format, $locale = '')
    {
        $result = '';
        if (!empty($value)) {
            if (!($value instanceof DateTimeInterface || $value instanceof DateTime)) {
                throw new Exception\BadArgumentType($value, '\\DateTime');
            }
            if (!is_string($format) || $format === '') {
                throw new Exception\BadArgumentType($format, 'date/time ISO format');
            }
            $decoder = self::tokenizeFormat($format);
            if (empty($locale)) {
                $locale = Data::getDefaultLocale();
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
     * @param number|\DateTime|\DateTimeInterface|string $value A Unix timestamp, a `\DateTimeInterface` instance or a string accepted by {@link https://www.php.net/manual/function.strtotime.php strtotime}.
     * @param string $format The ISO format that specify how to render the date/time. The following extra format chunks are valid:
     *                       - 'P': ISO-8601 numeric representation of the day of the week (same as 'e' but not locale dependent)
     *                       - 'PP': Numeric representation of the day of the week, from 0 (for Sunday) to 6 (for Saturday)
     *                       - 'PPP': English ordinal suffix for the day of the month
     *                       - 'PPPP': The day of the year (starting from 0)
     *                       - 'PPPPP': Number of days in the given month
     *                       - 'PPPPPP': Whether it's a leap year: 1 if it is a leap year, 0 otherwise.
     *                       - 'PPPPPPP': Lowercase Ante meridiem and Post meridiem (English only, for other locales it's the same as 'a')
     *                       - 'PPPPPPPP': Swatch Internet time
     *                       - 'PPPPPPPPP': Microseconds
     *                       - 'PPPPPPPPPP': Whether or not the date is in daylight saving time	1 if Daylight Saving Time, 0 otherwise.
     *                       - 'PPPPPPPPPPP': Timezone offset in seconds
     *                       - 'PPPPPPPPPPPP': RFC 2822 formatted date (Example: 'Thu, 21 Dec 2000 16:01:07 +0200')
     *                       - 'PPPPPPPPPPPPP': Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)
     * @param string|\DateTimeZone $toTimezone The timezone to set; leave empty to use the default timezone (or the timezone associated to $value if it's already a \DateTime)
     * @param string $locale The locale to use. If empty we'll use the default locale set in \Punic\Data
     *
     * @throws \Punic\Exception Throws an exception in case of problems
     *
     * @return string Returns an empty string if $value is empty, the localized textual representation otherwise
     *
     * @see https://cldr.unicode.org/translation/date-time/datetime-patterns
     * @see https://cldr.unicode.org/translation/date-time
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Format_Patterns
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
     * @param \DateTime|\DateTimeInterface $datetime The date for which you want the relative day name
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
            $key = "relative-type-{$deltaDays}";
            if (isset($data[$key])) {
                $result = $data[$key];
                if ($ucFirst) {
                    $result = Misc::fixCase($result, 'titlecase-firstword');
                }
            }
        }

        return $result;
    }

    /**
     * @param string $width
     * @param string $locale
     * @param string $overrideDateFormat
     * @param string $overrideTimeFormat
     *
     * @throws Exception\BadArgumentType
     * @throws Exception\ValueNotInList
     *
     * @return string
     */
    protected static function getDatetimeFormatReal($width, $locale = '', $overrideDateFormat = '', $overrideTimeFormat = '')
    {
        $chunks = explode('|', @str_replace(array('*', '^'), '', $width));
        switch (count($chunks)) {
            case 1:
                if ($width !== '' && $width[0] === '~') {
                    return self::getSkeletonFormat(substr($width, 1), $locale);
                }
                $timeWidth = $dateWidth = $wholeWidth = $chunks[0];
                break;
            case 2:
                $dateWidth = $chunks[0];
                $timeWidth = $chunks[1];
                $wholeWidth = self::getDatetimeWidth($dateWidth, $timeWidth);
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
            throw new Exception\ValueNotInList($wholeWidth, array_keys(array_filter($data, 'is_string')));
        }

        return sprintf(
            $data[$wholeWidth],
            $overrideTimeFormat !== '' ? $overrideTimeFormat : static::getTimeFormat($timeWidth, $locale),
            $overrideDateFormat !== '' ? $overrideDateFormat : static::getDateFormat($dateWidth, $locale)
        );
    }

    /**
     * @param string $dateWidth
     *
     * @return string
     */
    protected static function getDatetimeWidth($dateWidth)
    {
        if ($dateWidth === '' || $dateWidth[0] !== '~') {
            return $dateWidth;
        }

        // Select fullWidth according to UTS #35, part 4, section 2.6.2.2.
        // Strip string literal text.
        $dateWidth = preg_replace("@'.*?'@", '', $dateWidth);
        if (strpos($dateWidth, 'MMMM') !== false && strpos($dateWidth, 'MMMMM') == false
            || strpos($dateWidth, 'LLLL') !== false && strpos($dateWidth, 'LLLLL') == false) {
            if (strpos($dateWidth, 'E') !== false
                || strpos($dateWidth, 'e') !== false
                || strpos($dateWidth, 'c') !== false) {
                $wholeWidth = 'full';
            } else {
                $wholeWidth = 'long';
            }
        } elseif (strpos($dateWidth, 'MMM') !== false || strpos($dateWidth, 'LLL') !== false) {
            $wholeWidth = 'medium';
        } else {
            $wholeWidth = 'short';
        }

        return $wholeWidth;
    }

    /**
     * Rudimentary implementation of skeleton matching algorithm in #UTS 35, part 2, section 2.6.2.1.
     *
     * Limitations:
     * - No matching of different but equivalent fields (e.g. H, k, h, K).
     * - Distance calculation ignores difference between numeric and text fields.
     * - No support for appendItems.
     *
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#Matching_Skeletons
     *
     * @param string $requestedSkeleton
     * @param string[] $availableSkeletons
     *
     * @return array
     */
    protected static function getBestMatchingSkeleton($requestedSkeleton, $availableSkeletons)
    {
        if (in_array($requestedSkeleton, $availableSkeletons)) {
            return array($requestedSkeleton, array());
        }

        $requestedFields = array_values(array_unique(str_split($requestedSkeleton, 1)));
        $requestedLength = strlen($requestedSkeleton);

        // UTS 35, part 2, section 2.6.2.1, step 4: If patterns match apart from second
        // fraction field, adjust for this afterwards.
        $sWidth = substr_count($requestedSkeleton, 'S');
        if ($sWidth) {
            $requestedLengthWithoutMs = $requestedLength - $sWidth;
            $requestedFieldsWithoutMs = array_values(array_diff($requestedFields, array('S')));
        }

        $candidateSkeletons = array();
        foreach ($availableSkeletons as $skeleton) {
            $fields = array_values(array_unique(str_split($skeleton, 1)));

            if ($fields === $requestedFields) {
                $candidateSkeletons[$skeleton] = abs(strlen($skeleton) - $requestedLength);
            } elseif ($sWidth && $fields === $requestedFieldsWithoutMs) {
                $candidateSkeletons[$skeleton] = abs(strlen($skeleton) - $requestedLengthWithoutMs);
            }
        }

        if (!$candidateSkeletons) {
            return false;
        }

        asort($candidateSkeletons);
        $matchSkeleton = key($candidateSkeletons);

        $countAdjustments = array();
        foreach ($requestedFields as $field) {
            $count = substr_count($requestedSkeleton, $field);
            if ($count !== substr_count($matchSkeleton, $field)) {
                $countAdjustments[$field] = $count;
            }
        }

        return array(
            $matchSkeleton,
            $countAdjustments,
        );
    }

    /**
     * Replace special input skeleton fields (j, J, C) with locale-specific substitutions.
     *
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Field_Symbol_Table
     *
     * @param string $skeleton
     * @param string $locale
     *
     * @return array
     */
    protected static function preprocessSkeleton($skeleton, $locale)
    {
        $replacements = array();
        $match = (string) strpbrk($skeleton, 'jJC');
        if ($match !== '') {
            $field = $match[0];
            $timeData = Data::getGeneric('timeData');
            $time = Data::getTerritoryNode($timeData, $locale);

            if ($field === 'J') {
                $skeleton = str_replace('J', 'H', $skeleton);
                $replacements['h'] = $replacements['H'] = $time['preferred'][0];
            } else {
                $index = strpos($skeleton, $field);
                $count = strspn($skeleton, $field, $index);
                $fieldA = 'a';
                if ($field === 'j') {
                    $fieldH = $time['preferred'][0];
                } else { // $field === 'C'
                    $fieldH = $time['allowed'][0][0];
                    $match = (string) strpbrk($time['allowed'][0], 'bB');
                    if ($match !== '') {
                        $fieldA = $match[0];
                    }
                }
                // 'j' maps to 'h a', 'jj' to 'hh a', 'jjj' to 'h aaaa', 'jjjj' to 'h aaaa', etc.
                $countH = 2 - $count % 2;
                $countA = ($count <= 2 ? 1 : ($count <= 4 ? 4 : 5));
                $skeleton = substr($skeleton, 0, $index) . str_repeat($fieldH, $countH) . substr($skeleton, $index + $count);
                $replacements['a'] = str_repeat($fieldA, $countA);
            }
        }

        return array($skeleton, $replacements);
    }

    /**
     * Replace special input skeleton fields, adjust field widths, and add second fraction to format pattern.
     *
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Field_Symbol_Table
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#Matching_Skeletons
     *
     * @param string $format
     * @param array $countAdjustments
     * @param array $replacements
     * @param string $locale
     *
     * @return string
     */
    protected static function postprocessSkeletonFormat($format, $countAdjustments, $replacements, $locale)
    {
        static $countFields = array(
            'q' => 'Q',
            'L' => 'M',
            'e' => 'E',
            'c' => 'E',
            'b' => 'b',
            'b' => 'B',
            'K' => 'h',
            'k' => 'H',
        );

        $postprocessedFormat = '';
        $quoted = false;
        $length = strlen($format);
        for ($index = 0; $index < $length; $index++) {
            $char = $format[$index];
            if ($char === "'") {
                $quoted = !$quoted;
                $postprocessedFormat .= $char;
            } elseif (!$quoted) {
                $count = 1;
                for ($j = $index + 1; ($j < $length) && ($format[$j] === $char); $j++) {
                    $count++;
                    $index++;
                }

                $countField = isset($countFields[$char]) ? $countFields[$char] : $char;
                if (isset($countAdjustments[$countField])) {
                    $count = $countAdjustments[$countField];
                }
                if (isset($replacements[$char])) {
                    $char = $replacements[$char];
                }
                $postprocessedFormat .= str_repeat($char, $count);

                // Add second fraction if requested, and format does not contain it already.
                if ($char === 's' && isset($countAdjustments['S']) && strpos($format, 'S') === false) {
                    $data = Data::get('numbers', $locale);
                    $decimal = $data['symbols']['decimal'];
                    $postprocessedFormat .= $decimal . str_repeat('S', $countAdjustments['S']);
                }
            } else {
                $postprocessedFormat .= $char;
            }
        }

        return $postprocessedFormat;
    }

    /**
     * Return the most significant field where the two dates differ. For fractional seconds,
     * 'S' is returned if the differ on the first decimal, 'SS' for the second decimal etc.
     * If the dates are identical, the empty string is returned.
     *
     * @param \DateTime|\DateTimeInterface $value1
     * @param \DateTime|\DateTimeInterface $value2
     *
     * @return string
     */
    protected static function getGreatestDifference($value1, $value2)
    {
        if (!($value1 instanceof DateTimeInterface || $value1 instanceof DateTime)) {
            throw new Exception\BadArgumentType($value1, '\\DateTime');
        }
        if (!($value2 instanceof DateTimeInterface || $value2 instanceof DateTime)) {
            throw new Exception\BadArgumentType($value2, '\\DateTime');
        }

        $length = strlen(self::$differenceFields);
        for ($index = 0; $index < $length; $index++) {
            $field = self::$differenceFields[$index];
            // We just need to check if this field is the same for the two dates,
            // so any width and locale will do.
            $function = self::$decoderFunctions[$field];
            if (static::$function($value1, 1, 'en') !== static::$function($value2, 1, 'en')) {
                return $field;
            }
        }

        // Find the decimal where fractional seconds differ.
        for ($count = 2; $count < 6; $count++) {
            if (static::$function($value1, $count, 'en') !== static::$function($value2, $count, 'en')) {
                return str_repeat('S', $count);
            }
        }

        // Dates are identical.
        return '';
    }

    /**
     * Adjust greatest difference to the fields used in the skeleton.
     *
     * The spec does not go into much detail on how to do this. This logic works
     * with the skeletons currently provided in the data files but may need adjustment
     * if e.g. skeletons including weeks (w) are added.
     *
     * @param string $greatestDifference
     * @param string $skeleton
     *
     * @return string
     */
    protected static function adjustGreatestDifference($greatestDifference, $skeleton)
    {
        if ($greatestDifference === '') {
            return '';
        }

        // Adjust index for fractional second width (S).
        $greatestDifferenceIndex = strpos(self::$differenceFields, $greatestDifference[0]) + strlen($greatestDifference) - 1;
        if ($greatestDifferenceIndex === false) {
            throw new Exception\ValueNotInList($greatestDifference, str_split(self::$differenceFields, 1));
        }

        // Strip fields that do not represent an interval.
        $normalizedSkeleton = str_replace(array('z', 'Z', 'O', 'v', 'V', 'X', 'x', 'P'), '', $skeleton);
        $skeletonGranularity = substr($normalizedSkeleton, -1);
        if ($skeletonGranularity === 'h') {
            $skeletonGranularity = 'H';
        }
        $skeletonGranularityIndex = strpos(self::$differenceFields, $skeletonGranularity);
        if ($skeletonGranularityIndex === false) {
            throw new Exception\ValueNotInList($skeletonGranularity, str_split(self::$differenceFields, 1));
        }

        $adjustedGreatestDifference = $greatestDifference;
        if ($adjustedGreatestDifference === 'a' && strpos($skeleton, 'H') !== false) {
            // With a 24-hour clock we do not care about dayperiods.
            $adjustedGreatestDifference = 'H';
        } elseif ($adjustedGreatestDifference === 'H' && strpos($skeleton, 'h') !== false) {
            // 12-hour clock skeletons use h to indicate hour.
            $adjustedGreatestDifference = 'h';
        } elseif ($adjustedGreatestDifference === 'Q' && strpos($skeleton, 'Q') === false) {
            // Ignore quarter, if it is not part of the skeleton.
            $adjustedGreatestDifference = 'M';
        } elseif ($adjustedGreatestDifference[0] === 'S') {
            $skeletonGranularityIndex += substr_count($skeleton, 'S') - 1;
        }

        if ($greatestDifferenceIndex > $skeletonGranularityIndex) {
            // The dates are identical or only differ on less significant fields
            // not included in the skeleton.
            return '';
        }

        return $adjustedGreatestDifference;
    }

    /**
     * Splits an interval format into two datetime formats.
     *
     * @param string $format
     *
     * @return string[] An array containing two entries, each representing a datetime format
     *
     * @see https://www.unicode.org/reports/tr35/tr35-dates.html#intervalFormats
     */
    protected static function splitIntervalFormat($format)
    {
        $functionNames = array(array());
        $index = 0;

        // Split on the first recurring field.
        $tokens = self::tokenizeFormat($format);
        foreach ($tokens as $token) {
            if (is_array($token)) {
                if (in_array($token[0], $functionNames)) {
                    $index = $token[2];

                    return array(
                        substr($format, 0, $index),
                        substr($format, $index),
                    );
                }
                $functionNames[] = $token[0];
            }
        }

        throw new \Punic\Exception('No recurring field found in format: ' . $format);
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     * @param bool $standAlone
     *
     * @return string
     */
    protected static function decodeDayOfWeek($value, $count, $locale, $standAlone = false)
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

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     * @param bool $standAlone
     *
     * @return string
     */
    protected static function decodeDayOfWeekLocal($value, $count, $locale, $standAlone = false)
    {
        switch ($count) {
            case 1:
            case 2:
                $weekDay = (int) ($value->format('w'));
                $firstWeekdayForCountry = static::getFirstWeekday($locale);
                $localWeekday = 1 + ((7 + $weekDay - $firstWeekdayForCountry) % 7);

                return str_pad((string) $localWeekday, $count, '0', STR_PAD_LEFT);
            default:
                return static::decodeDayOfWeek($value, $count, $locale, $standAlone);
        }
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeDayOfWeekLocalAlone($value, $count, $locale)
    {
        return static::decodeDayOfWeekLocal($value, $count, $locale, true);
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeDayOfMonth($value, $count, $locale)
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

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     * @param bool $standAlone
     *
     * @return string
     */
    protected static function decodeMonth($value, $count, $locale, $standAlone = false)
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

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeMonthAlone($value, $count, $locale)
    {
        return static::decodeMonth($value, $count, $locale, true);
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeYear($value, $count, $locale)
    {
        switch ($count) {
            case 1:
                return (string) ((int) ($value->format('Y')));
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

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeHour12($value, $count, $locale)
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

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeDayperiod($value, $count, $locale)
    {
        switch ($count) {
            case 1:
            case 2:
            case 3:
                return static::getDayperiodName($value, 'abbreviated', $locale);
            case 4:
                return static::getDayperiodName($value, 'wide', $locale);
            case 5:
                return static::getDayperiodName($value, 'narrow', $locale);
            default:
                throw new Exception\ValueNotInList($count, array(1));
        }
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeVariableDayperiod($value, $count, $locale)
    {
        switch ($count) {
            case 1:
            case 2:
            case 3:
                return static::getVariableDayperiodName($value, 'abbreviated', $locale);
            case 4:
                return static::getVariableDayperiodName($value, 'abbreviated', $locale);
            case 5:
                return static::getVariableDayperiodName($value, 'abbreviated', $locale);
            default:
                throw new Exception\ValueNotInList($count, array(1));
        }
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeHour24($value, $count, $locale)
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

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeHour12From0($value, $count, $locale)
    {
        switch ($count) {
            case 1:
            case 2:
                return str_pad((string) ((int) ($value->format('G')) % 12), $count, '0', STR_PAD_LEFT);
            default:
                throw new Exception\ValueNotInList($count, array(1, 2));
        }
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeHour24From1($value, $count, $locale)
    {
        switch ($count) {
            case 1:
            case 2:
                return str_pad((string) (1 + (int) ($value->format('G'))), $count, '0', STR_PAD_LEFT);
            default:
                throw new Exception\ValueNotInList($count, array(1, 2));
        }
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeMinute($value, $count, $locale)
    {
        switch ($count) {
            case 1:
                return (string) ((int) ($value->format('i')));
            case 2:
                return $value->format('i');
            default:
                throw new Exception\ValueNotInList($count, array(1, 2));
        }
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeSecond($value, $count, $locale)
    {
        switch ($count) {
            case 1:
                return (string) ((int) ($value->format('s')));
            case 2:
                return $value->format('s');
            default:
                throw new Exception\ValueNotInList($count, array(1, 2));
        }
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeTimezoneNoLocationSpecific($value, $count, $locale)
    {
        switch ($count) {
            case 1:
            case 2:
            case 3:
                $tz = static::getTimezoneNameNoLocationSpecific($value, 'short', '', $locale);
                if ($tz === '') {
                    $tz = static::decodeTimezoneShortGMT($value, 1, $locale);
                }
                break;
            case 4:
                $tz = static::getTimezoneNameNoLocationSpecific($value, 'long', '', $locale);
                if ($tz === '') {
                    $tz = static::decodeTimezoneShortGMT($value, 4, $locale);
                }
                break;
            default:
                throw new Exception\ValueNotInList($count, array(1, 2, 3, 4));
        }

        return $tz;
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeTimezoneShortGMT($value, $count, $locale)
    {
        $offset = $value->getOffset();
        $sign = ($offset < 0) ? '-' : '+';
        $seconds = abs($offset);
        $hours = (int) (floor($seconds / 3600));
        $seconds -= $hours * 3600;
        $minutes = (int) (floor($seconds / 60));
        $data = Data::get('timeZoneNames', $locale);
        $format = isset($data['gmtFormat']) ? $data['gmtFormat'] : 'GMT%1$s';
        switch ($count) {
            case 1:
                return sprintf($format, $sign . $hours . (($minutes === 0) ? '' : (':' . substr('0' . $minutes, -2))));
            case 4:
                return sprintf($format, $sign . substr('0' . $hours, -2) . ':' . substr('0' . $minutes, -2));
            default:
                throw new Exception\ValueNotInList($count, array(1, 4));
        }
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeEra($value, $count, $locale)
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

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeYearWeekOfYear($value, $count, $locale)
    {
        $y = $value->format('o');
        if ($count === 2) {
            $y = substr('0' . $y, -2);
        } else {
            if (!isset($y[$count])) {
                $y = str_pad($y, $count, '0', STR_PAD_LEFT);
            }
        }

        return $y;
    }

    /**
     * Note: we assume Gregorian calendar here.
     *
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeYearExtended($value, $count, $locale)
    {
        return static::decodeYear($value, $count, $locale);
    }

    /**
     * Note: we assume Gregorian calendar here.
     *
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeYearRelatedGregorian($value, $count, $locale)
    {
        return static::decodeYearExtended($value, $count, $locale);
    }

    /**
     * @param DateTime|DateTimeInterface $value
     * @param int $count
     * @param string $locale
     * @param bool $standAlone
     *
     * @return string
     */
    protected static function decodeQuarter($value, $count, $locale, $standAlone = false)
    {
        $quarter = 1 + (int) (floor(((int) ($value->format('n')) - 1) / 3));
        switch ($count) {
            case 1:
                return (string) $quarter;
            case 2:
                return '0' . (string) $quarter;
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

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeQuarterAlone($value, $count, $locale)
    {
        return static::decodeQuarter($value, $count, $locale, true);
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeWeekOfYear($value, $count, $locale)
    {
        switch ($count) {
            case 1:
                return (string) ((int) ($value->format('W')));
            case 2:
                return $value->format('W');
            default:
                throw new Exception\ValueNotInList($count, array(1, 2));
        }
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeDayOfYear($value, $count, $locale)
    {
        switch ($count) {
            case 1:
            case 2:
            case 3:
                return str_pad((string) (1 + $value->format('z')), $count, '0', STR_PAD_LEFT);
            default:
                throw new Exception\ValueNotInList($count, array(1, 2, 3));
        }
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeWeekdayInMonth($value, $count, $locale)
    {
        switch ($count) {
            case 1:
            case 2:
            case 3:
                $dom = (int) ($value->format('j'));
                $wim = 1 + (int) (floor(($dom - 1) / 7));

                return str_pad((string) $wim, $count, '0', STR_PAD_LEFT);
            default:
                throw new Exception\ValueNotInList($count, array(1, 2, 3));
        }
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeFractionsOfSeconds($value, $count, $locale)
    {
        return substr(str_pad($value->format('u'), $count, '0', STR_PAD_RIGHT), 0, $count);
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeMsecInDay($value, $count, $locale)
    {
        $hours = (int) ($value->format('G'));
        $minutes = $hours * 60 + (int) ($value->format('i'));
        $seconds = $minutes * 60 + (int) ($value->format('s'));
        $milliseconds = $seconds * 1000 + (int) (floor((int) ($value->format('u')) / 1000));

        return str_pad((string) $milliseconds, $count, '0', STR_PAD_LEFT);
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeTimezoneDelta($value, $count, $locale)
    {
        $offset = $value->getOffset();
        $sign = ($offset < 0) ? '-' : '+';
        $seconds = abs($offset);
        $hours = (int) (floor($seconds / 3600));
        $seconds -= $hours * 3600;
        $minutes = (int) (floor($seconds / 60));
        $seconds -= $minutes * 60;
        $partsWithoutSeconds = array();
        $partsWithoutSeconds[] = $sign . substr('0' . (string) $hours, -2);
        $partsWithoutSeconds[] = substr('0' . (string) $minutes, -2);
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

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeTimezoneNoLocationGeneric($value, $count, $locale)
    {
        switch ($count) {
            case 1:
                $tz = static::getTimezoneNameNoLocationSpecific($value, 'short', 'generic', $locale);
                if ($tz === '') {
                    $tz = static::decodeTimezoneID($value, 4, $locale);
                }
                break;
            case 4:
                $tz = static::getTimezoneNameNoLocationSpecific($value, 'long', 'generic', $locale);
                if ($tz === '') {
                    $tz = static::decodeTimezoneID($value, 4, $locale);
                }
                break;
            default:
                throw new Exception\ValueNotInList($count, array(1, 4));
        }

        return $tz;
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeTimezoneID($value, $count, $locale)
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
                $result = static::getTimezoneNameLocationSpecific($value, $locale);
                if ($result === '') {
                    $result = static::decodeTimezoneShortGMT($value, 4, $locale);
                }
                break;
            default:
                throw new Exception\ValueNotInList($count, array(1, 2, 3, 4));
        }

        return $result;
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     * @param bool $zForZero
     *
     * @return string
     */
    protected static function decodeTimezoneWithTime($value, $count, $locale, $zForZero = false)
    {
        $offset = $value->getOffset();
        $useZ = ($zForZero && ($offset === 0)) ? true : false;
        $sign = ($offset < 0) ? '-' : '+';
        $seconds = abs($offset);
        $hours = (int) (floor($seconds / 3600));
        $seconds -= $hours * 3600;
        $minutes = (int) (floor($seconds / 60));
        $seconds -= $minutes * 60;
        $hours2 = $sign . substr('0' . (string) $hours, -2);
        $minutes2 = substr('0' . (string) $minutes, -2);
        /*
         * TZWS
         * $seconds2 = substr('0' . strval($seconds), -2);
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
                $result = $useZ ? 'Z' : "{$hours2}{$minutes2}";
                break;
            case 3:
                $result = $useZ ? 'Z' : "{$hours2}:{$minutes2}";
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

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeTimezoneWithTimeZ($value, $count, $locale)
    {
        return static::decodeTimezoneWithTime($value, $count, $locale, true);
    }

    /**
     * @todo
     *
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodeWeekOfMonth($value, $count, $locale)
    {
        throw new Exception\NotImplemented(__METHOD__);
    }

    /**
     * @todo
     */
    protected static function decodeYearCyclicName()
    {
        throw new Exception\NotImplemented(__METHOD__);
    }

    /**
     * @todo
     */
    protected static function decodeModifiedGiulianDay()
    {
        throw new Exception\NotImplemented(__METHOD__);
    }

    /**
     * @param string $timezoneID
     *
     * @return string
     */
    protected static function getTimezoneCanonicalID($timezoneID)
    {
        $timeZones = Data::getGeneric('timeZones');
        if (isset($timeZones['aliases'][$timezoneID])) {
            $timezoneID = $timeZones['aliases'][$timezoneID];
        }

        return $timezoneID;
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     * @param int $count
     * @param string $locale
     *
     * @return string
     */
    protected static function decodePunicExtension($value, $count, $locale)
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

    /**
     * @param \DateTime|\DateTimeInterface $dt
     *
     * @return string
     */
    protected static function getTimezoneNameFromDatetime($dt)
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

    /**
     * @return string
     */
    protected static function getTimezoneNameFromTimezone(DateTimeZone $tz)
    {
        if (defined('\HHVM_VERSION')) {
            $testDT = new DateTime('now', $tz);
            $result = $testDT->format('e');
            if (!preg_match('/[0-9][0-9]/', $result)) {
                $result = $tz->getName();
            }
        } else {
            $result = $tz->getName();
        }

        return $result;
    }

    /**
     * @param \DateTime|\DateTimeInterface $dt
     *
     * @return array|false
     */
    protected static function getTimezoneLocationFromDatetime($dt)
    {
        if (defined('\HHVM_VERSION')) {
            if (!preg_match('/[0-9][0-9]/', $dt->format('e'))) {
                $result = $dt->getTimezone()->getLocation();
            } else {
                $result = false;
            }
        } else {
            $result = $dt->getTimezone()->getLocation();
        }

        return $result;
    }

    /**
     * Tokenize an ISO date/time format string.
     *
     * @param string $format
     *
     * @return array
     */
    protected static function tokenizeFormat($format)
    {
        if (isset(self::$tokenizerCache[$format])) {
            $result = self::$tokenizerCache[$format];
        } else {
            $result = array();
            $length = strlen($format);
            $lengthM1 = $length - 1;
            $quoted = false;
            for ($index = 0; $index < $length; $index++) {
                $char = $format[$index];
                if ($char === "'") {
                    if (($index < $lengthM1) && ($format[$index + 1] === "'")) {
                        $result[] = "'";
                        $index++;
                    } else {
                        $quoted = !$quoted;
                    }
                } elseif ($quoted) {
                    $result[] = $char;
                } else {
                    $count = 1;
                    for ($j = $index + 1; ($j < $length) && ($format[$j] === $char); $j++) {
                        $count++;
                    }
                    if (isset(self::$decoderFunctions[$char])) {
                        $result[] = array(self::$decoderFunctions[$char], $count, $index);
                    } else {
                        $result[] = str_repeat($char, $count);
                    }
                    $index += $count - 1;
                }
            }
            self::$tokenizerCache[$format] = $result;
        }

        return $result;
    }
}
