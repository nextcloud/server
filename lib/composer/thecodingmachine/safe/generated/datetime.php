<?php

namespace Safe;

use Safe\Exceptions\DatetimeException;

/**
 * Returns associative array with detailed info about given date/time.
 *
 * @param string $format Format accepted by DateTime::createFromFormat.
 * @param string $datetime String representing the date/time.
 * @return array Returns associative array with detailed info about given date/time.
 * @throws DatetimeException
 *
 */
function date_parse_from_format(string $format, string $datetime): array
{
    error_clear_last();
    $result = \date_parse_from_format($format, $datetime);
    if ($result === false) {
        throw DatetimeException::createFromPhpError();
    }
    return $result;
}


/**
 *
 *
 * @param string $datetime Date/time in format accepted by
 * DateTimeImmutable::__construct.
 * @return array Returns array with information about the parsed date/time
 * on success.
 * @throws DatetimeException
 *
 */
function date_parse(string $datetime): array
{
    error_clear_last();
    $result = \date_parse($datetime);
    if ($result === false) {
        throw DatetimeException::createFromPhpError();
    }
    return $result;
}


/**
 *
 *
 * @param int $timestamp Unix timestamp.
 * @param float $latitude Latitude in degrees.
 * @param float $longitude Longitude in degrees.
 * @return array Returns array on success.
 * The structure of the array is detailed in the following list:
 *
 *
 *
 * sunrise
 *
 *
 * The timestamp of the sunrise (zenith angle = 90°35').
 *
 *
 *
 *
 * sunset
 *
 *
 * The timestamp of the sunset (zenith angle = 90°35').
 *
 *
 *
 *
 * transit
 *
 *
 * The timestamp when the sun is at its zenith, i.e. has reached its topmost
 * point.
 *
 *
 *
 *
 * civil_twilight_begin
 *
 *
 * The start of the civil dawn (zenith angle = 96°). It ends at sunrise.
 *
 *
 *
 *
 * civil_twilight_end
 *
 *
 * The end of the civil dusk (zenith angle = 96°). It starts at sunset.
 *
 *
 *
 *
 * nautical_twilight_begin
 *
 *
 * The start of the nautical dawn (zenith angle = 102°). It ends at
 * civil_twilight_begin.
 *
 *
 *
 *
 * nautical_twilight_end
 *
 *
 * The end of the nautical dusk (zenith angle = 102°). It starts at
 * civil_twilight_end.
 *
 *
 *
 *
 * astronomical_twilight_begin
 *
 *
 * The start of the astronomical dawn (zenith angle = 108°). It ends at
 * nautical_twilight_begin.
 *
 *
 *
 *
 * astronomical_twilight_end
 *
 *
 * The end of the astronomical dusk (zenith angle = 108°). It starts at
 * nautical_twilight_end.
 *
 *
 *
 *
 *
 * The values of the array elements are either UNIX timestamps, FALSE if the
 * sun is below the respective zenith for the whole day, or TRUE if the sun is
 * above the respective zenith for the whole day.
 * @throws DatetimeException
 *
 */
function date_sun_info(int $timestamp, float $latitude, float $longitude): array
{
    error_clear_last();
    $result = \date_sun_info($timestamp, $latitude, $longitude);
    if ($result === false) {
        throw DatetimeException::createFromPhpError();
    }
    return $result;
}


/**
 * date_sunrise returns the sunrise time for a given
 * day (specified as a timestamp) and location.
 *
 * @param int $timestamp The timestamp of the day from which the sunrise
 * time is taken.
 * @param int $returnFormat
 * returnFormat constants
 *
 *
 *
 * constant
 * description
 * example
 *
 *
 *
 *
 * SUNFUNCS_RET_STRING
 * returns the result as string
 * 16:46
 *
 *
 * SUNFUNCS_RET_DOUBLE
 * returns the result as float
 * 16.78243132
 *
 *
 * SUNFUNCS_RET_TIMESTAMP
 * returns the result as integer (timestamp)
 * 1095034606
 *
 *
 *
 *
 * @param float $latitude Defaults to North, pass in a negative value for South.
 * See also: date.default_latitude
 * @param float $longitude Defaults to East, pass in a negative value for West.
 * See also: date.default_longitude
 * @param float $zenith zenith is the angle between the center of the sun
 * and a line perpendicular to earth's surface. It defaults to
 * date.sunrise_zenith
 *
 * Common zenith angles
 *
 *
 *
 * Angle
 * Description
 *
 *
 *
 *
 * 90°50'
 * Sunrise: the point where the sun becomes visible.
 *
 *
 * 96°
 * Civil twilight: conventionally used to signify the start of dawn.
 *
 *
 * 102°
 * Nautical twilight: the point at which the horizon starts being visible at sea.
 *
 *
 * 108°
 * Astronomical twilight: the point at which the sun starts being the source of any illumination.
 *
 *
 *
 *
 * @param float $utcOffset Specified in hours.
 * The utcOffset is ignored, if
 * returnFormat is
 * SUNFUNCS_RET_TIMESTAMP.
 * @return mixed Returns the sunrise time in a specified returnFormat on
 * success. One potential reason for failure is that the
 * sun does not rise at all, which happens inside the polar circles for part of
 * the year.
 * @throws DatetimeException
 *
 */
