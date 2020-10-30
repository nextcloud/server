<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler;

/**
 * Provides utility functions to prepare a child process command-line and set
 * environment variables in that process.
 *
 * @author John Stevenson <john-stevenson@blueyonder.co.uk>
 * @internal
 */
class Process
{
    /**
     * Returns an array of parameters, including a color option if required
     *
     * A color option is needed because child process output is piped.
     *
     * @param array $args The script parameters
     * @param string $colorOption The long option to force color output
     *
     * @return array
     */
    public static function addColorOption(array $args, $colorOption)
    {
        if (!$colorOption
            || in_array($colorOption, $args)
            || !preg_match('/^--([a-z]+$)|(^--[a-z]+=)/', $colorOption, $matches)) {
            return $args;
        }

        if (isset($matches[2])) {
            // Handle --color(s)= options
            if (false !== ($index = array_search($matches[2].'auto', $args))) {
                $args[$index] = $colorOption;
                return $args;
            } elseif (preg_grep('/^'.$matches[2].'/', $args)) {
                return $args;
            }
        } elseif (in_array('--no-'.$matches[1], $args)) {
            return $args;
        }

        // Check for NO_COLOR variable (https://no-color.org/)
        if (false !== getenv('NO_COLOR')) {
            return $args;
        }

        if (false !== ($index = array_search('--', $args))) {
            // Position option before double-dash delimiter
            array_splice($args, $index, 0, $colorOption);
        } else {
            $args[] = $colorOption;
        }

        return $args;
    }

    /**
     * Escapes a string to be used as a shell argument.
     *
     * From https://github.com/johnstevenson/winbox-args
     * MIT Licensed (c) John Stevenson <john-stevenson@blueyonder.co.uk>
     *
     * @param string $arg  The argument to be escaped
     * @param bool   $meta Additionally escape cmd.exe meta characters
     * @param bool $module The argument is the module to invoke
     *
     * @return string The escaped argument
     */
    public static function escape($arg, $meta = true, $module = false)
    {
        if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            return "'".str_replace("'", "'\\''", $arg)."'";
        }

        $quote = strpbrk($arg, " \t") !== false || $arg === '';

        $arg = preg_replace('/(\\\\*)"/', '$1$1\\"', $arg, -1, $dquotes);

        if ($meta) {
            $meta = $dquotes || preg_match('/%[^%]+%/', $arg);

            if (!$meta) {
                $quote = $quote || strpbrk($arg, '^&|<>()') !== false;
            } elseif ($module && !$dquotes && $quote) {
                $meta = false;
            }
        }

        if ($quote) {
            $arg = '"'.preg_replace('/(\\\\*)$/', '$1$1', $arg).'"';
        }

        if ($meta) {
            $arg = preg_replace('/(["^&|<>()%])/', '^$1', $arg);
        }

        return $arg;
    }

    /**
     * Returns true if the output stream supports colors
     *
     * This is tricky on Windows, because Cygwin, Msys2 etc emulate pseudo
     * terminals via named pipes, so we can only check the environment.
     *
     * @param mixed $output A valid CLI output stream
     *
     * @return bool
     */
    public static function supportsColor($output)
    {
        if ('Hyper' === getenv('TERM_PROGRAM')) {
            return true;
        }

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            return (function_exists('sapi_windows_vt100_support')
                && sapi_windows_vt100_support($output))
                || false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM');
        }

        if (function_exists('stream_isatty')) {
            return stream_isatty($output);
        }

        if (function_exists('posix_isatty')) {
            return posix_isatty($output);
        }

        $stat = fstat($output);
        // Check if formatted mode is S_IFCHR
        return $stat ? 0020000 === ($stat['mode'] & 0170000) : false;
    }

    /**
     * Makes putenv environment changes available in $_SERVER and $_ENV
     *
     * @param string $name
     * @param string|false $value A false value unsets the variable
     *
     * @return bool Whether the environment variable was set
     */
    public static function setEnv($name, $value = false)
    {
        $unset = false === $value;

        if (!putenv($unset ? $name : $name.'='.$value)) {
            return false;
        }

        if ($unset) {
            unset($_SERVER[$name]);
        } else {
            $_SERVER[$name] = $value;
        }

        // Update $_ENV if it is being used
        if (false !== stripos((string) ini_get('variables_order'), 'E')) {
            if ($unset) {
                unset($_ENV[$name]);
            } else {
                $_ENV[$name] = $value;
            }
        }

        return true;
    }
}
