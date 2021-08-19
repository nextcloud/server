<?php

/**
 * This file is part of the ramsey/uuid library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Ramsey\Uuid\Converter;

use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Time;

/**
 * A time converter converts timestamps into representations that may be used
 * in UUIDs
 *
 * @psalm-immutable
 */
interface TimeConverterInterface
{
    /**
     * Uses the provided seconds and micro-seconds to calculate the count of
     * 100-nanosecond intervals since UTC 00:00:00.00, 15 October 1582, for
     * RFC 4122 variant UUIDs
     *
     * @link http://tools.ietf.org/html/rfc4122#section-4.2.2 RFC 4122, § 4.2.2: Generation Details
     *
     * @param string $seconds A string representation of the number of seconds
     *     since the Unix epoch for the time to calculate
     * @param string $microseconds A string representation of the micro-seconds
     *     associated with the time to calculate
     *
     * @return Hexadecimal The full UUID timestamp as a Hexadecimal value
     *
     * @psalm-pure
     */
    public function calculateTime(string $seconds, string $microseconds): Hexadecimal;

    /**
     * Converts a timestamp extracted from a UUID to a Unix timestamp
     *
     * @param Hexadecimal $uuidTimestamp A hexadecimal representation of a UUID
     *     timestamp; a UUID timestamp is a count of 100-nanosecond intervals
     *     since UTC 00:00:00.00, 15 October 1582.
     *
     * @return Time An instance of {@see Time}
     *
     * @psalm-pure
     */
    public function convertTime(Hexadecimal $uuidTimestamp): Time;
}
