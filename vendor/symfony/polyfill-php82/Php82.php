<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Php82;

/**
 * @author Alexander M. Turek <me@derrabus.de>
 * @author Greg Roach <greg@subaqua.co.uk>
 *
 * @internal
 */
class Php82
{
    /**
     * Determines if a string matches the ODBC quoting rules.
     *
     * A valid quoted string begins with a '{', ends with a '}', and has no '}'
     * inside of the string that aren't repeated (as to be escaped).
     *
     * These rules are what .NET also follows.
     *
     * @see https://github.com/php/php-src/blob/838f6bffff6363a204a2597cbfbaad1d7ee3f2b6/main/php_odbc_utils.c#L31-L57
     */
    public static function odbc_connection_string_is_quoted(string $str): bool
    {
        if ('' === $str || '{' !== $str[0]) {
            return false;
        }

        /* Check for } that aren't doubled up or at the end of the string */
        $length = \strlen($str) - 1;
        for ($i = 0; $i < $length; ++$i) {
            if ('}' !== $str[$i]) {
                continue;
            }

            if ('}' !== $str[++$i]) {
                return $i === $length;
            }
        }

        return true;
    }

    /**
     * Determines if a value for a connection string should be quoted.
     *
     * The ODBC specification mentions:
     * "Because of connection string and initialization file grammar, keywords and
     * attribute values that contain the characters []{}(),;?*=!@ not enclosed
     * with braces should be avoided."
     *
     * Note that it assumes that the string is *not* already quoted. You should
     * check beforehand.
     *
     * @see https://github.com/php/php-src/blob/838f6bffff6363a204a2597cbfbaad1d7ee3f2b6/main/php_odbc_utils.c#L59-L73
     */
    public static function odbc_connection_string_should_quote(string $str): bool
    {
        return false !== strpbrk($str, '[]{}(),;?*=!@');
    }

    public static function odbc_connection_string_quote(string $str): string
    {
        return '{'.str_replace('}', '}}', $str).'}';
    }

