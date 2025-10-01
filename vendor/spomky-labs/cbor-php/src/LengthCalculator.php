<?php

declare(strict_types=1);

namespace CBOR;

use Brick\Math\BigInteger;
use InvalidArgumentException;
use function chr;
use function count;
use const STR_PAD_LEFT;

final class LengthCalculator
{
    /**
     * @return array{int, null|string}
     */
    public static function getLengthOfString(string $data): array
    {
        $length = mb_strlen($data, '8bit');

        return self::computeLength($length);
    }

    /**
     * @param array<int|string, mixed> $data
     *
     * @return array{int, null|string}
     */
    public static function getLengthOfArray(array $data): array
    {
        $length = count($data);

        return self::computeLength($length);
    }

    /**
     * @return array{int, null|string}
     */
    private static function computeLength(int $length): array
    {
        return match (true) {
            $length <= 23 => [$length, null],
            $length <= 0xFF => [24, chr($length)],
            $length <= 0xFFFF => [25, self::hex2bin(dechex($length))],
            $length <= 0xFFFFFFFF => [26, self::hex2bin(dechex($length))],
            BigInteger::of($length)->isLessThan(BigInteger::fromBase('FFFFFFFFFFFFFFFF', 16)) => [
                27,
                self::hex2bin(dechex($length)),
            ],
            default => [31, null],
        };
    }

    private static function hex2bin(string $data): string
    {
        $data = str_pad($data, (int) (2 ** ceil(log(mb_strlen($data, '8bit'), 2))), '0', STR_PAD_LEFT);
        $result = hex2bin($data);
        if ($result === false) {
            throw new InvalidArgumentException('Unable to convert the data');
        }

        return $result;
    }
}
