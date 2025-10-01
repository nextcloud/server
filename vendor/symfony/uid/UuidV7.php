<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Uid;

/**
 * A v7 UUID is lexicographically sortable and contains a 48-bit timestamp and 74 extra unique bits.
 *
 * Within the same millisecond, monotonicity is ensured by incrementing the random part by a random increment.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class UuidV7 extends Uuid implements TimeBasedUidInterface
{
    protected const TYPE = 7;

    private static string $time = '';
    private static array $rand = [];
    private static string $seed;
    private static array $seedParts;
    private static int $seedIndex = 0;

    public function __construct(?string $uuid = null)
    {
        if (null === $uuid) {
            $this->uid = static::generate();
        } else {
            parent::__construct($uuid, true);
        }
    }

    public function getDateTime(): \DateTimeImmutable
    {
        $time = substr($this->uid, 0, 8).substr($this->uid, 9, 4);
        $time = \PHP_INT_SIZE >= 8 ? (string) hexdec($time) : BinaryUtil::toBase(hex2bin($time), BinaryUtil::BASE10);

        if (4 > \strlen($time)) {
            $time = '000'.$time;
        }

        return \DateTimeImmutable::createFromFormat('U.v', substr_replace($time, '.', -3, 0));
    }

    public static function generate(?\DateTimeInterface $time = null): string
    {
        if (null === $mtime = $time) {
            $time = microtime(false);
            $time = substr($time, 11).substr($time, 2, 3);
        } elseif (0 > $time = $time->format('Uv')) {
            throw new \InvalidArgumentException('The timestamp must be positive.');
        }

        if ($time > self::$time || (null !== $mtime && $time !== self::$time)) {
            randomize:
            self::$rand = unpack('n*', isset(self::$seed) ? random_bytes(10) : self::$seed = random_bytes(16));
            self::$rand[1] &= 0x03FF;
            self::$time = $time;
        } else {
            // Within the same ms, we increment the rand part by a random 24-bit number.
            // Instead of getting this number from random_bytes(), which is slow, we get
            // it by sha512-hashing self::$seed. This produces 64 bytes of entropy,
            // which we need to split in a list of 24-bit numbers. unpack() first splits
            // them into 16 x 32-bit numbers; we take the first byte of each of these
            // numbers to get 5 extra 24-bit numbers. Then, we consume those numbers
            // one-by-one and run this logic every 21 iterations.
            // self::$rand holds the random part of the UUID, split into 5 x 16-bit
            // numbers for x86 portability. We increment this random part by the next
            // 24-bit number in the self::$seedParts list and decrement self::$seedIndex.

            if (!self::$seedIndex) {
                $s = unpack('l*', self::$seed = hash('sha512', self::$seed, true));
                $s[] = ($s[1] >> 8 & 0xFF0000) | ($s[2] >> 16 & 0xFF00) | ($s[3] >> 24 & 0xFF);
                $s[] = ($s[4] >> 8 & 0xFF0000) | ($s[5] >> 16 & 0xFF00) | ($s[6] >> 24 & 0xFF);
                $s[] = ($s[7] >> 8 & 0xFF0000) | ($s[8] >> 16 & 0xFF00) | ($s[9] >> 24 & 0xFF);
                $s[] = ($s[10] >> 8 & 0xFF0000) | ($s[11] >> 16 & 0xFF00) | ($s[12] >> 24 & 0xFF);
                $s[] = ($s[13] >> 8 & 0xFF0000) | ($s[14] >> 16 & 0xFF00) | ($s[15] >> 24 & 0xFF);
                self::$seedParts = $s;
                self::$seedIndex = 21;
            }

            self::$rand[5] = 0xFFFF & $carry = self::$rand[5] + 1 + (self::$seedParts[self::$seedIndex--] & 0xFFFFFF);
            self::$rand[4] = 0xFFFF & $carry = self::$rand[4] + ($carry >> 16);
            self::$rand[3] = 0xFFFF & $carry = self::$rand[3] + ($carry >> 16);
            self::$rand[2] = 0xFFFF & $carry = self::$rand[2] + ($carry >> 16);
            self::$rand[1] += $carry >> 16;

            if (0xFC00 & self::$rand[1]) {
                if (\PHP_INT_SIZE >= 8 || 10 > \strlen($time = self::$time)) {
                    $time = (string) (1 + $time);
                } elseif ('999999999' === $mtime = substr($time, -9)) {
                    $time = (1 + substr($time, 0, -9)).'000000000';
                } else {
                    $time = substr_replace($time, str_pad(++$mtime, 9, '0', \STR_PAD_LEFT), -9);
                }

                goto randomize;
            }

            $time = self::$time;
        }

        if (\PHP_INT_SIZE >= 8) {
            $time = dechex($time);
        } else {
            $time = bin2hex(BinaryUtil::fromBase($time, BinaryUtil::BASE10));
        }

        return substr_replace(sprintf('%012s-%04x-%04x-%04x%04x%04x',
            $time,
            0x7000 | (self::$rand[1] << 2) | (self::$rand[2] >> 14),
            0x8000 | (self::$rand[2] & 0x3FFF),
            self::$rand[3],
            self::$rand[4],
            self::$rand[5],
        ), '-', 8, 0);
    }
}
