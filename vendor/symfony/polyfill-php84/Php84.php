<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Php84;

/**
 * @author Ayesh Karunaratne <ayesh@aye.sh>
 * @author Pierre Ambroise <pierre27.ambroise@gmail.com>
 *
 * @internal
 */
final class Php84
{
    public static function mb_ucfirst(string $string, ?string $encoding = null): string
    {
        if (null === $encoding) {
            $encoding = mb_internal_encoding();
        }

        try {
            $validEncoding = @mb_check_encoding('', $encoding);
        } catch (\ValueError $e) {
            throw new \ValueError(sprintf('mb_ucfirst(): Argument #2 ($encoding) must be a valid encoding, "%s" given', $encoding));
        }

        // BC for PHP 7.3 and lower
        if (!$validEncoding) {
            throw new \ValueError(sprintf('mb_ucfirst(): Argument #2 ($encoding) must be a valid encoding, "%s" given', $encoding));
        }

        $firstChar = mb_substr($string, 0, 1, $encoding);
        $firstChar = mb_convert_case($firstChar, \MB_CASE_TITLE, $encoding);

        return $firstChar.mb_substr($string, 1, null, $encoding);
    }

    public static function mb_lcfirst(string $string, ?string $encoding = null): string
    {
        if (null === $encoding) {
            $encoding = mb_internal_encoding();
        }

        try {
            $validEncoding = @mb_check_encoding('', $encoding);
        } catch (\ValueError $e) {
            throw new \ValueError(sprintf('mb_lcfirst(): Argument #2 ($encoding) must be a valid encoding, "%s" given', $encoding));
        }

        // BC for PHP 7.3 and lower
        if (!$validEncoding) {
            throw new \ValueError(sprintf('mb_lcfirst(): Argument #2 ($encoding) must be a valid encoding, "%s" given', $encoding));
        }

        $firstChar = mb_substr($string, 0, 1, $encoding);
        $firstChar = mb_convert_case($firstChar, \MB_CASE_LOWER, $encoding);

        return $firstChar.mb_substr($string, 1, null, $encoding);
    }

    public static function array_find(array $array, callable $callback)
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return null;
    }

    public static function array_find_key(array $array, callable $callback)
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $key;
            }
        }

        return null;
    }

    public static function array_any(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }

        return false;
    }

    public static function array_all(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if (!$callback($value, $key)) {
                return false;
            }
        }

        return true;
    }

    public static function fpow(float $num, float $exponent): float
    {
        return $num ** $exponent;
    }

    public static function mb_trim(string $string, ?string $characters = null, ?string $encoding = null): string
    {
        return self::mb_internal_trim('{^[%s]+|[%1$s]+$}Du', $string, $characters, $encoding, __FUNCTION__);
    }

    public static function mb_ltrim(string $string, ?string $characters = null, ?string $encoding = null): string
    {
        return self::mb_internal_trim('{^[%s]+}Du', $string, $characters, $encoding, __FUNCTION__);
    }

    public static function mb_rtrim(string $string, ?string $characters = null, ?string $encoding = null): string
    {
        return self::mb_internal_trim('{[%s]+$}Du', $string, $characters, $encoding, __FUNCTION__);
    }

    private static function mb_internal_trim(string $regex, string $string, ?string $characters, ?string $encoding, string $function): string
    {
        if (null === $encoding) {
            $encoding = mb_internal_encoding();
        }

        try {
            $validEncoding = @mb_check_encoding('', $encoding);
        } catch (\ValueError $e) {
            throw new \ValueError(sprintf('%s(): Argument #3 ($encoding) must be a valid encoding, "%s" given', $function, $encoding));
        }

        // BC for PHP 7.3 and lower
        if (!$validEncoding) {
            throw new \ValueError(sprintf('%s(): Argument #3 ($encoding) must be a valid encoding, "%s" given', $function, $encoding));
        }

        if ('' === $characters) {
            return null === $encoding ? $string : mb_convert_encoding($string, $encoding);
        }

        if ('UTF-8' === $encoding || \in_array(strtolower($encoding), ['utf-8', 'utf8'], true)) {
            $encoding = 'UTF-8';
        }

        $string = mb_convert_encoding($string, 'UTF-8', $encoding);

        if (null !== $characters) {
            $characters = mb_convert_encoding($characters, 'UTF-8', $encoding);
        }

        if (null === $characters) {
            $characters = "\\0 \f\n\r\t\v\u{00A0}\u{1680}\u{2000}\u{2001}\u{2002}\u{2003}\u{2004}\u{2005}\u{2006}\u{2007}\u{2008}\u{2009}\u{200A}\u{2028}\u{2029}\u{202F}\u{205F}\u{3000}\u{0085}\u{180E}";
        } else {
            $characters = preg_quote($characters);
        }

        $string = preg_replace(sprintf($regex, $characters), '', $string);

        if ('UTF-8' === $encoding) {
            return $string;
        }

        return mb_convert_encoding($string, $encoding, 'UTF-8');
    }
}
