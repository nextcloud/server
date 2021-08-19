<?php

namespace Sabre\VObject;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

/**
 * DateTimeParser.
 *
 * This class is responsible for parsing the several different date and time
 * formats iCalendar and vCards have.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class DateTimeParser
{
    /**
     * Parses an iCalendar (rfc5545) formatted datetime and returns a
     * DateTimeImmutable object.
     *
     * Specifying a reference timezone is optional. It will only be used
     * if the non-UTC format is used. The argument is used as a reference, the
     * returned DateTimeImmutable object will still be in the UTC timezone.
     *
     * @param string       $dt
     * @param DateTimeZone $tz
     *
     * @return DateTimeImmutable
     */
    public static function parseDateTime($dt, DateTimeZone $tz = null)
    {
        // Format is YYYYMMDD + "T" + hhmmss
        $result = preg_match('/^([0-9]{4})([0-1][0-9])([0-3][0-9])T([0-2][0-9])([0-5][0-9])([0-5][0-9])([Z]?)$/', $dt, $matches);

        if (!$result) {
            throw new InvalidDataException('The supplied iCalendar datetime value is incorrect: '.$dt);
        }

        if ('Z' === $matches[7] || is_null($tz)) {
            $tz = new DateTimeZone('UTC');
        }

        try {
            $date = new DateTimeImmutable($matches[1].'-'.$matches[2].'-'.$matches[3].' '.$matches[4].':'.$matches[5].':'.$matches[6], $tz);
        } catch (\Exception $e) {
            throw new InvalidDataException('The supplied iCalendar datetime value is incorrect: '.$dt);
        }

        return $date;
    }

    /**
     * Parses an iCalendar (rfc5545) formatted date and returns a DateTimeImmutable object.
     *
     * @param string       $date
     * @param DateTimeZone $tz
     *
     * @return DateTimeImmutable
     */
    public static function parseDate($date, DateTimeZone $tz = null)
    {
        // Format is YYYYMMDD
        $result = preg_match('/^([0-9]{4})([0-1][0-9])([0-3][0-9])$/', $date, $matches);

        if (!$result) {
            throw new InvalidDataException('The supplied iCalendar date value is incorrect: '.$date);
        }

        if (is_null($tz)) {
            $tz = new DateTimeZone('UTC');
        }

        try {
            $date = new DateTimeImmutable($matches[1].'-'.$matches[2].'-'.$matches[3], $tz);
        } catch (\Exception $e) {
            throw new InvalidDataException('The supplied iCalendar date value is incorrect: '.$date);
        }

        return $date;
    }

    /**
     * Parses an iCalendar (RFC5545) formatted duration value.
     *
     * This method will either return a DateTimeInterval object, or a string
     * suitable for strtotime or DateTime::modify.
     *
     * @param string $duration
     * @param bool   $asString
     *
     * @return DateInterval|string
     */
    public static function parseDuration($duration, $asString = false)
    {
        $result = preg_match('/^(?<plusminus>\+|-)?P((?<week>\d+)W)?((?<day>\d+)D)?(T((?<hour>\d+)H)?((?<minute>\d+)M)?((?<second>\d+)S)?)?$/', $duration, $matches);
        if (!$result) {
            throw new InvalidDataException('The supplied iCalendar duration value is incorrect: '.$duration);
        }

        if (!$asString) {
            $invert = false;

            if ('-' === $matches['plusminus']) {
                $invert = true;
            }

            $parts = [
                'week',
                'day',
                'hour',
                'minute',
                'second',
            ];

            foreach ($parts as $part) {
                $matches[$part] = isset($matches[$part]) && $matches[$part] ? (int) $matches[$part] : 0;
            }

            // We need to re-construct the $duration string, because weeks and
            // days are not supported by DateInterval in the same string.
            $duration = 'P';
            $days = $matches['day'];

            if ($matches['week']) {
                $days += $matches['week'] * 7;
            }

            if ($days) {
                $duration .= $days.'D';
            }

            if ($matches['minute'] || $matches['second'] || $matches['hour']) {
                $duration .= 'T';

                if ($matches['hour']) {
                    $duration .= $matches['hour'].'H';
                }

                if ($matches['minute']) {
                    $duration .= $matches['minute'].'M';
                }

                if ($matches['second']) {
                    $duration .= $matches['second'].'S';
                }
            }

            if ('P' === $duration) {
                $duration = 'PT0S';
            }

            $iv = new DateInterval($duration);

            if ($invert) {
                $iv->invert = true;
            }

            return $iv;
        }

        $parts = [
            'week',
            'day',
            'hour',
            'minute',
            'second',
        ];

        $newDur = '';

        foreach ($parts as $part) {
            if (isset($matches[$part]) && $matches[$part]) {
                $newDur .= ' '.$matches[$part].' '.$part.'s';
            }
        }

        $newDur = ('-' === $matches['plusminus'] ? '-' : '+').trim($newDur);

        if ('+' === $newDur) {
            $newDur = '+0 seconds';
        }

        return $newDur;
    }

    /**
     * Parses either a Date or DateTime, or Duration value.
     *
     * @param string              $date
     * @param DateTimeZone|string $referenceTz
     *
     * @return DateTimeImmutable|DateInterval
     */
    public static function parse($date, $referenceTz = null)
    {
        if ('P' === $date[0] || ('-' === $date[0] && 'P' === $date[1])) {
            return self::parseDuration($date);
        } elseif (8 === strlen($date)) {
            return self::parseDate($date, $referenceTz);
        } else {
            return self::parseDateTime($date, $referenceTz);
        }
    }

    /**
     * This method parses a vCard date and or time value.
     *
     * This can be used for the DATE, DATE-TIME, TIMESTAMP and
     * DATE-AND-OR-TIME value.
     *
     * This method returns an array, not a DateTime value.
     *
     * The elements in the array are in the following order:
     * year, month, date, hour, minute, second, timezone
     *
     * Almost any part of the string may be omitted. It's for example legal to
     * just specify seconds, leave out the year, etc.
     *
     * Timezone is either returned as 'Z' or as '+0800'
     *
     * For any non-specified values null is returned.
     *
     * List of date formats that are supported:
     * YYYY
     * YYYY-MM
     * YYYYMMDD
     * --MMDD
     * ---DD
     *
     * YYYY-MM-DD
     * --MM-DD
     * ---DD
     *
     * List of supported time formats:
     *
     * HH
     * HHMM
     * HHMMSS
     * -MMSS
     * --SS
     *
     * HH
     * HH:MM
     * HH:MM:SS
     * -MM:SS
     * --SS
     *
     * A full basic-format date-time string looks like :
     * 20130603T133901
     *
     * A full extended-format date-time string looks like :
     * 2013-06-03T13:39:01
     *
     * Times may be postfixed by a timezone offset. This can be either 'Z' for
     * UTC, or a string like -0500 or +1100.
     *
     * @param string $date
     *
     * @return array
     */
    public static function parseVCardDateTime($date)
    {
        $regex = '/^
            (?:  # date part
                (?:
                    (?: (?<year> [0-9]{4}) (?: -)?| --)
                    (?<month> [0-9]{2})?
                |---)
                (?<date> [0-9]{2})?
            )?
            (?:T  # time part
                (?<hour> [0-9]{2} | -)
                (?<minute> [0-9]{2} | -)?
                (?<second> [0-9]{2})?

                (?: \.[0-9]{3})? # milliseconds
                (?P<timezone> # timezone offset

                    Z | (?: \+|-)(?: [0-9]{4})

                )?

            )?
            $/x';

        if (!preg_match($regex, $date, $matches)) {
            // Attempting to parse the extended format.
            $regex = '/^
                (?: # date part
                    (?: (?<year> [0-9]{4}) - | -- )
                    (?<month> [0-9]{2}) -
                    (?<date> [0-9]{2})
                )?
                (?:T # time part

                    (?: (?<hour> [0-9]{2}) : | -)
                    (?: (?<minute> [0-9]{2}) : | -)?
                    (?<second> [0-9]{2})?

                    (?: \.[0-9]{3})? # milliseconds
                    (?P<timezone> # timezone offset

                        Z | (?: \+|-)(?: [0-9]{2}:[0-9]{2})

                    )?

                )?
                $/x';

            if (!preg_match($regex, $date, $matches)) {
                throw new InvalidDataException('Invalid vCard date-time string: '.$date);
            }
        }
        $parts = [
            'year',
            'month',
            'date',
            'hour',
            'minute',
            'second',
            'timezone',
        ];

        $result = [];
        foreach ($parts as $part) {
            if (empty($matches[$part])) {
                $result[$part] = null;
            } elseif ('-' === $matches[$part] || '--' === $matches[$part]) {
                $result[$part] = null;
            } else {
                $result[$part] = $matches[$part];
            }
        }

        return $result;
    }

    /**
     * This method parses a vCard TIME value.
     *
     * This method returns an array, not a DateTime value.
     *
     * The elements in the array are in the following order:
     * hour, minute, second, timezone
     *
     * Almost any part of the string may be omitted. It's for example legal to
     * just specify seconds, leave out the hour etc.
     *
     * Timezone is either returned as 'Z' or as '+08:00'
     *
     * For any non-specified values null is returned.
     *
     * List of supported time formats:
     *
     * HH
     * HHMM
     * HHMMSS
     * -MMSS
     * --SS
     *
     * HH
     * HH:MM
     * HH:MM:SS
     * -MM:SS
     * --SS
     *
     * A full basic-format time string looks like :
     * 133901
     *
     * A full extended-format time string looks like :
     * 13:39:01
     *
     * Times may be postfixed by a timezone offset. This can be either 'Z' for
     * UTC, or a string like -0500 or +11:00.
     *
     * @param string $date
     *
     * @return array
     */
    public static function parseVCardTime($date)
    {
        $regex = '/^
            (?<hour> [0-9]{2} | -)
            (?<minute> [0-9]{2} | -)?
            (?<second> [0-9]{2})?

            (?: \.[0-9]{3})? # milliseconds
            (?P<timezone> # timezone offset

                Z | (?: \+|-)(?: [0-9]{4})

            )?
            $/x';

        if (!preg_match($regex, $date, $matches)) {
            // Attempting to parse the extended format.
            $regex = '/^
                (?: (?<hour> [0-9]{2}) : | -)
                (?: (?<minute> [0-9]{2}) : | -)?
                (?<second> [0-9]{2})?

                (?: \.[0-9]{3})? # milliseconds
                (?P<timezone> # timezone offset

                    Z | (?: \+|-)(?: [0-9]{2}:[0-9]{2})

                )?
                $/x';

            if (!preg_match($regex, $date, $matches)) {
                throw new InvalidDataException('Invalid vCard time string: '.$date);
            }
        }
        $parts = [
            'hour',
            'minute',
            'second',
            'timezone',
        ];

        $result = [];
        foreach ($parts as $part) {
            if (empty($matches[$part])) {
                $result[$part] = null;
            } elseif ('-' === $matches[$part]) {
                $result[$part] = null;
            } else {
                $result[$part] = $matches[$part];
            }
        }

        return $result;
    }

    /**
     * This method parses a vCard date and or time value.
     *
     * This can be used for the DATE, DATE-TIME and
     * DATE-AND-OR-TIME value.
     *
     * This method returns an array, not a DateTime value.
     * The elements in the array are in the following order:
     *     year, month, date, hour, minute, second, timezone
     * Almost any part of the string may be omitted. It's for example legal to
     * just specify seconds, leave out the year, etc.
     *
     * Timezone is either returned as 'Z' or as '+0800'
     *
     * For any non-specified values null is returned.
     *
     * List of date formats that are supported:
     *     20150128
     *     2015-01
     *     --01
     *     --0128
     *     ---28
     *
     * List of supported time formats:
     *     13
     *     1353
     *     135301
     *     -53
     *     -5301
     *     --01 (unreachable, see the tests)
     *     --01Z
     *     --01+1234
     *
     * List of supported date-time formats:
     *     20150128T13
     *     --0128T13
     *     ---28T13
     *     ---28T1353
     *     ---28T135301
     *     ---28T13Z
     *     ---28T13+1234
     *
     * See the regular expressions for all the possible patterns.
     *
     * Times may be postfixed by a timezone offset. This can be either 'Z' for
     * UTC, or a string like -0500 or +1100.
     *
     * @param string $date
     *
     * @return array
     */
    public static function parseVCardDateAndOrTime($date)
    {
        // \d{8}|\d{4}-\d\d|--\d\d(\d\d)?|---\d\d
        $valueDate = '/^(?J)(?:'.
                         '(?<year>\d{4})(?<month>\d\d)(?<date>\d\d)'.
                         '|(?<year>\d{4})-(?<month>\d\d)'.
                         '|--(?<month>\d\d)(?<date>\d\d)?'.
                         '|---(?<date>\d\d)'.
                         ')$/';

        // (\d\d(\d\d(\d\d)?)?|-\d\d(\d\d)?|--\d\d)(Z|[+\-]\d\d(\d\d)?)?
        $valueTime = '/^(?J)(?:'.
                         '((?<hour>\d\d)((?<minute>\d\d)(?<second>\d\d)?)?'.
                         '|-(?<minute>\d\d)(?<second>\d\d)?'.
                         '|--(?<second>\d\d))'.
                         '(?<timezone>(Z|[+\-]\d\d(\d\d)?))?'.
                         ')$/';

        // (\d{8}|--\d{4}|---\d\d)T\d\d(\d\d(\d\d)?)?(Z|[+\-]\d\d(\d\d?)?
        $valueDateTime = '/^(?:'.
                         '((?<year0>\d{4})(?<month0>\d\d)(?<date0>\d\d)'.
                         '|--(?<month1>\d\d)(?<date1>\d\d)'.
                         '|---(?<date2>\d\d))'.
                         'T'.
                         '(?<hour>\d\d)((?<minute>\d\d)(?<second>\d\d)?)?'.
                         '(?<timezone>(Z|[+\-]\d\d(\d\d?)))?'.
                         ')$/';

        // date-and-or-time is date | date-time | time
        // in this strict order.

        if (0 === preg_match($valueDate, $date, $matches)
            && 0 === preg_match($valueDateTime, $date, $matches)
            && 0 === preg_match($valueTime, $date, $matches)) {
            throw new InvalidDataException('Invalid vCard date-time string: '.$date);
        }

        $parts = [
            'year' => null,
            'month' => null,
            'date' => null,
            'hour' => null,
            'minute' => null,
            'second' => null,
            'timezone' => null,
        ];

        // The $valueDateTime expression has a bug with (?J) so we simulate it.
        $parts['date0'] = &$parts['date'];
        $parts['date1'] = &$parts['date'];
        $parts['date2'] = &$parts['date'];
        $parts['month0'] = &$parts['month'];
        $parts['month1'] = &$parts['month'];
        $parts['year0'] = &$parts['year'];

        foreach ($parts as $part => &$value) {
            if (!empty($matches[$part])) {
                $value = $matches[$part];
            }
        }

        unset($parts['date0']);
        unset($parts['date1']);
        unset($parts['date2']);
        unset($parts['month0']);
        unset($parts['month1']);
        unset($parts['year0']);

        return $parts;
    }
}
