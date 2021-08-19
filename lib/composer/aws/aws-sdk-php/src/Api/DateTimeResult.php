<?php

namespace Aws\Api;

use Aws\Api\Parser\Exception\ParserException;
use Exception;

/**
 * DateTime overrides that make DateTime work more seamlessly as a string,
 * with JSON documents, and with JMESPath.
 */
class DateTimeResult extends \DateTime implements \JsonSerializable
{
    /**
     * Create a new DateTimeResult from a unix timestamp.
     * The Unix epoch (or Unix time or POSIX time or Unix
     * timestamp) is the number of seconds that have elapsed since
     * January 1, 1970 (midnight UTC/GMT).
     * @param $unixTimestamp
     *
     * @return DateTimeResult
     * @throws Exception
     */
    public static function fromEpoch($unixTimestamp)
    {
        return new self(gmdate('c', $unixTimestamp));
    }

    /**
     * @param $iso8601Timestamp
     *
     * @return DateTimeResult
     */
    public static function fromISO8601($iso8601Timestamp)
    {
        if (is_numeric($iso8601Timestamp) || !is_string($iso8601Timestamp)) {
            throw new ParserException('Invalid timestamp value passed to DateTimeResult::fromISO8601');
        }
        return new DateTimeResult($iso8601Timestamp);
    }

    /**
     * Create a new DateTimeResult from an unknown timestamp.
     *
     * @param $timestamp
     *
     * @return DateTimeResult
     * @throws ParserException|Exception
     */
    public static function fromTimestamp($timestamp, $expectedFormat = null)
    {
        if (empty($timestamp)) {
            return self::fromEpoch(0);
        }

        if (!(is_string($timestamp) || is_numeric($timestamp))) {
            throw new ParserException('Invalid timestamp value passed to DateTimeResult::fromTimestamp');
        }

        try {
            if ($expectedFormat == 'iso8601') {
                try {
                    return self::fromISO8601($timestamp);
                } catch (Exception $exception) {
                    return self::fromEpoch($timestamp);
                }
            } else if ($expectedFormat == 'unixTimestamp') {
                try {
                    return self::fromEpoch($timestamp);
                } catch (Exception $exception) {
                    return self::fromISO8601($timestamp);
                }
            } else if (\Aws\is_valid_epoch($timestamp)) {
                return self::fromEpoch($timestamp);
            }
            return self::fromISO8601($timestamp);
        } catch (Exception $exception) {
            throw new ParserException('Invalid timestamp value passed to DateTimeResult::fromTimestamp');
        }
    }

    /**
     * Serialize the DateTimeResult as an ISO 8601 date string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->format('c');
    }

    /**
     * Serialize the date as an ISO 8601 date when serializing as JSON.
     *
     * @return mixed|string
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return (string) $this;
    }
}