function date_sunrise(int $timestamp, int $returnFormat = SUNFUNCS_RET_STRING, float $latitude = null, float $longitude = null, float $zenith = null, float $utcOffset = 0)
{
    error_clear_last();
    if ($utcOffset !== 0) {
        $result = \date_sunrise($timestamp, $returnFormat, $latitude, $longitude, $zenith, $utcOffset);
    } elseif ($zenith !== null) {
        $result = \date_sunrise($timestamp, $returnFormat, $latitude, $longitude, $zenith);
    } elseif ($longitude !== null) {
        $result = \date_sunrise($timestamp, $returnFormat, $latitude, $longitude);
    } elseif ($latitude !== null) {
        $result = \date_sunrise($timestamp, $returnFormat, $latitude);
    } else {
        $result = \date_sunrise($timestamp, $returnFormat);
    }
    if ($result === false) {
        throw DatetimeException::createFromPhpError();
    }
    return $result;
}


/**
 * date_sunset returns the sunset time for a given
 * day (specified as a timestamp) and location.
 *
 * @param int $timestamp The timestamp of the day from which the sunset
 * time is taken.
 * @param int $returnFormat
 * returnFormat constants
 *
 *
 *
 * constant
 * description
 * example
 *
 *
 *
 *
 * SUNFUNCS_RET_STRING
 * returns the result as string
 * 16:46
 *
 *
 * SUNFUNCS_RET_DOUBLE
 * returns the result as float
 * 16.78243132
 *
 *
 * SUNFUNCS_RET_TIMESTAMP
 * returns the result as integer (timestamp)
 * 1095034606
 *
 *
 *
 *
 * @param float $latitude Defaults to North, pass in a negative value for South.
 * See also: date.default_latitude
 * @param float $longitude Defaults to East, pass in a negative value for West.
 * See also: date.default_longitude
 * @param float $zenith zenith is the angle between the center of the sun
 * and a line perpendicular to earth's surface. It defaults to
 * date.sunset_zenith
 *
 * Common zenith angles
 *
 *
 *
 * Angle
 * Description
 *
 *
 *
 *
 * 90°50'
 * Sunset: the point where the sun becomes invisible.
 *
 *
 * 96°
 * Civil twilight: conventionally used to signify the end of dusk.
 *
 *
 * 102°
 * Nautical twilight: the point at which the horizon ends being visible at sea.
 *
 *
 * 108°
 * Astronomical twilight: the point at which the sun ends being the source of any illumination.
 *
 *
 *
 *
 * @param float $utcOffset Specified in hours.
 * The utcOffset is ignored, if
 * returnFormat is
 * SUNFUNCS_RET_TIMESTAMP.
 * @return mixed Returns the sunset time in a specified returnFormat on
 * success. One potential reason for failure is that the
 * sun does not set at all, which happens inside the polar circles for part of
 * the year.
 * @throws DatetimeException
 *
 */
function date_sunset(int $timestamp, int $returnFormat = SUNFUNCS_RET_STRING, float $latitude = null, float $longitude = null, float $zenith = null, float $utcOffset = 0)
{
    error_clear_last();
    if ($utcOffset !== 0) {
        $result = \date_sunset($timestamp, $returnFormat, $latitude, $longitude, $zenith, $utcOffset);
    } elseif ($zenith !== null) {
        $result = \date_sunset($timestamp, $returnFormat, $latitude, $longitude, $zenith);
    } elseif ($longitude !== null) {
        $result = \date_sunset($timestamp, $returnFormat, $latitude, $longitude);
    } elseif ($latitude !== null) {
        $result = \date_sunset($timestamp, $returnFormat, $latitude);
    } else {
        $result = \date_sunset($timestamp, $returnFormat);
    }
    if ($result === false) {
        throw DatetimeException::createFromPhpError();
    }
    return $result;
}