    /**
     * Implementation closely based on the original C code - including the GOTOs
     * and pointer-style string access.
     *
     * @see https://github.com/php/php-src/blob/master/Zend/zend_ini.c
     */
    public static function ini_parse_quantity(string $value): int
    {
        // Avoid dependency on ctype_space()
        $ctype_space = " \t\v\r\n\f";

        $str = 0;
        $str_end = \strlen($value);
        $digits = $str;
        $overflow = false;

        /* Ignore leading whitespace, but keep it for error messages. */
        while ($digits < $str_end && false !== strpos($ctype_space, $value[$digits])) {
            ++$digits;
        }

        /* Ignore trailing whitespace, but keep it for error messages. */
        while ($digits < $str_end && false !== strpos($ctype_space, $value[$str_end - 1])) {
            --$str_end;
        }

        if ($digits === $str_end) {
            return 0;
        }

        $is_negative = false;

        if ('+' === $value[$digits]) {
            ++$digits;
        } elseif ('-' === $value[$digits]) {
            $is_negative = true;
            ++$digits;
        }

        if ($value[$digits] < '0' || $value[$digits] > 9) {
            $message = sprintf(
                'Invalid quantity "%s": no valid leading digits, interpreting as "0" for backwards compatibility',
                self::escapeString($value)
            );

            trigger_error($message, \E_USER_WARNING);

            return 0;
        }

        $base = 10;
        $allowed_digits = '0123456789';

        if ('0' === $value[$digits] && ($digits + 1 === $str_end || false === strpos($allowed_digits, $value[$digits + 1]))) {
            if ($digits + 1 === $str_end) {
                return 0;
            }

            switch ($value[$digits + 1]) {
                case 'g':
                case 'G':
                case 'm':
                case 'M':
                case 'k':
                case 'K':
                    goto evaluation;
                case 'x':
                case 'X':
                    $base = 16;
                    $allowed_digits = '0123456789abcdefABCDEF';
                    break;
                case 'o':
                case 'O':
                    $base = 8;
                    $allowed_digits = '01234567';
                    break;
                case 'b':
                case 'B':
                    $base = 2;
                    $allowed_digits = '01';
                    break;
                default:
                    $message = sprintf(
                        'Invalid prefix "0%s", interpreting as "0" for backwards compatibility',
                        $value[$digits + 1]
                    );
                    trigger_error($message, \E_USER_WARNING);

                    return 0;
            }

            $digits += 2;
            if ($digits === $str_end) {
                $message = sprintf(
                    'Invalid quantity "%s": no digits after base prefix, interpreting as "0" for backwards compatibility',
                    self::escapeString($value)
                );
                trigger_error($message, \E_USER_WARNING);

                return 0;
            }

            $digits_consumed = $digits;
            /* Ignore leading whitespace. */
            while ($digits_consumed < $str_end && false !== strpos($ctype_space, $value[$digits_consumed])) {
                ++$digits_consumed;
            }
            if ($digits_consumed !== $str_end && ('+' === $value[$digits_consumed] || '-' === $value[$digits_consumed])) {
                ++$digits_consumed;
            }

            if ('0' === $value[$digits_consumed]) {
                /* Value is just 0 */
                if ($digits_consumed + 1 === $str_end) {
                    goto evaluation;
                }
                switch ($value[$digits_consumed + 1]) {
                    case 'x':
                    case 'X':
                    case 'o':
                    case 'O':
                    case 'b':
                    case 'B':
                        $digits_consumed += 2;
                        break;
                }
            }

            if ($digits !== $digits_consumed) {
                $message = sprintf(
                    'Invalid quantity "%s": no digits after base prefix, interpreting as "0" for backwards compatibility',
                    self::escapeString($value)
                );
                trigger_error($message, \E_USER_WARNING);

                return 0;
            }
        }

        evaluation:

        if (10 === $base && '0' === $value[$digits]) {
            $base = 8;
            $allowed_digits = '01234567';
        }

        while ($digits < $str_end && ' ' === $value[$digits]) {
            ++$digits;
        }

        if ($digits < $str_end && '+' === $value[$digits]) {
            ++$digits;
        } elseif ($digits < $str_end && '-' === $value[$digits]) {
            $is_negative = true;
            $overflow = true;
            ++$digits;
        }

        $digits_end = $digits;

        while ($digits_end < $str_end && false !== strpos($allowed_digits, $value[$digits_end])) {
            ++$digits_end;
        }

        $retval = base_convert(substr($value, $digits, $digits_end - $digits), $base, 10);

        if ($is_negative && '0' === $retval) {
            $is_negative = false;
            $overflow = false;
        }

        // Check for overflow - remember that -PHP_INT_MIN = 1 + PHP_INT_MAX
        if ($is_negative) {
            $signed_max = strtr((string) \PHP_INT_MIN, ['-' => '']);
        } else {
            $signed_max = (string) \PHP_INT_MAX;
        }

        $max_length = max(\strlen($retval), \strlen($signed_max));

        $tmp1 = str_pad($retval, $max_length, '0', \STR_PAD_LEFT);
        $tmp2 = str_pad($signed_max, $max_length, '0', \STR_PAD_LEFT);

        if ($tmp1 > $tmp2) {
            $retval = -1;
            $overflow = true;
        } elseif ($is_negative) {
            $retval = '-'.$retval;
        }

        $retval = (int) $retval;

        if ($digits_end === $digits) {
            $message = sprintf(
                'Invalid quantity "%s": no valid leading digits, interpreting as "0" for backwards compatibility',
                self::escapeString($value)
            );
            trigger_error($message, \E_USER_WARNING);

            return 0;
        }

        /* Allow for whitespace between integer portion and any suffix character */
        while ($digits_end < $str_end && false !== strpos($ctype_space, $value[$digits_end])) {
            ++$digits_end;
        }

        /* No exponent suffix. */
        if ($digits_end === $str_end) {
            goto end;
        }

        switch ($value[$str_end - 1]) {
            case 'g':
            case 'G':
                $shift = 30;
                break;
            case 'm':
            case 'M':
                $shift = 20;
                break;
            case 'k':
            case 'K':
                $shift = 10;
                break;
            default:
                /* Unknown suffix */
                $invalid = self::escapeString($value);
                $interpreted = self::escapeString(substr($value, $str, $digits_end - $str));
                $chr = self::escapeString($value[$str_end - 1]);

                $message = sprintf(
                    'Invalid quantity "%s": unknown multiplier "%s", interpreting as "%s" for backwards compatibility',
                    $invalid,
                    $chr,
                    $interpreted
                );

                trigger_error($message, \E_USER_WARNING);

                return $retval;
        }

        $factor = 1 << $shift;

        if (!$overflow) {
            if ($retval > 0) {
                $overflow = $retval > \PHP_INT_MAX / $factor;
            } else {
                $overflow = $retval < \PHP_INT_MIN / $factor;
            }
        }

        if (\is_float($retval * $factor)) {
            $overflow = true;
            $retval <<= $shift;
        } else {
            $retval *= $factor;
        }

        if ($digits_end !== $str_end - 1) {
            /* More than one character in suffix */
            $message = sprintf(
                'Invalid quantity "%s", interpreting as "%s%s" for backwards compatibility',
                self::escapeString($value),
                self::escapeString(substr($value, $str, $digits_end - $str)),
                self::escapeString($value[$str_end - 1])
            );
            trigger_error($message, \E_USER_WARNING);

            return $retval;
        }

        end:

        if ($overflow) {
            /* Not specifying the resulting value here because the caller may make
             * additional conversions. Not specifying the allowed range
             * because the caller may do narrower range checks. */
            $message = sprintf(
                'Invalid quantity "%s": value is out of range, using overflow result for backwards compatibility',
                self::escapeString($value)
            );
            trigger_error($message, \E_USER_WARNING);
        }

        return $retval;
    }

    /**
     * Escape the string to avoid null bytes and to make non-printable chars visible.
     */
    private static function escapeString(string $string): string
    {
        $escaped = '';

        for ($n = 0, $len = \strlen($string); $n < $len; ++$n) {
            $c = \ord($string[$n]);

            if ($c < 32 || '\\' === $string[$n] || $c > 126) {
                switch ($string[$n]) {
                    case "\n": $escaped .= '\\n'; break;
                    case "\r": $escaped .= '\\r'; break;
                    case "\t": $escaped .= '\\t'; break;
                    case "\f": $escaped .= '\\f'; break;
                    case "\v": $escaped .= '\\v'; break;
                    case '\\': $escaped .= '\\\\'; break;
                    case "\x1B": $escaped .= '\\e'; break;
                    default:
                        $escaped .= '\\x'.strtoupper(sprintf('%02x', $c));
                }
            } else {
                $escaped .= $string[$n];
            }
        }

        return $escaped;
    }
}
