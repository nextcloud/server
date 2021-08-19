<?php

namespace Sabre\VObject;

/**
 * Time zone name translation.
 *
 * This file translates well-known time zone names into "Olson database" time zone names.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Frank Edelhaeuser (fedel@users.sourceforge.net)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class TimeZoneUtil
{
    public static $map = null;

    /**
     * List of microsoft exchange timezone ids.
     *
     * Source: http://msdn.microsoft.com/en-us/library/aa563018(loband).aspx
     */
    public static $microsoftExchangeMap = [
        0 => 'UTC',
        31 => 'Africa/Casablanca',

        // Insanely, id #2 is used for both Europe/Lisbon, and Europe/Sarajevo.
        // I'm not even kidding.. We handle this special case in the
        // getTimeZone method.
        2 => 'Europe/Lisbon',
        1 => 'Europe/London',
        4 => 'Europe/Berlin',
        6 => 'Europe/Prague',
        3 => 'Europe/Paris',
        69 => 'Africa/Luanda', // This was a best guess
        7 => 'Europe/Athens',
        5 => 'Europe/Bucharest',
        49 => 'Africa/Cairo',
        50 => 'Africa/Harare',
        59 => 'Europe/Helsinki',
        27 => 'Asia/Jerusalem',
        26 => 'Asia/Baghdad',
        74 => 'Asia/Kuwait',
        51 => 'Europe/Moscow',
        56 => 'Africa/Nairobi',
        25 => 'Asia/Tehran',
        24 => 'Asia/Muscat', // Best guess
        54 => 'Asia/Baku',
        48 => 'Asia/Kabul',
        58 => 'Asia/Yekaterinburg',
        47 => 'Asia/Karachi',
        23 => 'Asia/Calcutta',
        62 => 'Asia/Kathmandu',
        46 => 'Asia/Almaty',
        71 => 'Asia/Dhaka',
        66 => 'Asia/Colombo',
        61 => 'Asia/Rangoon',
        22 => 'Asia/Bangkok',
        64 => 'Asia/Krasnoyarsk',
        45 => 'Asia/Shanghai',
        63 => 'Asia/Irkutsk',
        21 => 'Asia/Singapore',
        73 => 'Australia/Perth',
        75 => 'Asia/Taipei',
        20 => 'Asia/Tokyo',
        72 => 'Asia/Seoul',
        70 => 'Asia/Yakutsk',
        19 => 'Australia/Adelaide',
        44 => 'Australia/Darwin',
        18 => 'Australia/Brisbane',
        76 => 'Australia/Sydney',
        43 => 'Pacific/Guam',
        42 => 'Australia/Hobart',
        68 => 'Asia/Vladivostok',
        41 => 'Asia/Magadan',
        17 => 'Pacific/Auckland',
        40 => 'Pacific/Fiji',
        67 => 'Pacific/Tongatapu',
        29 => 'Atlantic/Azores',
        53 => 'Atlantic/Cape_Verde',
        30 => 'America/Noronha',
         8 => 'America/Sao_Paulo', // Best guess
        32 => 'America/Argentina/Buenos_Aires',
        60 => 'America/Godthab',
        28 => 'America/St_Johns',
         9 => 'America/Halifax',
        33 => 'America/Caracas',
        65 => 'America/Santiago',
        35 => 'America/Bogota',
        10 => 'America/New_York',
        34 => 'America/Indiana/Indianapolis',
        55 => 'America/Guatemala',
        11 => 'America/Chicago',
        37 => 'America/Mexico_City',
        36 => 'America/Edmonton',
        38 => 'America/Phoenix',
        12 => 'America/Denver', // Best guess
        13 => 'America/Los_Angeles', // Best guess
        14 => 'America/Anchorage',
        15 => 'Pacific/Honolulu',
        16 => 'Pacific/Midway',
        39 => 'Pacific/Kwajalein',
    ];

    /**
     * This method will try to find out the correct timezone for an iCalendar
     * date-time value.
     *
     * You must pass the contents of the TZID parameter, as well as the full
     * calendar.
     *
     * If the lookup fails, this method will return the default PHP timezone
     * (as configured using date_default_timezone_set, or the date.timezone ini
     * setting).
     *
     * Alternatively, if $failIfUncertain is set to true, it will throw an
     * exception if we cannot accurately determine the timezone.
     *
     * @param string                  $tzid
     * @param Sabre\VObject\Component $vcalendar
     *
     * @return \DateTimeZone
     */
    public static function getTimeZone($tzid, Component $vcalendar = null, $failIfUncertain = false)
    {
        // First we will just see if the tzid is a support timezone identifier.
        //
        // The only exception is if the timezone starts with (. This is to
        // handle cases where certain microsoft products generate timezone
        // identifiers that for instance look like:
        //
        // (GMT+01.00) Sarajevo/Warsaw/Zagreb
        //
        // Since PHP 5.5.10, the first bit will be used as the timezone and
        // this method will return just GMT+01:00. This is wrong, because it
        // doesn't take DST into account.
        if ('(' !== $tzid[0]) {
            // PHP has a bug that logs PHP warnings even it shouldn't:
            // https://bugs.php.net/bug.php?id=67881
            //
            // That's why we're checking if we'll be able to successfully instantiate
            // \DateTimeZone() before doing so. Otherwise we could simply instantiate
            // and catch the exception.
            $tzIdentifiers = \DateTimeZone::listIdentifiers();

            try {
                if (
                    (in_array($tzid, $tzIdentifiers)) ||
                    (preg_match('/^GMT(\+|-)([0-9]{4})$/', $tzid, $matches)) ||
                    (in_array($tzid, self::getIdentifiersBC()))
                ) {
                    return new \DateTimeZone($tzid);
                }
            } catch (\Exception $e) {
            }
        }

        self::loadTzMaps();

        // Next, we check if the tzid is somewhere in our tzid map.
        if (isset(self::$map[$tzid])) {
            return new \DateTimeZone(self::$map[$tzid]);
        }

        // Some Microsoft products prefix the offset first, so let's strip that off
        // and see if it is our tzid map.  We don't want to check for this first just
        // in case there are overrides in our tzid map.
        if (preg_match('/^\((UTC|GMT)(\+|\-)[\d]{2}\:[\d]{2}\) (.*)/', $tzid, $matches)) {
            $tzidAlternate = $matches[3];
            if (isset(self::$map[$tzidAlternate])) {
                return new \DateTimeZone(self::$map[$tzidAlternate]);
            }
        }

        // Maybe the author was hyper-lazy and just included an offset. We
        // support it, but we aren't happy about it.
        if (preg_match('/^GMT(\+|-)([0-9]{4})$/', $tzid, $matches)) {
            // Note that the path in the source will never be taken from PHP 5.5.10
            // onwards. PHP 5.5.10 supports the "GMT+0100" style of format, so it
            // already gets returned early in this function. Once we drop support
            // for versions under PHP 5.5.10, this bit can be taken out of the
            // source.
            // @codeCoverageIgnoreStart
            return new \DateTimeZone('Etc/GMT'.$matches[1].ltrim(substr($matches[2], 0, 2), '0'));
            // @codeCoverageIgnoreEnd
        }

        if ($vcalendar) {
            // If that didn't work, we will scan VTIMEZONE objects
            foreach ($vcalendar->select('VTIMEZONE') as $vtimezone) {
                if ((string) $vtimezone->TZID === $tzid) {
                    // Some clients add 'X-LIC-LOCATION' with the olson name.
                    if (isset($vtimezone->{'X-LIC-LOCATION'})) {
                        $lic = (string) $vtimezone->{'X-LIC-LOCATION'};

                        // Libical generators may specify strings like
                        // "SystemV/EST5EDT". For those we must remove the
                        // SystemV part.
                        if ('SystemV/' === substr($lic, 0, 8)) {
                            $lic = substr($lic, 8);
                        }

                        return self::getTimeZone($lic, null, $failIfUncertain);
                    }
                    // Microsoft may add a magic number, which we also have an
                    // answer for.
                    if (isset($vtimezone->{'X-MICROSOFT-CDO-TZID'})) {
                        $cdoId = (int) $vtimezone->{'X-MICROSOFT-CDO-TZID'}->getValue();

                        // 2 can mean both Europe/Lisbon and Europe/Sarajevo.
                        if (2 === $cdoId && false !== strpos((string) $vtimezone->TZID, 'Sarajevo')) {
                            return new \DateTimeZone('Europe/Sarajevo');
                        }

                        if (isset(self::$microsoftExchangeMap[$cdoId])) {
                            return new \DateTimeZone(self::$microsoftExchangeMap[$cdoId]);
                        }
                    }
                }
            }
        }

        if ($failIfUncertain) {
            throw new \InvalidArgumentException('We were unable to determine the correct PHP timezone for tzid: '.$tzid);
        }

        // If we got all the way here, we default to UTC.
        return new \DateTimeZone(date_default_timezone_get());
    }

    /**
     * This method will load in all the tz mapping information, if it's not yet
     * done.
     */
    public static function loadTzMaps()
    {
        if (!is_null(self::$map)) {
            return;
        }

        self::$map = array_merge(
            include __DIR__.'/timezonedata/windowszones.php',
            include __DIR__.'/timezonedata/lotuszones.php',
            include __DIR__.'/timezonedata/exchangezones.php',
            include __DIR__.'/timezonedata/php-workaround.php'
        );
    }

    /**
     * This method returns an array of timezone identifiers, that are supported
     * by DateTimeZone(), but not returned by DateTimeZone::listIdentifiers().
     *
     * We're not using DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC) because:
     * - It's not supported by some PHP versions as well as HHVM.
     * - It also returns identifiers, that are invalid values for new DateTimeZone() on some PHP versions.
     * (See timezonedata/php-bc.php and timezonedata php-workaround.php)
     *
     * @return array
     */
    public static function getIdentifiersBC()
    {
        return include __DIR__.'/timezonedata/php-bc.php';
    }
}
