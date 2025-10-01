<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * PHP Version 5
 *
 * Copyright (c) 2001-2015, The PEAR developers
 *
 * This source file is subject to the BSD-2-Clause license,
 * that is bundled with this package in the file LICENSE, and is
 * available through the world-wide-web at the following url:
 * http://opensource.org/licenses/bsd-license.php.
 *
 * @category Console
 * @package  Console_Getopt
 * @author   Andrei Zmievski <andrei@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php BSD-2-Clause
 * @version  CVS: $Id$
 * @link     http://pear.php.net/package/Console_Getopt
 */

require_once 'PEAR.php';

/**
 * Command-line options parsing class.
 *
 * @category Console
 * @package  Console_Getopt
 * @author   Andrei Zmievski <andrei@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php BSD-2-Clause
 * @link     http://pear.php.net/package/Console_Getopt
 */
class Console_Getopt
{

    /**
     * Parses the command-line options.
     *
     * The first parameter to this function should be the list of command-line
     * arguments without the leading reference to the running program.
     *
     * The second parameter is a string of allowed short options. Each of the
     * option letters can be followed by a colon ':' to specify that the option
     * requires an argument, or a double colon '::' to specify that the option
     * takes an optional argument.
     *
     * The third argument is an optional array of allowed long options. The
     * leading '--' should not be included in the option name. Options that
     * require an argument should be followed by '=', and options that take an
     * option argument should be followed by '=='.
     *
     * The return value is an array of two elements: the list of parsed
     * options and the list of non-option command-line arguments. Each entry in
     * the list of parsed options is a pair of elements - the first one
     * specifies the option, and the second one specifies the option argument,
     * if there was one.
     *
     * Long and short options can be mixed.
     *
     * Most of the semantics of this function are based on GNU getopt_long().
     *
     * @param array  $args          an array of command-line arguments
     * @param string $short_options specifies the list of allowed short options
     * @param array  $long_options  specifies the list of allowed long options
     * @param boolean $skip_unknown suppresses Console_Getopt: unrecognized option
     *
     * @return array two-element array containing the list of parsed options and
     * the non-option arguments
     */
    public static function getopt2($args, $short_options, $long_options = null, $skip_unknown = false)
    {
        return Console_Getopt::doGetopt(2, $args, $short_options, $long_options, $skip_unknown);
    }

    /**
     * This function expects $args to start with the script name (POSIX-style).
     * Preserved for backwards compatibility.
     *
     * @param array  $args          an array of command-line arguments
     * @param string $short_options specifies the list of allowed short options
     * @param array  $long_options  specifies the list of allowed long options
     *
     * @see getopt2()
     * @return array two-element array containing the list of parsed options and
     * the non-option arguments
     */
    public static function getopt($args, $short_options, $long_options = null, $skip_unknown = false)
    {
        return Console_Getopt::doGetopt(1, $args, $short_options, $long_options, $skip_unknown);
    }

    /**
     * The actual implementation of the argument parsing code.
     *
     * @param int    $version       Version to use
     * @param array  $args          an array of command-line arguments
     * @param string $short_options specifies the list of allowed short options
     * @param array  $long_options  specifies the list of allowed long options
     * @param boolean $skip_unknown suppresses Console_Getopt: unrecognized option
     *
     * @return array
     */
    public static function doGetopt($version, $args, $short_options, $long_options = null, $skip_unknown = false)
    {
        // in case you pass directly readPHPArgv() as the first arg
        if (PEAR::isError($args)) {
            return $args;
        }

        if (empty($args)) {
            return array(array(), array());
        }

        $non_opts = $opts = array();

        settype($args, 'array');

        if ($long_options) {
            sort($long_options);
        }

        /*
         * Preserve backwards compatibility with callers that relied on
         * erroneous POSIX fix.
         */
        if ($version < 2) {
            if (isset($args[0][0]) && $args[0][0] != '-') {
                array_shift($args);
            }
        }

        for ($i = 0; $i < count($args); $i++) {
            $arg = $args[$i];
            /* The special element '--' means explicit end of
               options. Treat the rest of the arguments as non-options
               and end the loop. */
            if ($arg == '--') {
                $non_opts = array_merge($non_opts, array_slice($args, $i + 1));
                break;
            }

            if ($arg[0] != '-' || (strlen($arg) > 1 && $arg[1] == '-' && !$long_options)) {
                $non_opts = array_merge($non_opts, array_slice($args, $i));
                break;
            } elseif (strlen($arg) > 1 && $arg[1] == '-') {
                $error = Console_Getopt::_parseLongOption(substr($arg, 2),
                                                          $long_options,
                                                          $opts,
                                                          $i,
                                                          $args,
                                                          $skip_unknown);
                if (PEAR::isError($error)) {
                    return $error;
                }
            } elseif ($arg == '-') {
                // - is stdin
                $non_opts = array_merge($non_opts, array_slice($args, $i));
                break;
            } else {
                $error = Console_Getopt::_parseShortOption(substr($arg, 1),
                                                           $short_options,
                                                           $opts,
                                                           $i,
                                                           $args,
                                                           $skip_unknown);
                if (PEAR::isError($error)) {
                    return $error;
                }
            }
        }

        return array($opts, $non_opts);
    }

