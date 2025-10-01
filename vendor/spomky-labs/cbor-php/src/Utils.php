<?php

declare(strict_types=1);

namespace CBOR;

use Brick\Math\BigInteger;
use InvalidArgumentException;
use function is_string;

/**
 * @internal
 */
abstract class Utils
{
    public static function binToInt(string $value): int
    {
        return self::binToBigInteger($value)->toInt();
    }

    public static function binToBigInteger(string $value): BigInteger
    {
        return self::hexToBigInteger(bin2hex($value));
    }

    public static function hexToInt(string $value): int
    {
        return self::hexToBigInteger($value)->toInt();
    }

    public static function hexToBigInteger(string $value): BigInteger
    {
        return BigInteger::fromBase($value, 16);
    }

    public static function hexToString(string $value): string
    {
        return BigInteger::fromBase(bin2hex($value), 16)->toBase(10);
    }

    public static function decode(string $data): string
    {
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid data provided');
        }

        return $decoded;
    }

    /**
     * @param mixed|null $data
     */
    public static function assertString($data, ?string $message = null): void
    {
        if (! is_string($data)) {
            throw new InvalidArgumentException($message ?? '');
        }
    }
}
