<?php

namespace Sabre\VObject\TimezoneGuesser;

use DateTimeZone;

interface TimezoneFinder
{
    public function find(string $tzid, bool $failIfUncertain = false): ?DateTimeZone;
}