    /**
     * Parse short option
     *
     * @param string     $arg           Argument
     * @param string[]   $short_options Available short options
     * @param string[][] &$opts
     * @param int        &$argIdx
     * @param string[]   $args
     * @param boolean    $skip_unknown suppresses Console_Getopt: unrecognized option
     *
     * @return void
     */
    protected static function _parseShortOption($arg, $short_options, &$opts, &$argIdx, $args, $skip_unknown)
    {
        for ($i = 0; $i < strlen($arg); $i++) {
            $opt     = $arg[$i];
            $opt_arg = null;

            /* Try to find the short option in the specifier string. */
            if (($spec = strstr($short_options, $opt)) === false || $arg[$i] == ':') {
                if ($skip_unknown === true) {
                    break;
                }

                $msg = "Console_Getopt: unrecognized option -- $opt";
                return PEAR::raiseError($msg);
            }

            if (strlen($spec) > 1 && $spec[1] == ':') {
                if (strlen($spec) > 2 && $spec[2] == ':') {
                    if ($i + 1 < strlen($arg)) {
                        /* Option takes an optional argument. Use the remainder of
                           the arg string if there is anything left. */
                        $opts[] = array($opt, substr($arg, $i + 1));
                        break;
                    }
                } else {
                    /* Option requires an argument. Use the remainder of the arg
                       string if there is anything left. */
                    if ($i + 1 < strlen($arg)) {
                        $opts[] = array($opt,  substr($arg, $i + 1));
                        break;
                    } else if (isset($args[++$argIdx])) {
                        $opt_arg = $args[$argIdx];
                        /* Else use the next argument. */;
                        if (Console_Getopt::_isShortOpt($opt_arg)
                            || Console_Getopt::_isLongOpt($opt_arg)) {
                            $msg = "option requires an argument --$opt";
                            return PEAR::raiseError("Console_Getopt: " . $msg);
                        }
                    } else {
                        $msg = "option requires an argument --$opt";
                        return PEAR::raiseError("Console_Getopt: " . $msg);
                    }
                }
            }

            $opts[] = array($opt, $opt_arg);
        }
    }

    /**
     * Checks if an argument is a short option
     *
     * @param string $arg Argument to check
     *
     * @return bool
     */
    protected static function _isShortOpt($arg)
    {
        return strlen($arg) == 2 && $arg[0] == '-'
               && preg_match('/[a-zA-Z]/', $arg[1]);
    }

    /**
     * Checks if an argument is a long option
     *
     * @param string $arg Argument to check
     *
     * @return bool
     */
    protected static function _isLongOpt($arg)
    {
        return strlen($arg) > 2 && $arg[0] == '-' && $arg[1] == '-' &&
               preg_match('/[a-zA-Z]+$/', substr($arg, 2));
    }

    /**
     * Parse long option
     *
     * @param string     $arg          Argument
     * @param string[]   $long_options Available long options
     * @param string[][] &$opts
     * @param int        &$argIdx
     * @param string[]   $args
     *
     * @return void|PEAR_Error
     */
    protected static function _parseLongOption($arg, $long_options, &$opts, &$argIdx, $args, $skip_unknown)
    {
        @list($opt, $opt_arg) = explode('=', $arg, 2);

        $opt_len = strlen($opt);

        for ($i = 0; $i < count($long_options); $i++) {
            $long_opt  = $long_options[$i];
            $opt_start = substr($long_opt, 0, $opt_len);

            $long_opt_name = str_replace('=', '', $long_opt);

            /* Option doesn't match. Go on to the next one. */
            if ($long_opt_name != $opt) {
                continue;
            }

            $opt_rest = substr($long_opt, $opt_len);

            /* Check that the options uniquely matches one of the allowed
               options. */
            if ($i + 1 < count($long_options)) {
                $next_option_rest = substr($long_options[$i + 1], $opt_len);
            } else {
                $next_option_rest = '';
            }

            if ($opt_rest != '' && $opt[0] != '=' &&
                $i + 1 < count($long_options) &&
                $opt == substr($long_options[$i+1], 0, $opt_len) &&
                $next_option_rest != '' &&
                $next_option_rest[0] != '=') {

                $msg = "Console_Getopt: option --$opt is ambiguous";
                return PEAR::raiseError($msg);
            }

            if (substr($long_opt, -1) == '=') {
                if (substr($long_opt, -2) != '==') {
                    /* Long option requires an argument.
                       Take the next argument if one wasn't specified. */;
                    if (!strlen($opt_arg)) {
                        if (!isset($args[++$argIdx])) {
                            $msg = "Console_Getopt: option requires an argument --$opt";
                            return PEAR::raiseError($msg);
                        }
                        $opt_arg = $args[$argIdx];
                    }

                    if (Console_Getopt::_isShortOpt($opt_arg)
                        || Console_Getopt::_isLongOpt($opt_arg)) {
                        $msg = "Console_Getopt: option requires an argument --$opt";
                        return PEAR::raiseError($msg);
                    }
                }
            } else if ($opt_arg) {
                $msg = "Console_Getopt: option --$opt doesn't allow an argument";
                return PEAR::raiseError($msg);
            }

            $opts[] = array('--' . $opt, $opt_arg);
            return;
        }

        if ($skip_unknown === true) {
            return;
        }

        return PEAR::raiseError("Console_Getopt: unrecognized option --$opt");
    }

    /**
     * Safely read the $argv PHP array across different PHP configurations.
     * Will take care on register_globals and register_argc_argv ini directives
     *
     * @return mixed the $argv PHP array or PEAR error if not registered
     */
    public static function readPHPArgv()
    {
        global $argv;
        if (!is_array($argv)) {
            if (!@is_array($_SERVER['argv'])) {
                if (!@is_array($GLOBALS['HTTP_SERVER_VARS']['argv'])) {
                    $msg = "Could not read cmd args (register_argc_argv=Off?)";
                    return PEAR::raiseError("Console_Getopt: " . $msg);
                }
                return $GLOBALS['HTTP_SERVER_VARS']['argv'];
            }
            return $_SERVER['argv'];
        }
        return $argv;
    }

}
