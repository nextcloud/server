<?php

declare(strict_types=1);

namespace Sabre\VObject\TimezoneGuesser;

use DateTimeZone;
use Sabre\VObject\Component\VTimeZone;

class GuessFromMsTzId implements TimezoneGuesser
{
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

    public function guess(VTimeZone $vtimezone, bool $throwIfUnsure = false): ?DateTimeZone
    {
        // Microsoft may add a magic number, which we also have an
        // answer for.
        if (!isset($vtimezone->{'X-MICROSOFT-CDO-TZID'})) {
            return null;
        }
        $cdoId = (int) $vtimezone->{'X-MICROSOFT-CDO-TZID'}->getValue();

        // 2 can mean both Europe/Lisbon and Europe/Sarajevo.
        if (2 === $cdoId && false !== strpos((string) $vtimezone->TZID, 'Sarajevo')) {
            return new DateTimeZone('Europe/Sarajevo');
        }

        if (isset(self::$microsoftExchangeMap[$cdoId])) {
            return new DateTimeZone(self::$microsoftExchangeMap[$cdoId]);
        }

        return null;
    }
}
