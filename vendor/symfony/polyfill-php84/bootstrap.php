<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Polyfill\Php84 as p;

if (\PHP_VERSION_ID >= 80400) {
    return;
}

if (defined('CURL_VERSION_HTTP3') || PHP_VERSION_ID < 80200 && function_exists('curl_version') && curl_version()['version'] >= 0x074200) { // libcurl >= 7.66.0
    if (!defined('CURL_HTTP_VERSION_3')) {
        define('CURL_HTTP_VERSION_3', 30);
    }

    if (!defined('CURL_HTTP_VERSION_3ONLY') && defined('CURLOPT_SSH_HOST_PUBLIC_KEY_SHA256')) { // libcurl >= 7.80.0 (7.88 would be better but is slow to check)
        define('CURL_HTTP_VERSION_3ONLY', 31);
    }
}

if (!function_exists('array_find')) {
    function array_find(array $array, callable $callback) { return p\Php84::array_find($array, $callback); }
}

if (!function_exists('array_find_key')) {
    function array_find_key(array $array, callable $callback) { return p\Php84::array_find_key($array, $callback); }
}

if (!function_exists('array_any')) {
    function array_any(array $array, callable $callback): bool { return p\Php84::array_any($array, $callback); }
}

if (!function_exists('array_all')) {
    function array_all(array $array, callable $callback): bool { return p\Php84::array_all($array, $callback); }
}

if (!function_exists('fpow')) {
    function fpow(float $num, float $exponent): float { return p\Php84::fpow($num, $exponent); }
}

if (extension_loaded('mbstring')) {
    if (!function_exists('mb_ucfirst')) {
        function mb_ucfirst(string $string, ?string $encoding = null): string { return p\Php84::mb_ucfirst($string, $encoding); }
    }

    if (!function_exists('mb_lcfirst')) {
        function mb_lcfirst(string $string, ?string $encoding = null): string { return p\Php84::mb_lcfirst($string, $encoding); }
    }

    if (!function_exists('mb_trim')) {
        function mb_trim(string $string, ?string $characters = null, ?string $encoding = null): string { return p\Php84::mb_trim($string, $characters, $encoding); }
    }

    if (!function_exists('mb_ltrim')) {
        function mb_ltrim(string $string, ?string $characters = null, ?string $encoding = null): string { return p\Php84::mb_ltrim($string, $characters, $encoding); }
    }

    if (!function_exists('mb_rtrim')) {
        function mb_rtrim(string $string, ?string $characters = null, ?string $encoding = null): string { return p\Php84::mb_rtrim($string, $characters, $encoding); }
    }
}
