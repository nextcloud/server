<?php
declare(strict_types=1);
namespace ParagonIE\ConstantTime;

use RangeException;
use TypeError;

/**
 *  Copyright (c) 2016 - 2022 Paragon Initiative Enterprises.
 *  Copyright (c) 2014 Steve "Sc00bz" Thomas (steve at tobtu dot com)
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *  SOFTWARE.
 */

/**
 * Class Hex
 * @package ParagonIE\ConstantTime
 */
abstract class Hex implements EncoderInterface
{
    /**
     * Convert a binary string into a hexadecimal string without cache-timing
     * leaks
     *
     * @param string $binString (raw binary)
     * @return string
     * @throws TypeError
     */
    public static function encode(string $binString): string
    {
        $hex = '';
        $len = Binary::safeStrlen($binString);
        for ($i = 0; $i < $len; ++$i) {
            /** @var array<int, int> $chunk */
            $chunk = \unpack('C', $binString[$i]);
            $c = $chunk[1] & 0xf;
            $b = $chunk[1] >> 4;

            $hex .= \pack(
                'CC',
                (87 + $b + ((($b - 10) >> 8) & ~38)),
                (87 + $c + ((($c - 10) >> 8) & ~38))
            );
        }
        return $hex;
    }

    /**
     * Convert a binary string into a hexadecimal string without cache-timing
     * leaks, returning uppercase letters (as per RFC 4648)
     *
     * @param string $binString (raw binary)
     * @return string
     * @throws TypeError
     */
    public static function encodeUpper(string $binString): string
    {
        $hex = '';
        $len = Binary::safeStrlen($binString);

        for ($i = 0; $i < $len; ++$i) {
            /** @var array<int, int> $chunk */
            $chunk = \unpack('C', $binString[$i]);
            $c = $chunk[1] & 0xf;
            $b = $chunk[1] >> 4;

            $hex .= \pack(
                'CC',
                (55 + $b + ((($b - 10) >> 8) & ~6)),
                (55 + $c + ((($c - 10) >> 8) & ~6))
            );
        }
        return $hex;
    }

    /**
     * Convert a hexadecimal string into a binary string without cache-timing
     * leaks
     *
     * @param string $encodedString
     * @param bool $strictPadding
     * @return string (raw binary)
     * @throws RangeException
     */
    public static function decode(
        string $encodedString,
        bool $strictPadding = false
    ): string {
        $hex_pos = 0;
        $bin = '';
        $c_acc = 0;
        $hex_len = Binary::safeStrlen($encodedString);
        $state = 0;
        if (($hex_len & 1) !== 0) {
            if ($strictPadding) {
                throw new RangeException(
                    'Expected an even number of hexadecimal characters'
                );
            } else {
                $encodedString = '0' . $encodedString;
                ++$hex_len;
            }
        }

        /** @var array<int, int> $chunk */
        $chunk = \unpack('C*', $encodedString);
        while ($hex_pos < $hex_len) {
            ++$hex_pos;
            $c = $chunk[$hex_pos];
            $c_num = $c ^ 48;
            $c_num0 = ($c_num - 10) >> 8;
            $c_alpha = ($c & ~32) - 55;
            $c_alpha0 = (($c_alpha - 10) ^ ($c_alpha - 16)) >> 8;

            if (($c_num0 | $c_alpha0) === 0) {
                throw new RangeException(
                    'Expected hexadecimal character'
                );
            }
            $c_val = ($c_num0 & $c_num) | ($c_alpha & $c_alpha0);
            if ($state === 0) {
                $c_acc = $c_val * 16;
            } else {
                $bin .= \pack('C', $c_acc | $c_val);
            }
            $state ^= 1;
        }
        return $bin;
    }
}