/**
 * Returns a string formatted according to the given format string using the
 * given integer timestamp or the current time
 * if no timestamp is given.  In other words, timestamp
 * is optional and defaults to the value of time.
 *
 * @param string $format Format accepted by DateTimeInterface::format.
 * @param int $timestamp The optional timestamp parameter is an
 * integer Unix timestamp that defaults to the current
 * local time if a timestamp is not given. In other
 * words, it defaults to the value of time.
 * @return string Returns a formatted date string. If a non-numeric value is used for
 * timestamp, FALSE is returned and an
 * E_WARNING level error is emitted.
 * @throws DatetimeException
 *
 */
function date(string $format, int $timestamp = null): string
{
    error_clear_last();
    if ($timestamp !== null) {
        $result = \date($format, $timestamp);
    } else {
        $result = \date($format);
    }
    if ($result === false) {
        throw DatetimeException::createFromPhpError();
    }
    return $result;
}


/**
 * Identical to the date function except that
 * the time returned is Greenwich Mean Time (GMT).
 *
 * @param string $format The format of the outputted date string. See the formatting
 * options for the date function.
 * @param int $timestamp The optional timestamp parameter is an
 * integer Unix timestamp that defaults to the current
 * local time if a timestamp is not given. In other
 * words, it defaults to the value of time.
 * @return string Returns a formatted date string. If a non-numeric value is used for
 * timestamp, FALSE is returned and an
 * E_WARNING level error is emitted.
 * @throws DatetimeException
 *
 */
function gmdate(string $format, int $timestamp = null): string
{
    error_clear_last();
    if ($timestamp !== null) {
        $result = \gmdate($format, $timestamp);
    } else {
        $result = \gmdate($format);
    }
    if ($result === false) {
        throw DatetimeException::createFromPhpError();
    }
    return $result;
}


/**
 * Returns the Unix timestamp corresponding to the arguments
 * given. This timestamp is a long integer containing the number of
 * seconds between the Unix Epoch (January 1 1970 00:00:00 GMT) and the time
 * specified.
 *
 * Arguments may be left out in order from right to left; any
 * arguments thus omitted will be set to the current value according
 * to the local date and time.
 *
 * @param int $hour The number of the hour relative to the start of the day determined by
 * month, day and year.
 * Negative values reference the hour before midnight of the day in question.
 * Values greater than 23 reference the appropriate hour in the following day(s).
 * @param int $minute The number of the minute relative to the start of the hour.
 * Negative values reference the minute in the previous hour.
 * Values greater than 59 reference the appropriate minute in the following hour(s).
 * @param int $second The number of seconds relative to the start of the minute.
 * Negative values reference the second in the previous minute.
 * Values greater than 59 reference the appropriate second in the following minute(s).
 * @param int $month The number of the month relative to the end of the previous year.
 * Values 1 to 12 reference the normal calendar months of the year in question.
 * Values less than 1 (including negative values) reference the months in the previous year in reverse order, so 0 is December, -1 is November, etc.
 * Values greater than 12 reference the appropriate month in the following year(s).
 * @param int $day The number of the day relative to the end of the previous month.
 * Values 1 to 28, 29, 30 or 31 (depending upon the month) reference the normal days in the relevant month.
 * Values less than 1 (including negative values) reference the days in the previous month, so 0 is the last day of the previous month, -1 is the day before that, etc.
 * Values greater than the number of days in the relevant month reference the appropriate day in the following month(s).
 * @param int $year The number of the year, may be a two or four digit value,
 * with values between 0-69 mapping to 2000-2069 and 70-100 to
 * 1970-2000. On systems where time_t is a 32bit signed integer, as
 * most common today, the valid range for year
 * is somewhere between 1901 and 2038. However, before PHP 5.1.0 this
 * range was limited from 1970 to 2038 on some systems (e.g. Windows).
 * @return int mktime returns the Unix timestamp of the arguments
 * given.
 * If the arguments are invalid, the function returns FALSE (before PHP 5.1
 * it returned -1).
 * @throws DatetimeException
 *
 */
