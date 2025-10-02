<?php

declare(strict_types=1);

namespace Sabre\VObject\TimezoneGuesser;

use DateTimeZone;
use Exception;

/**
 * Some clients add 'X-LIC-LOCATION' with the olson name.
 */
class FindFromTimezoneIdentifier implements TimezoneFinder
{
    public function find(string $tzid, bool $failIfUncertain = false): ?DateTimeZone
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
        // doesn't take DST into account
        if (!isset($tzid[0])) {
            return null;
        }
        if ('(' === $tzid[0]) {
            return null;
        }
        // PHP has a bug that logs PHP warnings even it shouldn't:
        // https://bugs.php.net/bug.php?id=67881
        //
        // That's why we're checking if we'll be able to successfully instantiate
        // \DateTimeZone() before doing so. Otherwise we could simply instantiate
        // and catch the exception.
        $tzIdentifiers = DateTimeZone::listIdentifiers();

        try {
            if (
                (in_array($tzid, $tzIdentifiers)) ||
                (preg_match('/^GMT(\+|-)([0-9]{4})$/', $tzid, $matches)) ||
                (in_array($tzid, $this->getIdentifiersBC()))
            ) {
                return new DateTimeZone($tzid);
            }
        } catch (Exception $e) {
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
    private function getIdentifiersBC()
    {
        return include __DIR__.'/../timezonedata/php-bc.php';
    }
}
