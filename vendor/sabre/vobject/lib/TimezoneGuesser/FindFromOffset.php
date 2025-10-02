<?php

declare(strict_types=1);

namespace Sabre\VObject\TimezoneGuesser;

use DateTimeZone;

/**
 * Some clients add 'X-LIC-LOCATION' with the olson name.
 */
class FindFromOffset implements TimezoneFinder
{
    public function find(string $tzid, bool $failIfUncertain = false): ?DateTimeZone
    {
        // Maybe the author was hyper-lazy and just included an offset. We
        // support it, but we aren't happy about it.
        if (preg_match('/^GMT(\+|-)([0-9]{4})$/', $tzid, $matches)) {
            // Note that the path in the source will never be taken from PHP 5.5.10
            // onwards. PHP 5.5.10 supports the "GMT+0100" style of format, so it
            // already gets returned early in this function. Once we drop support
            // for versions under PHP 5.5.10, this bit can be taken out of the
            // source.
            // @codeCoverageIgnoreStart
            return new DateTimeZone('Etc/GMT'.$matches[1].ltrim(substr($matches[2], 0, 2), '0'));
            // @codeCoverageIgnoreEnd
        }

        return null;
    }
}