function mktime(int $hour = null, int $minute = null, int $second = null, int $month = null, int $day = null, int $year = null): int
{
    error_clear_last();
    if ($year !== null) {
        $result = \mktime($hour, $minute, $second, $month, $day, $year);
    } elseif ($day !== null) {
        $result = \mktime($hour, $minute, $second, $month, $day);
    } elseif ($month !== null) {
        $result = \mktime($hour, $minute, $second, $month);
    } elseif ($second !== null) {
        $result = \mktime($hour, $minute, $second);
    } elseif ($minute !== null) {
        $result = \mktime($hour, $minute);
    } elseif ($hour !== null) {
        $result = \mktime($hour);
    } else {
        $result = \mktime();
    }
    if ($result === false) {
        throw DatetimeException::createFromPhpError();
    }
    return $result;
}


/**
 * strptime returns an array with the
 * date parsed.
 *
 * Month and weekday names and other language dependent strings respect the
 * current locale set with setlocale (LC_TIME).
 *
 * @param string $date The string to parse (e.g. returned from strftime).
 * @param string $format The format used in date (e.g. the same as
 * used in strftime). Note that some of the format
 * options available to strftime may not have any
 * effect within strptime; the exact subset that are
 * supported will vary based on the operating system and C library in
 * use.
 *
 * For more information about the format options, read the
 * strftime page.
 * @return array Returns an array.
 *
 *
 * The following parameters are returned in the array
 *
 *
 *
 * parameters
 * Description
 *
 *
 *
 *
 * "tm_sec"
 * Seconds after the minute (0-61)
 *
 *
 * "tm_min"
 * Minutes after the hour (0-59)
 *
 *
 * "tm_hour"
 * Hour since midnight (0-23)
 *
 *
 * "tm_mday"
 * Day of the month (1-31)
 *
 *
 * "tm_mon"
 * Months since January (0-11)
 *
 *
 * "tm_year"
 * Years since 1900
 *
 *
 * "tm_wday"
 * Days since Sunday (0-6)
 *
 *
 * "tm_yday"
 * Days since January 1 (0-365)
 *
 *
 * "unparsed"
 * the date part which was not
 * recognized using the specified format
 *
 *
 *
 *
 * @throws DatetimeException
 *
 */
function strptime(string $date, string $format): array
{
    error_clear_last();
    $result = \strptime($date, $format);
    if ($result === false) {
        throw DatetimeException::createFromPhpError();
    }
    return $result;
}


/**
 * Each parameter of this function uses the default time zone unless a
 * time zone is specified in that parameter.  Be careful not to use
 * different time zones in each parameter unless that is intended.
 * See date_default_timezone_get on the various
 * ways to define the default time zone.
 *
 * @param string $datetime A date/time string. Valid formats are explained in Date and Time Formats.
 * @param int $now The timestamp which is used as a base for the calculation of relative
 * dates.
 * @return int Returns a timestamp on success, FALSE otherwise. Previous to PHP 5.1.0,
 * this function would return -1 on failure.
 * @throws DatetimeException
 *
 */
function strtotime(string $datetime, int $now = null): int
{
    error_clear_last();
    if ($now !== null) {
        $result = \strtotime($datetime, $now);
    } else {
        $result = \strtotime($datetime);
    }
    if ($result === false) {
        throw DatetimeException::createFromPhpError();
    }
    return $result;
}


/**
 *
 *
 * @param string $abbr Time zone abbreviation.
 * @param int $utcOffset Offset from GMT in seconds. Defaults to -1 which means that first found
 * time zone corresponding to abbr is returned.
 * Otherwise exact offset is searched and only if not found then the first
 * time zone with any offset is returned.
 * @param int $isDST Daylight saving time indicator. Defaults to -1, which means that
 * whether the time zone has daylight saving or not is not taken into
 * consideration when searching. If this is set to 1, then the
 * utcOffset is assumed to be an offset with
 * daylight saving in effect; if 0, then utcOffset
 * is assumed to be an offset without daylight saving in effect. If
 * abbr doesn't exist then the time zone is
 * searched solely by the utcOffset and
 * isDST.
 * @return string Returns time zone name on success.
 * @throws DatetimeException
 *
 */
function timezone_name_from_abbr(string $abbr, int $utcOffset = -1, int $isDST = -1): string
{
    error_clear_last();
    $result = \timezone_name_from_abbr($abbr, $utcOffset, $isDST);
    if ($result === false) {
        throw DatetimeException::createFromPhpError();
    }
    return $result;
}
