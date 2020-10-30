<?php declare(strict_types=1);

/**
 * PHP Polyfill for spl_object_id() for PHP <= 7.1
 * This file will be included even in releases which will analyze PHP 7.2,
 * there aren't any major compatibilities preventing analysis of PHP 7.2 from running in PHP 7.1.
 */
if (!function_exists('spl_object_id')) {
    if (function_exists('runkit_object_id') &&
        !(new ReflectionFunction('runkit_object_id'))->isUserDefined()) {
        /**
         * See https://github.com/runkit7/runkit_object_id for a faster native version for php <= 7.1
         *
         * @param object $object
         * @return int The object id
         */
        function spl_object_id($object) : int
        {
            return runkit_object_id($object);
        }
    } elseif (PHP_INT_SIZE === 8) {
        /**
         * See https://github.com/runkit7/runkit_object_id for a faster native version for php <= 7.1
         *
         * @param object $object
         * @return int (The object id, XORed with a random number)
         */
        function spl_object_id($object) : int
        {
            $hash = spl_object_hash($object);
            // Fit this into a php long (32-bit or 64-bit signed int).
            // The first 16 hex digits (64 bytes) vary, the last 16 don't.
            // Values are usually padded with 0s at the front.
            return intval(substr($hash, 1, 15), 16);
        }
    } else {
        /**
         * See https://github.com/runkit7/runkit_object_id for a faster native version for php <= 7.1
         *
         * @param object $object
         * @return int (The object id, XORed with a random number)
         */
        function spl_object_id($object) : int
        {
            $hash = spl_object_hash($object);
            // Fit this into a php long (32-bit or 64-bit signed int).
            // The first 16 hex digits (64 bytes) vary, the last 16 don't.
            // Values are usually padded with 0s at the front.
            return intval(substr($hash, 9, 7), 16);
        }
    }
}
