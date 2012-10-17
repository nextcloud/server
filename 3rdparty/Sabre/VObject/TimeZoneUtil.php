<?php

namespace Sabre\VObject;

/**
 * Time zone name translation
 *
 * This file translates well-known time zone names into "Olson database" time zone names.
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Frank Edelhaeuser (fedel@users.sourceforge.net)
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class TimeZoneUtil {

    public static $map = array(

        // from http://unicode.org/repos/cldr-tmp/trunk/diff/supplemental/zone_tzid.html
        // snapshot taken on 2012/01/16

        // windows
        'AUS Central Standard Time'=>'Australia/Darwin',
        'AUS Eastern Standard Time'=>'Australia/Sydney',
        'Afghanistan Standard Time'=>'Asia/Kabul',
        'Alaskan Standard Time'=>'America/Anchorage',
        'Arab Standard Time'=>'Asia/Riyadh',
        'Arabian Standard Time'=>'Asia/Dubai',
        'Arabic Standard Time'=>'Asia/Baghdad',
        'Argentina Standard Time'=>'America/Buenos_Aires',
        'Armenian Standard Time'=>'Asia/Yerevan',
        'Atlantic Standard Time'=>'America/Halifax',
        'Azerbaijan Standard Time'=>'Asia/Baku',
        'Azores Standard Time'=>'Atlantic/Azores',
        'Bangladesh Standard Time'=>'Asia/Dhaka',
        'Canada Central Standard Time'=>'America/Regina',
        'Cape Verde Standard Time'=>'Atlantic/Cape_Verde',
        'Caucasus Standard Time'=>'Asia/Yerevan',
        'Cen. Australia Standard Time'=>'Australia/Adelaide',
        'Central America Standard Time'=>'America/Guatemala',
        'Central Asia Standard Time'=>'Asia/Almaty',
        'Central Brazilian Standard Time'=>'America/Cuiaba',
        'Central Europe Standard Time'=>'Europe/Budapest',
        'Central European Standard Time'=>'Europe/Warsaw',
        'Central Pacific Standard Time'=>'Pacific/Guadalcanal',
        'Central Standard Time'=>'America/Chicago',
        'Central Standard Time (Mexico)'=>'America/Mexico_City',
        'China Standard Time'=>'Asia/Shanghai',
        'Dateline Standard Time'=>'Etc/GMT+12',
        'E. Africa Standard Time'=>'Africa/Nairobi',
        'E. Australia Standard Time'=>'Australia/Brisbane',
        'E. Europe Standard Time'=>'Europe/Minsk',
        'E. South America Standard Time'=>'America/Sao_Paulo',
        'Eastern Standard Time'=>'America/New_York',
        'Egypt Standard Time'=>'Africa/Cairo',
        'Ekaterinburg Standard Time'=>'Asia/Yekaterinburg',
        'FLE Standard Time'=>'Europe/Kiev',
        'Fiji Standard Time'=>'Pacific/Fiji',
        'GMT Standard Time'=>'Europe/London',
        'GTB Standard Time'=>'Europe/Istanbul',
        'Georgian Standard Time'=>'Asia/Tbilisi',
        'Greenland Standard Time'=>'America/Godthab',
        'Greenwich Standard Time'=>'Atlantic/Reykjavik',
        'Hawaiian Standard Time'=>'Pacific/Honolulu',
        'India Standard Time'=>'Asia/Calcutta',
        'Iran Standard Time'=>'Asia/Tehran',
        'Israel Standard Time'=>'Asia/Jerusalem',
        'Jordan Standard Time'=>'Asia/Amman',
        'Kamchatka Standard Time'=>'Asia/Kamchatka',
        'Korea Standard Time'=>'Asia/Seoul',
        'Magadan Standard Time'=>'Asia/Magadan',
        'Mauritius Standard Time'=>'Indian/Mauritius',
        'Mexico Standard Time'=>'America/Mexico_City',
        'Mexico Standard Time 2'=>'America/Chihuahua',
        'Mid-Atlantic Standard Time'=>'Etc/GMT+2',
        'Middle East Standard Time'=>'Asia/Beirut',
        'Montevideo Standard Time'=>'America/Montevideo',
        'Morocco Standard Time'=>'Africa/Casablanca',
        'Mountain Standard Time'=>'America/Denver',
        'Mountain Standard Time (Mexico)'=>'America/Chihuahua',
        'Myanmar Standard Time'=>'Asia/Rangoon',
        'N. Central Asia Standard Time'=>'Asia/Novosibirsk',
        'Namibia Standard Time'=>'Africa/Windhoek',
        'Nepal Standard Time'=>'Asia/Katmandu',
        'New Zealand Standard Time'=>'Pacific/Auckland',
        'Newfoundland Standard Time'=>'America/St_Johns',
        'North Asia East Standard Time'=>'Asia/Irkutsk',
        'North Asia Standard Time'=>'Asia/Krasnoyarsk',
        'Pacific SA Standard Time'=>'America/Santiago',
        'Pacific Standard Time'=>'America/Los_Angeles',
        'Pacific Standard Time (Mexico)'=>'America/Santa_Isabel',
        'Pakistan Standard Time'=>'Asia/Karachi',
        'Paraguay Standard Time'=>'America/Asuncion',
        'Romance Standard Time'=>'Europe/Paris',
        'Russian Standard Time'=>'Europe/Moscow',
        'SA Eastern Standard Time'=>'America/Cayenne',
        'SA Pacific Standard Time'=>'America/Bogota',
        'SA Western Standard Time'=>'America/La_Paz',
        'SE Asia Standard Time'=>'Asia/Bangkok',
        'Samoa Standard Time'=>'Pacific/Apia',
        'Singapore Standard Time'=>'Asia/Singapore',
        'South Africa Standard Time'=>'Africa/Johannesburg',
        'Sri Lanka Standard Time'=>'Asia/Colombo',
        'Syria Standard Time'=>'Asia/Damascus',
        'Taipei Standard Time'=>'Asia/Taipei',
        'Tasmania Standard Time'=>'Australia/Hobart',
        'Tokyo Standard Time'=>'Asia/Tokyo',
        'Tonga Standard Time'=>'Pacific/Tongatapu',
        'US Eastern Standard Time'=>'America/Indianapolis',
        'US Mountain Standard Time'=>'America/Phoenix',
        'UTC'=>'Etc/GMT',
        'UTC+12'=>'Etc/GMT-12',
        'UTC-02'=>'Etc/GMT+2',
        'UTC-11'=>'Etc/GMT+11',
        'Ulaanbaatar Standard Time'=>'Asia/Ulaanbaatar',
        'Venezuela Standard Time'=>'America/Caracas',
        'Vladivostok Standard Time'=>'Asia/Vladivostok',
        'W. Australia Standard Time'=>'Australia/Perth',
        'W. Central Africa Standard Time'=>'Africa/Lagos',
        'W. Europe Standard Time'=>'Europe/Berlin',
        'West Asia Standard Time'=>'Asia/Tashkent',
        'West Pacific Standard Time'=>'Pacific/Port_Moresby',
        'Yakutsk Standard Time'=>'Asia/Yakutsk',

        // Microsoft exchange timezones
        // Source:
        // http://msdn.microsoft.com/en-us/library/ms988620%28v=exchg.65%29.aspx
        //
        // Correct timezones deduced with help from:
        // http://en.wikipedia.org/wiki/List_of_tz_database_time_zones
        'Universal Coordinated Time' => 'UTC',
        'Casablanca, Monrovia' => 'Africa/Casablanca',
        'Greenwich Mean Time: Dublin, Edinburgh, Lisbon, London' => 'Europe/Lisbon',
        'Greenwich Mean Time; Dublin, Edinburgh, London' =>  'Europe/London',
        'Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna' => 'Europe/Berlin',
        'Belgrade, Pozsony, Budapest, Ljubljana, Prague' => 'Europe/Prague',
        'Brussels, Copenhagen, Madrid, Paris' => 'Europe/Paris',
        'Paris, Madrid, Brussels, Copenhagen' => 'Europe/Paris',
        'Prague, Central Europe' => 'Europe/Prague',
        'Sarajevo, Skopje, Sofija, Vilnius, Warsaw, Zagreb' => 'Europe/Sarajevo',
        'West Central Africa' => 'Africa/Luanda', // This was a best guess
        'Athens, Istanbul, Minsk' => 'Europe/Athens',
        'Bucharest' => 'Europe/Bucharest',
        'Cairo' => 'Africa/Cairo',
        'Harare, Pretoria' => 'Africa/Harare',
        'Helsinki, Riga, Tallinn' => 'Europe/Helsinki',
        'Israel, Jerusalem Standard Time' => 'Asia/Jerusalem',
        'Baghdad' => 'Asia/Baghdad',
        'Arab, Kuwait, Riyadh' => 'Asia/Kuwait',
        'Moscow, St. Petersburg, Volgograd' => 'Europe/Moscow',
        'East Africa, Nairobi' => 'Africa/Nairobi',
        'Tehran' => 'Asia/Tehran',
        'Abu Dhabi, Muscat' => 'Asia/Muscat', // Best guess
        'Baku, Tbilisi, Yerevan' => 'Asia/Baku',
        'Kabul' => 'Asia/Kabul',
        'Ekaterinburg' => 'Asia/Yekaterinburg',
        'Islamabad, Karachi, Tashkent' => 'Asia/Karachi',
        'Kolkata, Chennai, Mumbai, New Delhi, India Standard Time' => 'Asia/Calcutta',
        'Kathmandu, Nepal' => 'Asia/Kathmandu',
        'Almaty, Novosibirsk, North Central Asia' => 'Asia/Almaty',
        'Astana, Dhaka' => 'Asia/Dhaka',
        'Sri Jayawardenepura, Sri Lanka' => 'Asia/Colombo',
        'Rangoon' => 'Asia/Rangoon',
        'Bangkok, Hanoi, Jakarta' => 'Asia/Bangkok',
        'Krasnoyarsk' => 'Asia/Krasnoyarsk',
        'Beijing, Chongqing, Hong Kong SAR, Urumqi' => 'Asia/Shanghai',
        'Irkutsk, Ulaan Bataar' => 'Asia/Irkutsk',
        'Kuala Lumpur, Singapore' => 'Asia/Singapore',
        'Perth, Western Australia' => 'Australia/Perth',
        'Taipei' => 'Asia/Taipei',
        'Osaka, Sapporo, Tokyo' => 'Asia/Tokyo',
        'Seoul, Korea Standard time' => 'Asia/Seoul',
        'Yakutsk' => 'Asia/Yakutsk',
        'Adelaide, Central Australia' => 'Australia/Adelaide',
        'Darwin' => 'Australia/Darwin',
        'Brisbane, East Australia' => 'Australia/Brisbane',
        'Canberra, Melbourne, Sydney, Hobart (year 2000 only)' => 'Australia/Sydney',
        'Guam, Port Moresby' => 'Pacific/Guam',
        'Hobart, Tasmania' => 'Australia/Hobart',
        'Vladivostok' => 'Asia/Vladivostok',
        'Magadan, Solomon Is., New Caledonia' => 'Asia/Magadan',
        'Auckland, Wellington' => 'Pacific/Auckland',
        'Fiji Islands, Kamchatka, Marshall Is.' => 'Pacific/Fiji',
        'Nuku\'alofa, Tonga' => 'Pacific/Tongatapu',
        'Azores' => 'Atlantic/Azores',
        'Cape Verde Is.' => 'Atlantic/Cape_Verde',
        'Mid-Atlantic' => 'America/Noronha',
        'Brasilia' => 'America/Sao_Paulo', // Best guess
        'Buenos Aires' => 'America/Argentina/Buenos_Aires',
        'Greenland' => 'America/Godthab',
        'Newfoundland' => 'America/St_Johns',
        'Atlantic Time (Canada)' => 'America/Halifax',
        'Caracas, La Paz' => 'America/Caracas',
        'Santiago' => 'America/Santiago',
        'Bogota, Lima, Quito' => 'America/Bogota',
        'Eastern Time (US & Canada)' => 'America/New_York',
        'Indiana (East)' => 'America/Indiana/Indianapolis',
        'Central America' => 'America/Guatemala',
        'Central Time (US & Canada)' => 'America/Chicago',
        'Mexico City, Tegucigalpa' => 'America/Mexico_City',
        'Saskatchewan' => 'America/Edmonton',
        'Arizona' => 'America/Phoenix',
        'Mountain Time (US & Canada)' => 'America/Denver', // Best guess
        'Pacific Time (US & Canada); Tijuana' => 'America/Los_Angeles', // Best guess
        'Alaska' => 'America/Anchorage',
        'Hawaii' => 'Pacific/Honolulu',
        'Midway Island, Samoa' => 'Pacific/Midway',
        'Eniwetok, Kwajalein, Dateline Time' => 'Pacific/Kwajalein',

    );

    public static $microsoftExchangeMap = array(
        0  => 'UTC',
        31 => 'Africa/Casablanca',
        2  => 'Europe/Lisbon',
        1  => 'Europe/London',
        4  => 'Europe/Berlin',
        6  => 'Europe/Prague',
        3  => 'Europe/Paris',
        69 => 'Africa/Luanda', // This was a best guess
        7  => 'Europe/Athens',
        5  => 'Europe/Bucharest',
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
    );

    /**
     * This method will try to find out the correct timezone for an iCalendar
     * date-time value.
     *
     * You must pass the contents of the TZID parameter, as well as the full
     * calendar.
     *
     * If the lookup fails, this method will return UTC.
     *
     * @param string $tzid
     * @param Sabre\VObject\Component $vcalendar
     * @return DateTimeZone
     */
    static public function getTimeZone($tzid, Component $vcalendar = null) {

        // First we will just see if the tzid is a support timezone identifier.
        try {
            return new \DateTimeZone($tzid);
        } catch (\Exception $e) {
        }

        // Next, we check if the tzid is somewhere in our tzid map.
        if (isset(self::$map[$tzid])) {
            return new \DateTimeZone(self::$map[$tzid]);
        }

        if ($vcalendar) {

            // If that didn't work, we will scan VTIMEZONE objects
            foreach($vcalendar->select('VTIMEZONE') as $vtimezone) {

                if ((string)$vtimezone->TZID === $tzid) {

                    // Some clients add 'X-LIC-LOCATION' with the olson name.
                    if (isset($vtimezone->{'X-LIC-LOCATION'})) {
                        try {
                            return new \DateTimeZone($vtimezone->{'X-LIC-LOCATION'});
                        } catch (\Exception $e) {
                        }

                    }
                    // Microsoft may add a magic number, which we also have an
                    // answer for.
                    if (isset($vtimezone->{'X-MICROSOFT-CDO-TZID'})) {
                        if (isset(self::$microsoftExchangeMap[(int)$vtimezone->{'X-MICROSOFT-CDO-TZID'}->value])) {
                            return new \DateTimeZone(self::$microsoftExchangeMap[(int)$vtimezone->{'X-MICROSOFT-CDO-TZID'}->value]);
                        }
                    }
                }

            }

        }

        // If we got all the way here, we default to UTC.
        return new \DateTimeZone(date_default_timezone_get());


    }


}
