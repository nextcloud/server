<?php

declare(strict_types=1);

namespace Sabre\VObject\TimezoneGuesser;

use DateTimeZone;

/**
 * Some clients add 'X-LIC-LOCATION' with the olson name.
 */
class FindFromTimezoneMap implements TimezoneFinder
{
    private $map = [];

    private $patterns = [
        '/^\((UTC|GMT)(\+|\-)[\d]{2}\:[\d]{2}\) (.*)/',
        '/^\((UTC|GMT)(\+|\-)[\d]{2}\.[\d]{2}\) (.*)/',
    ];

    public function find(string $tzid, bool $failIfUncertain = false): ?DateTimeZone
    {
        // Next, we check if the tzid is somewhere in our tzid map.
        if ($this->hasTzInMap($tzid)) {
            return new DateTimeZone($this->getTzFromMap($tzid));
        }

        // Some Microsoft products prefix the offset first, so let's strip that off
        // and see if it is our tzid map.  We don't want to check for this first just
        // in case there are overrides in our tzid map.
        foreach ($this->patterns as $pattern) {
            if (!preg_match($pattern, $tzid, $matches)) {
                continue;
            }
            $tzidAlternate = $matches[3];
            if ($this->hasTzInMap($tzidAlternate)) {
                return new DateTimeZone($this->getTzFromMap($tzidAlternate));
            }
        }

        return null;
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
    private function getTzMaps()
    {
        if ([] === $this->map) {
            $this->map = array_merge(
                include __DIR__.'/../timezonedata/windowszones.php',
                include __DIR__.'/../timezonedata/lotuszones.php',
                include __DIR__.'/../timezonedata/exchangezones.php',
                include __DIR__.'/../timezonedata/php-workaround.php'
            );
        }

        return $this->map;
    }

    private function getTzFromMap(string $tzid): string
    {
        return $this->getTzMaps()[$tzid];
    }

    private function hasTzInMap(string $tzid): bool
    {
        return isset($this->getTzMaps()[$tzid]);
    }
}
