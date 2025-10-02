<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Feature;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use RuntimeException;
use UnexpectedValueException;

/**
 * Helper trait for classes employing date and time handling.
 */
trait DateTimeHelper
{
    /**
     * Create DateTime object from time string and timezone.
     *
     * @param null|string $time Time string, default to 'now'
     * @param null|string $tz Timezone, default if omitted
     */
    private static function createDateTime(?string $time = null, ?string $tz = null): DateTimeImmutable
    {
        if (! isset($time)) {
            $time = 'now';
        }
        if (! isset($tz)) {
            $tz = date_default_timezone_get();
        }
        try {
            $dt = new DateTimeImmutable($time, self::createTimeZone($tz));
            return self::roundDownFractionalSeconds($dt);
        } catch (Exception $e) {
            throw new RuntimeException('Failed to create DateTime:', 0, $e);
        }
    }

    /**
     * Rounds a \DateTimeImmutable value such that fractional seconds are removed.
     */
    private static function roundDownFractionalSeconds(DateTimeImmutable $dt): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dt->format('Y-m-d H:i:s'), $dt->getTimezone());
    }

    /**
     * Create DateTimeZone object from string.
     */
    private static function createTimeZone(string $tz): DateTimeZone
    {
        try {
            return new DateTimeZone($tz);
        } catch (Exception $e) {
            throw new UnexpectedValueException('Invalid timezone.', 0, $e);
        }
    }
}
