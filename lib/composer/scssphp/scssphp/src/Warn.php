<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp;

final class Warn
{
    /**
     * @var callable|null
     * @phpstan-var (callable(string, bool): void)|null
     */
    private static $callback;

    /**
     * Prints a warning message associated with the current `@import` or function call.
     *
     * This may only be called within a custom function or importer callback.
     *
     * @param string $message
     *
     * @return void
     */
    public static function warning($message)
    {
        self::reportWarning($message, false);
    }

    /**
     * Prints a deprecation warning message associated with the current `@import` or function call.
     *
     * This may only be called within a custom function or importer callback.
     *
     * @param string $message
     *
     * @return void
     */
    public static function deprecation($message)
    {
        self::reportWarning($message, true);
    }

    /**
     * @param callable|null $callback
     *
     * @return callable|null The previous warn callback
     *
     * @phpstan-param (callable(string, bool): void)|null $callback
     *
     * @phpstan-return (callable(string, bool): void)|null
     *
     * @internal
     */
    public static function setCallback(callable $callback = null)
    {
        $previousCallback = self::$callback;
        self::$callback = $callback;

        return $previousCallback;
    }

    /**
     * @param string $message
     * @param bool   $deprecation
     *
     * @return void
     */
    private static function reportWarning($message, $deprecation)
    {
        if (self::$callback === null) {
            throw new \BadMethodCallException('The warning Reporter may only be called within a custom function or importer callback.');
        }

        \call_user_func(self::$callback, $message, $deprecation);
    }
}
