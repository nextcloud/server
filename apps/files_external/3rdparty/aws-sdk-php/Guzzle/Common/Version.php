<?php

namespace Guzzle\Common;

/**
 * Guzzle version information
 */
class Version
{
    const VERSION = '3.9.2';

    /**
     * @var bool Set this value to true to enable warnings for deprecated functionality use. This should be on in your
     *           unit tests, but probably not in production.
     */
    public static $emitWarnings = false;

    /**
     * Emit a deprecation warning
     *
     * @param string $message Warning message
     */
    public static function warn($message)
    {
        if (self::$emitWarnings) {
            trigger_error('Deprecation warning: ' . $message, E_USER_DEPRECATED);
        }
    }
}
