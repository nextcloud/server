<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Polyfill\Intl\Grapheme as p;

if (extension_loaded('intl')) {
    return;
}

if (\PHP_VERSION_ID >= 80000) {
    return require __DIR__.'/bootstrap80.php';
}

if (!defined('GRAPHEME_EXTR_COUNT')) {
    define('GRAPHEME_EXTR_COUNT', 0);
}
if (!defined('GRAPHEME_EXTR_MAXBYTES')) {
    define('GRAPHEME_EXTR_MAXBYTES', 1);
}
if (!defined('GRAPHEME_EXTR_MAXCHARS')) {
    define('GRAPHEME_EXTR_MAXCHARS', 2);
}

if (!function_exists('grapheme_extract')) {
    function grapheme_extract($haystack, $size, $type = 0, $start = 0, &$next = 0) { return p\Grapheme::grapheme_extract($haystack, $size, $type, $start, $next); }
}
if (!function_exists('grapheme_stripos')) {
    function grapheme_stripos($haystack, $needle, $offset = 0) { return p\Grapheme::grapheme_stripos($haystack, $needle, $offset); }
}
if (!function_exists('grapheme_stristr')) {
    function grapheme_stristr($haystack, $needle, $beforeNeedle = false) { return p\Grapheme::grapheme_stristr($haystack, $needle, $beforeNeedle); }
}
if (!function_exists('grapheme_strlen')) {
    function grapheme_strlen($input) { return p\Grapheme::grapheme_strlen($input); }
}
if (!function_exists('grapheme_strpos')) {
    function grapheme_strpos($haystack, $needle, $offset = 0) { return p\Grapheme::grapheme_strpos($haystack, $needle, $offset); }
}
if (!function_exists('grapheme_strripos')) {
    function grapheme_strripos($haystack, $needle, $offset = 0) { return p\Grapheme::grapheme_strripos($haystack, $needle, $offset); }
}
if (!function_exists('grapheme_strrpos')) {
    function grapheme_strrpos($haystack, $needle, $offset = 0) { return p\Grapheme::grapheme_strrpos($haystack, $needle, $offset); }
}
if (!function_exists('grapheme_strstr')) {
    function grapheme_strstr($haystack, $needle, $beforeNeedle = false) { return p\Grapheme::grapheme_strstr($haystack, $needle, $beforeNeedle); }
}
if (!function_exists('grapheme_substr')) {
    function grapheme_substr($string, $offset, $length = null) { return p\Grapheme::grapheme_substr($string, $offset, $length); }
}
