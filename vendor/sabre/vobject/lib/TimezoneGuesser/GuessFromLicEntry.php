<?php

declare(strict_types=1);

namespace Sabre\VObject\TimezoneGuesser;

use DateTimeZone;
use Sabre\VObject\Component\VTimeZone;
use Sabre\VObject\TimeZoneUtil;

/**
 * Some clients add 'X-LIC-LOCATION' with the olson name.
 */
class GuessFromLicEntry implements TimezoneGuesser
{
    public function guess(VTimeZone $vtimezone, bool $failIfUncertain = false): ?DateTimeZone
    {
        if (!isset($vtimezone->{'X-LIC-LOCATION'})) {
            return null;
        }

        $lic = (string) $vtimezone->{'X-LIC-LOCATION'};

        // Libical generators may specify strings like
        // "SystemV/EST5EDT". For those we must remove the
        // SystemV part.
        if ('SystemV/' === substr($lic, 0, 8)) {
            $lic = substr($lic, 8);
        }

        return TimeZoneUtil::getTimeZone($lic, null, $failIfUncertain);
    }
}
