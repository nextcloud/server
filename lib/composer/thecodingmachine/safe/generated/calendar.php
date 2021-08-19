<?php

namespace Safe;

use Safe\Exceptions\CalendarException;

/**
 * This function will return a Unix timestamp corresponding to the
 * Julian Day given in jday or FALSE if
 * jday is outside of the allowed range. The time returned is
 * UTC.
 *
 * @param int $jday A julian day number between 2440588 and 106751993607888
 * on 64bit systems, or between 2440588 and 2465443 on 32bit systems.
 * @return int The unix timestamp for the start (midnight, not noon) of the given Julian day.
 * @throws CalendarException
 *
 */
function jdtounix(int $jday): int
{
    error_clear_last();
    $result = \jdtounix($jday);
    if ($result === false) {
        throw CalendarException::createFromPhpError();
    }
    return $result;
}
