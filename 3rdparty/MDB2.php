<?php
// vim: set et ts=4 sw=4 fdm=marker:
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2007 Manuel Lemos, Tomas V.V.Cox,                 |
// | Stig. S. Bakken, Lukas Smith                                         |
// | All rights reserved.                                                 |
// +----------------------------------------------------------------------+
// | MDB2 is a merge of PEAR DB and Metabases that provides a unified DB  |
// | API as well as database abstraction for PHP applications.            |
// | This LICENSE is in the BSD license style.                            |
// |                                                                      |
// | Redistribution and use in source and binary forms, with or without   |
// | modification, are permitted provided that the following conditions   |
// | are met:                                                             |
// |                                                                      |
// | Redistributions of source code must retain the above copyright       |
// | notice, this list of conditions and the following disclaimer.        |
// |                                                                      |
// | Redistributions in binary form must reproduce the above copyright    |
// | notice, this list of conditions and the following disclaimer in the  |
// | documentation and/or other materials provided with the distribution. |
// |                                                                      |
// | Neither the name of Manuel Lemos, Tomas V.V.Cox, Stig. S. Bakken,    |
// | Lukas Smith nor the names of his contributors may be used to endorse |
// | or promote products derived from this software without specific prior|
// | written permission.                                                  |
// |                                                                      |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
// | FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE      |
// | REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,          |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
// | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS|
// |  OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED  |
// | AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT          |
// | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY|
// | WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE          |
// | POSSIBILITY OF SUCH DAMAGE.                                          |
// +----------------------------------------------------------------------+
// | Author: Lukas Smith <smith@pooteeweet.org>                           |
// +----------------------------------------------------------------------+
//
// $Id$
//

/**
 * @package     MDB2
 * @category    Database
 * @author      Lukas Smith <smith@pooteeweet.org>
 */

require_once 'PEAR.php';

// {{{ Error constants

/**
 * The method mapErrorCode in each MDB2_dbtype implementation maps
 * native error codes to one of these.
 *
 * If you add an error code here, make sure you also add a textual
 * version of it in MDB2::errorMessage().
 */

define('MDB2_OK',                      true);
define('MDB2_ERROR',                     -1);
define('MDB2_ERROR_SYNTAX',              -2);
define('MDB2_ERROR_CONSTRAINT',          -3);
define('MDB2_ERROR_NOT_FOUND',           -4);
define('MDB2_ERROR_ALREADY_EXISTS',      -5);
define('MDB2_ERROR_UNSUPPORTED',         -6);
define('MDB2_ERROR_MISMATCH',            -7);
define('MDB2_ERROR_INVALID',             -8);
define('MDB2_ERROR_NOT_CAPABLE',         -9);
define('MDB2_ERROR_TRUNCATED',          -10);
define('MDB2_ERROR_INVALID_NUMBER',     -11);
define('MDB2_ERROR_INVALID_DATE',       -12);
define('MDB2_ERROR_DIVZERO',            -13);
define('MDB2_ERROR_NODBSELECTED',       -14);
define('MDB2_ERROR_CANNOT_CREATE',      -15);
define('MDB2_ERROR_CANNOT_DELETE',      -16);
define('MDB2_ERROR_CANNOT_DROP',        -17);
define('MDB2_ERROR_NOSUCHTABLE',        -18);
define('MDB2_ERROR_NOSUCHFIELD',        -19);
define('MDB2_ERROR_NEED_MORE_DATA',     -20);
define('MDB2_ERROR_NOT_LOCKED',         -21);
define('MDB2_ERROR_VALUE_COUNT_ON_ROW', -22);
define('MDB2_ERROR_INVALID_DSN',        -23);
define('MDB2_ERROR_CONNECT_FAILED',     -24);
define('MDB2_ERROR_EXTENSION_NOT_FOUND',-25);
define('MDB2_ERROR_NOSUCHDB',           -26);
define('MDB2_ERROR_ACCESS_VIOLATION',   -27);
define('MDB2_ERROR_CANNOT_REPLACE',     -28);
define('MDB2_ERROR_CONSTRAINT_NOT_NULL',-29);
define('MDB2_ERROR_DEADLOCK',           -30);
define('MDB2_ERROR_CANNOT_ALTER',       -31);
define('MDB2_ERROR_MANAGER',            -32);
define('MDB2_ERROR_MANAGER_PARSE',      -33);
define('MDB2_ERROR_LOADMODULE',         -34);
define('MDB2_ERROR_INSUFFICIENT_DATA',  -35);
define('MDB2_ERROR_NO_PERMISSION',      -36);
define('MDB2_ERROR_DISCONNECT_FAILED',  -37);

// }}}
// {{{ Verbose constants
/**
 * These are just helper constants to more verbosely express parameters to prepare()
 */

define('MDB2_PREPARE_MANIP', false);
define('MDB2_PREPARE_RESULT', null);

// }}}
// {{{ Fetchmode constants

/**
 * This is a special constant that tells MDB2 the user hasn't specified
 * any particular get mode, so the default should be used.
 */
define('MDB2_FETCHMODE_DEFAULT', 0);

/**
 * Column data indexed by numbers, ordered from 0 and up
 */
define('MDB2_FETCHMODE_ORDERED', 1);

/**
 * Column data indexed by column names
 */
define('MDB2_FETCHMODE_ASSOC', 2);

/**
 * Column data as object properties
 */
define('MDB2_FETCHMODE_OBJECT', 3);

/**
 * For multi-dimensional results: normally the first level of arrays
 * is the row number, and the second level indexed by column number or name.
 * MDB2_FETCHMODE_FLIPPED switches this order, so the first level of arrays
 * is the column name, and the second level the row number.
 */
define('MDB2_FETCHMODE_FLIPPED', 4);

// }}}
// {{{ Portability mode constants

/**
 * Portability: turn off all portability features.
 * @see MDB2_Driver_Common::setOption()
 */
define('MDB2_PORTABILITY_NONE', 0);

/**
 * Portability: convert names of tables and fields to case defined in the
 * "field_case" option when using the query*(), fetch*() and tableInfo() methods.
 * @see MDB2_Driver_Common::setOption()
 */
define('MDB2_PORTABILITY_FIX_CASE', 1);

/**
 * Portability: right trim the data output by query*() and fetch*().
 * @see MDB2_Driver_Common::setOption()
 */
define('MDB2_PORTABILITY_RTRIM', 2);

/**
 * Portability: force reporting the number of rows deleted.
 * @see MDB2_Driver_Common::setOption()
 */
define('MDB2_PORTABILITY_DELETE_COUNT', 4);

/**
 * Portability: not needed in MDB2 (just left here for compatibility to DB)
 * @see MDB2_Driver_Common::setOption()
 */
define('MDB2_PORTABILITY_NUMROWS', 8);

/**
 * Portability: makes certain error messages in certain drivers compatible
 * with those from other DBMS's.
 *
 * + mysql, mysqli:  change unique/primary key constraints
 *   MDB2_ERROR_ALREADY_EXISTS -> MDB2_ERROR_CONSTRAINT
 *
 * + odbc(access):  MS's ODBC driver reports 'no such field' as code
 *   07001, which means 'too few parameters.'  When this option is on
 *   that code gets mapped to MDB2_ERROR_NOSUCHFIELD.
 *
 * @see MDB2_Driver_Common::setOption()
 */
define('MDB2_PORTABILITY_ERRORS', 16);

/**
 * Portability: convert empty values to null strings in data output by
 * query*() and fetch*().
 * @see MDB2_Driver_Common::setOption()
 */
define('MDB2_PORTABILITY_EMPTY_TO_NULL', 32);

/**
 * Portability: removes database/table qualifiers from associative indexes
 * @see MDB2_Driver_Common::setOption()
 */
define('MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES', 64);

/**
 * Portability: turn on all portability features.
 * @see MDB2_Driver_Common::setOption()
 */
define('MDB2_PORTABILITY_ALL', 127);

// }}}
// {{{ Globals for class instance tracking

/**
 * These are global variables that are used to track the various class instances
 */

$GLOBALS['_MDB2_databases'] = array();
$GLOBALS['_MDB2_dsninfo_default'] = array(
    'phptype'  => false,
    'dbsyntax' => false,
    'username' => false,
    'password' => false,
    'protocol' => false,
    'hostspec' => false,
    'port'     => false,
    'socket'   => false,
    'database' => false,
    'mode'     => false,
);

// }}}
// {{{ class MDB2

/**
 * The main 'MDB2' class is simply a container class with some static
 * methods for creating DB objects as well as some utility functions
 * common to all parts of DB.
 *
 * The object model of MDB2 is as follows (indentation means inheritance):
 *
 * MDB2          The main MDB2 class.  This is simply a utility class
 *              with some 'static' methods for creating MDB2 objects as
 *              well as common utility functions for other MDB2 classes.
 *
 * MDB2_Driver_Common   The base for each MDB2 implementation.  Provides default
 * |            implementations (in OO lingo virtual methods) for
 * |            the actual DB implementations as well as a bunch of
 * |            query utility functions.
 * |
 * +-MDB2_Driver_mysql  The MDB2 implementation for MySQL. Inherits MDB2_Driver_Common.
 *              When calling MDB2::factory or MDB2::connect for MySQL
 *              connections, the object returned is an instance of this
 *              class.
 * +-MDB2_Driver_pgsql  The MDB2 implementation for PostGreSQL. Inherits MDB2_Driver_Common.
 *              When calling MDB2::factory or MDB2::connect for PostGreSQL
 *              connections, the object returned is an instance of this
 *              class.
 *
 * @package     MDB2
 * @category    Database
 * @author      Lukas Smith <smith@pooteeweet.org>
 */
class MDB2
{
    // {{{ function setOptions($db, $options)

    /**
     * set option array   in an exiting database object
     *
     * @param   MDB2_Driver_Common  MDB2 object
     * @param   array   An associative array of option names and their values.
     *
     * @return mixed   MDB2_OK or a PEAR Error object
     *
     * @access  public
     */
    static function setOptions($db, $options)
    {
        if (is_array($options)) {
            foreach ($options as $option => $value) {
                $test = $db->setOption($option, $value);
                if (MDB2::isError($test)) {
                    return $test;
                }
            }
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ function classExists($classname)

    /**
     * Checks if a class exists without triggering __autoload
     *
     * @param   string  classname
     *
     * @return  bool    true success and false on error
     * @static
     * @access  public
     */
    static function classExists($classname)
    {
        return class_exists($classname, false);
    }

    // }}}
    // {{{ function loadClass($class_name, $debug)

    /**
     * Loads a PEAR class.
     *
     * @param   string  classname to load
     * @param   bool    if errors should be suppressed
     *
     * @return  mixed   true success or PEAR_Error on failure
     *
     * @access  public
     */
    static function loadClass($class_name, $debug)
    {
        if (!MDB2::classExists($class_name)) {
            $file_name = str_replace('_', DIRECTORY_SEPARATOR, $class_name).'.php';
            if ($debug) {
                $include = include_once($file_name);
            } else {
                $include = @include_once($file_name);
            }
            if (!$include) {
                if (!MDB2::fileExists($file_name)) {
                    $msg = "unable to find package '$class_name' file '$file_name'";
                } else {
                    $msg = "unable to load class '$class_name' from file '$file_name'";
                }
                $err = MDB2::raiseError(MDB2_ERROR_NOT_FOUND, null, null, $msg);
                return $err;
            }
            if (!MDB2::classExists($class_name)) {
                $msg = "unable to load class '$class_name' from file '$file_name'";
                $err = MDB2::raiseError(MDB2_ERROR_NOT_FOUND, null, null, $msg);
                return $err;
            }
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ function factory($dsn, $options = false)

    /**
     * Create a new MDB2 object for the specified database type
     *
     * @param   mixed   'data source name', see the MDB2::parseDSN
     *                      method for a description of the dsn format.
     *                      Can also be specified as an array of the
     *                      format returned by MDB2::parseDSN.
     * @param   array   An associative array of option names and
     *                            their values.
     *
     * @return  mixed   a newly created MDB2 object, or false on error
     *
     * @access  public
     */
    static function factory($dsn, $options = false)
    {
        $dsninfo = MDB2::parseDSN($dsn);
        if (empty($dsninfo['phptype'])) {
            $err = MDB2::raiseError(MDB2_ERROR_NOT_FOUND,
                null, null, 'no RDBMS driver specified');
            return $err;
        }
        $class_name = 'MDB2_Driver_'.$dsninfo['phptype'];

        $debug = (!empty($options['debug']));
        $err = MDB2::loadClass($class_name, $debug);
        if (MDB2::isError($err)) {
            return $err;
        }

        $db = new $class_name();
        $db->setDSN($dsninfo);
        $err = MDB2::setOptions($db, $options);
        if (MDB2::isError($err)) {
            return $err;
        }

        return $db;
    }

    // }}}
    // {{{ function connect($dsn, $options = false)

    /**
     * Create a new MDB2_Driver_* connection object and connect to the specified
     * database
     *
     * @param mixed $dsn     'data source name', see the MDB2::parseDSN
     *                       method for a description of the dsn format.
     *                       Can also be specified as an array of the
     *                       format returned by MDB2::parseDSN.
     * @param array $options An associative array of option names and
     *                       their values.
     *
     * @return mixed a newly created MDB2 connection object, or a MDB2
     *               error object on error
     *
     * @access  public
     * @see     MDB2::parseDSN
     */
    static function connect($dsn, $options = false)
    {
        $db = MDB2::factory($dsn, $options);
        if (MDB2::isError($db)) {
            return $db;
        }

        $err = $db->connect();
        if (MDB2::isError($err)) {
            $dsn = $db->getDSN('string', 'xxx');
            $db->disconnect();
            $err->addUserInfo($dsn);
            return $err;
        }

        return $db;
    }

    // }}}
    // {{{ function singleton($dsn = null, $options = false)

    /**
     * Returns a MDB2 connection with the requested DSN.
     * A new MDB2 connection object is only created if no object with the
     * requested DSN exists yet.
     *
     * @param   mixed   'data source name', see the MDB2::parseDSN
     *                            method for a description of the dsn format.
     *                            Can also be specified as an array of the
     *                            format returned by MDB2::parseDSN.
     * @param   array   An associative array of option names and
     *                            their values.
     *
     * @return  mixed   a newly created MDB2 connection object, or a MDB2
     *                  error object on error
     *
     * @access  public
     * @see     MDB2::parseDSN
     */
    static function singleton($dsn = null, $options = false)
    {
        if ($dsn) {
            $dsninfo = MDB2::parseDSN($dsn);
            $dsninfo = array_merge($GLOBALS['_MDB2_dsninfo_default'], $dsninfo);
            $keys = array_keys($GLOBALS['_MDB2_databases']);
            for ($i=0, $j=count($keys); $i<$j; ++$i) {
                if (isset($GLOBALS['_MDB2_databases'][$keys[$i]])) {
                    $tmp_dsn = $GLOBALS['_MDB2_databases'][$keys[$i]]->getDSN('array');
                    if (count(array_diff_assoc($tmp_dsn, $dsninfo)) == 0) {
                        MDB2::setOptions($GLOBALS['_MDB2_databases'][$keys[$i]], $options);
                        return $GLOBALS['_MDB2_databases'][$keys[$i]];
                    }
                }
            }
        } elseif (is_array($GLOBALS['_MDB2_databases']) && reset($GLOBALS['_MDB2_databases'])) {
            return $GLOBALS['_MDB2_databases'][key($GLOBALS['_MDB2_databases'])];
        }
        $db = MDB2::factory($dsn, $options);
        return $db;
    }

    // }}}
    // {{{ function areEquals()

    /**
     * It looks like there's a memory leak in array_diff() in PHP 5.1.x,
     * so use this method instead.
     * @see http://pear.php.net/bugs/bug.php?id=11790
     *
     * @param array $arr1
     * @param array $arr2
     * @return boolean
     */
    static function areEquals($arr1, $arr2)
    {
        if (count($arr1) != count($arr2)) {
            return false;
        }
        foreach (array_keys($arr1) as $k) {
            if (!array_key_exists($k, $arr2) || $arr1[$k] != $arr2[$k]) {
                return false;
            }
        }
        return true;
    }

    // }}}
    // {{{ function loadFile($file)

    /**
     * load a file (like 'Date')
     *
     * @param string $file name of the file in the MDB2 directory (without '.php')
     *
     * @return string name of the file that was included
     *
     * @access  public
     */
    static function loadFile($file)
    {
        $file_name = 'MDB2'.DIRECTORY_SEPARATOR.$file.'.php';
        if (!MDB2::fileExists($file_name)) {
            return MDB2::raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'unable to find: '.$file_name);
        }
        if (!include_once($file_name)) {
            return MDB2::raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'unable to load driver class: '.$file_name);
        }
        return $file_name;
    }

    // }}}
    // {{{ function apiVersion()

    /**
     * Return the MDB2 API version
     *
     * @return  string  the MDB2 API version number
     *
     * @access  public
     */
    function apiVersion()
    {
        return '@package_version@';
    }

    // }}}
    // {{{ function &raiseError($code = null, $mode = null, $options = null, $userinfo = null)

    /**
     * This method is used to communicate an error and invoke error
     * callbacks etc.  Basically a wrapper for PEAR::raiseError
     * without the message string.
     *
     * @param   mixed  int error code
     *
     * @param   int    error mode, see PEAR_Error docs
     *
     * @param   mixed  If error mode is PEAR_ERROR_TRIGGER, this is the
     *                 error level (E_USER_NOTICE etc).  If error mode is
     *                 PEAR_ERROR_CALLBACK, this is the callback function,
     *                 either as a function name, or as an array of an
     *                 object and method name.  For other error modes this
     *                 parameter is ignored.
     *
     * @param   string Extra debug information.  Defaults to the last
     *                 query and native error code.
     *
     * @return PEAR_Error instance of a PEAR Error object
     *
     * @access  private
     * @see     PEAR_Error
     */
    public static function &raiseError($code = null,
                         $mode = null,
                         $options = null,
                         $userinfo = null,
                         $dummy1 = null,
                         $dummy2 = null,
                         $dummy3 = false)
    {
        $pear = new PEAR;
        $err = $pear->raiseError(null, $code, $mode, $options, $userinfo, 'MDB2_Error', true);
        return $err;
    }

    // }}}
    // {{{ function isError($data, $code = null)

    /**
     * Tell whether a value is a MDB2 error.
     *
     * @param   mixed   the value to test
     * @param   int     if is an error object, return true
     *                        only if $code is a string and
     *                        $db->getMessage() == $code or
     *                        $code is an integer and $db->getCode() == $code
     *
     * @return  bool    true if parameter is an error
     *
     * @access  public
     */
    static function isError($data, $code = null)
    {
        if ($data instanceof MDB2_Error) {
            if (null === $code) {
                return true;
            }
            if (is_string($code)) {
                return $data->getMessage() === $code;
            }
            return in_array($data->getCode(), (array)$code);
        }
        return false;
    }

    // }}}
    // {{{ function isConnection($value)

    /**
     * Tell whether a value is a MDB2 connection
     *
     * @param   mixed   value to test
     *
     * @return  bool    whether $value is a MDB2 connection
     * @access  public
     */
    static function isConnection($value)
    {
        return ($value instanceof MDB2_Driver_Common);
    }

    // }}}
    // {{{ function isResult($value)

    /**
     * Tell whether a value is a MDB2 result
     *
     * @param mixed $value value to test
     *
     * @return bool whether $value is a MDB2 result
     *
     * @access public
     */
    function isResult($value)
    {
        return ($value instanceof MDB2_Result);
    }

    // }}}
    // {{{ function isResultCommon($value)

    /**
     * Tell whether a value is a MDB2 result implementing the common interface
     *
     * @param mixed $value value to test
     *
     * @return bool whether $value is a MDB2 result implementing the common interface
     *
     * @access  public
     */
    static function isResultCommon($value)
    {
        return ($value instanceof MDB2_Result_Common);
    }

    // }}}
    // {{{ function isStatement($value)

    /**
     * Tell whether a value is a MDB2 statement interface
     *
     * @param   mixed   value to test
     *
     * @return  bool    whether $value is a MDB2 statement interface
     *
     * @access  public
     */
    function isStatement($value)
    {
        return ($value instanceof MDB2_Statement_Common);
    }

    // }}}
    // {{{ function errorMessage($value = null)

    /**
     * Return a textual error message for a MDB2 error code
     *
     * @param   int|array   integer error code,
                                null to get the current error code-message map,
                                or an array with a new error code-message map
     *
     * @return  string  error message, or false if the error code was
     *                  not recognized
     *
     * @access  public
     */
    static function errorMessage($value = null)
    {
        static $errorMessages;

        if (is_array($value)) {
            $errorMessages = $value;
            return MDB2_OK;
        }

        if (!isset($errorMessages)) {
            $errorMessages = array(
                MDB2_OK                       => 'no error',
                MDB2_ERROR                    => 'unknown error',
                MDB2_ERROR_ALREADY_EXISTS     => 'already exists',
                MDB2_ERROR_CANNOT_CREATE      => 'can not create',
                MDB2_ERROR_CANNOT_ALTER       => 'can not alter',
                MDB2_ERROR_CANNOT_REPLACE     => 'can not replace',
                MDB2_ERROR_CANNOT_DELETE      => 'can not delete',
                MDB2_ERROR_CANNOT_DROP        => 'can not drop',
                MDB2_ERROR_CONSTRAINT         => 'constraint violation',
                MDB2_ERROR_CONSTRAINT_NOT_NULL=> 'null value violates not-null constraint',
                MDB2_ERROR_DIVZERO            => 'division by zero',
                MDB2_ERROR_INVALID            => 'invalid',
                MDB2_ERROR_INVALID_DATE       => 'invalid date or time',
                MDB2_ERROR_INVALID_NUMBER     => 'invalid number',
                MDB2_ERROR_MISMATCH           => 'mismatch',
                MDB2_ERROR_NODBSELECTED       => 'no database selected',
                MDB2_ERROR_NOSUCHFIELD        => 'no such field',
                MDB2_ERROR_NOSUCHTABLE        => 'no such table',
                MDB2_ERROR_NOT_CAPABLE        => 'MDB2 backend not capable',
                MDB2_ERROR_NOT_FOUND          => 'not found',
                MDB2_ERROR_NOT_LOCKED         => 'not locked',
                MDB2_ERROR_SYNTAX             => 'syntax error',
                MDB2_ERROR_UNSUPPORTED        => 'not supported',
                MDB2_ERROR_VALUE_COUNT_ON_ROW => 'value count on row',
                MDB2_ERROR_INVALID_DSN        => 'invalid DSN',
                MDB2_ERROR_CONNECT_FAILED     => 'connect failed',
                MDB2_ERROR_NEED_MORE_DATA     => 'insufficient data supplied',
                MDB2_ERROR_EXTENSION_NOT_FOUND=> 'extension not found',
                MDB2_ERROR_NOSUCHDB           => 'no such database',
                MDB2_ERROR_ACCESS_VIOLATION   => 'insufficient permissions',
                MDB2_ERROR_LOADMODULE         => 'error while including on demand module',
                MDB2_ERROR_TRUNCATED          => 'truncated',
                MDB2_ERROR_DEADLOCK           => 'deadlock detected',
                MDB2_ERROR_NO_PERMISSION      => 'no permission',
                MDB2_ERROR_DISCONNECT_FAILED  => 'disconnect failed',
            );
        }

        if (null === $value) {
            return $errorMessages;
        }

        if (MDB2::isError($value)) {
            $value = $value->getCode();
        }

        return isset($errorMessages[$value]) ?
           $errorMessages[$value] : $errorMessages[MDB2_ERROR];
    }

    // }}}
    // {{{ function parseDSN($dsn)

    /**
     * Parse a data source name.
     *
     * Additional keys can be added by appending a URI query string to the
     * end of the DSN.
     *
     * The format of the supplied DSN is in its fullest form:
     * <code>
     *  phptype(dbsyntax)://username:password@protocol+hostspec/database?option=8&another=true
     * </code>
     *
     * Most variations are allowed:
     * <code>
     *  phptype://username:password@protocol+hostspec:110//usr/db_file.db?mode=0644
     *  phptype://username:password@hostspec/database_name
     *  phptype://username:password@hostspec
     *  phptype://username@hostspec
     *  phptype://hostspec/database
     *  phptype://hostspec
     *  phptype(dbsyntax)
     *  phptype
     * </code>
     *
     * @param   string  Data Source Name to be parsed
     *
     * @return  array   an associative array with the following keys:
     *  + phptype:  Database backend used in PHP (mysql, odbc etc.)
     *  + dbsyntax: Database used with regards to SQL syntax etc.
     *  + protocol: Communication protocol to use (tcp, unix etc.)
     *  + hostspec: Host specification (hostname[:port])
     *  + database: Database to use on the DBMS server
     *  + username: User name for login
     *  + password: Password for login
     *
     * @access  public
     * @author  Tomas V.V.Cox <cox@idecnet.com>
     */
    static function parseDSN($dsn)
    {
        $parsed = $GLOBALS['_MDB2_dsninfo_default'];

        if (is_array($dsn)) {
            $dsn = array_merge($parsed, $dsn);
            if (!$dsn['dbsyntax']) {
                $dsn['dbsyntax'] = $dsn['phptype'];
            }
            return $dsn;
        }

        // Find phptype and dbsyntax
        if (($pos = strpos($dsn, '://')) !== false) {
            $str = substr($dsn, 0, $pos);
            $dsn = substr($dsn, $pos + 3);
        } else {
            $str = $dsn;
            $dsn = null;
        }

        // Get phptype and dbsyntax
        // $str => phptype(dbsyntax)
        if (preg_match('|^(.+?)\((.*?)\)$|', $str, $arr)) {
            $parsed['phptype']  = $arr[1];
            $parsed['dbsyntax'] = !$arr[2] ? $arr[1] : $arr[2];
        } else {
            $parsed['phptype']  = $str;
            $parsed['dbsyntax'] = $str;
        }

        if (!count($dsn)) {
            return $parsed;
        }

        // Get (if found): username and password
        // $dsn => username:password@protocol+hostspec/database
        if (($at = strrpos($dsn,'@')) !== false) {
            $str = substr($dsn, 0, $at);
            $dsn = substr($dsn, $at + 1);
            if (($pos = strpos($str, ':')) !== false) {
                $parsed['username'] = rawurldecode(substr($str, 0, $pos));
                $parsed['password'] = rawurldecode(substr($str, $pos + 1));
            } else {
                $parsed['username'] = rawurldecode($str);
            }
        }

        // Find protocol and hostspec

        // $dsn => proto(proto_opts)/database
        if (preg_match('|^([^(]+)\((.*?)\)/?(.*?)$|', $dsn, $match)) {
            $proto       = $match[1];
            $proto_opts  = $match[2] ? $match[2] : false;
            $dsn         = $match[3];

        // $dsn => protocol+hostspec/database (old format)
        } else {
            if (strpos($dsn, '+') !== false) {
                list($proto, $dsn) = explode('+', $dsn, 2);
            }
            if (   strpos($dsn, '//') === 0
                && strpos($dsn, '/', 2) !== false
                && $parsed['phptype'] == 'oci8'
            ) {
                //oracle's "Easy Connect" syntax:
                //"username/password@[//]host[:port][/service_name]"
                //e.g. "scott/tiger@//mymachine:1521/oracle"
                $proto_opts = $dsn;
                $pos = strrpos($proto_opts, '/');
                $dsn = substr($proto_opts, $pos + 1);
                $proto_opts = substr($proto_opts, 0, $pos);
            } elseif (strpos($dsn, '/') !== false) {
                list($proto_opts, $dsn) = explode('/', $dsn, 2);
            } else {
                $proto_opts = $dsn;
                $dsn = null;
            }
        }

        // process the different protocol options
        $parsed['protocol'] = (!empty($proto)) ? $proto : 'tcp';
        $proto_opts = rawurldecode($proto_opts);
        if (strpos($proto_opts, ':') !== false) {
            list($proto_opts, $parsed['port']) = explode(':', $proto_opts);
        }
        if ($parsed['protocol'] == 'tcp') {
            $parsed['hostspec'] = $proto_opts;
        } elseif ($parsed['protocol'] == 'unix') {
            $parsed['socket'] = $proto_opts;
        }

        // Get dabase if any
        // $dsn => database
        if ($dsn) {
            // /database
            if (($pos = strpos($dsn, '?')) === false) {
                $parsed['database'] = rawurldecode($dsn);
            // /database?param1=value1&param2=value2
            } else {
                $parsed['database'] = rawurldecode(substr($dsn, 0, $pos));
                $dsn = substr($dsn, $pos + 1);
                if (strpos($dsn, '&') !== false) {
                    $opts = explode('&', $dsn);
                } else { // database?param1=value1
                    $opts = array($dsn);
                }
                foreach ($opts as $opt) {
                    list($key, $value) = explode('=', $opt);
                    if (!array_key_exists($key, $parsed) || false === $parsed[$key]) {
                        // don't allow params overwrite
                        $parsed[$key] = rawurldecode($value);
                    }
                }
            }
        }

        return $parsed;
    }

    // }}}
    // {{{ function fileExists($file)

    /**
     * Checks if a file exists in the include path
     *
     * @param   string  filename
     *
     * @return  bool    true success and false on error
     *
     * @access  public
     */
    static function fileExists($file)
    {
        // safe_mode does notwork with is_readable()
        if (!@ini_get('safe_mode')) {
             $dirs = explode(PATH_SEPARATOR, ini_get('include_path'));
             foreach ($dirs as $dir) {
                 if (is_readable($dir . DIRECTORY_SEPARATOR . $file)) {
                     return true;
                 }
            }
        } else {
            $fp = @fopen($file, 'r', true);
            if (is_resource($fp)) {
                @fclose($fp);
                return true;
            }
        }
        return false;
    }
    // }}}
}

// }}}
// {{{ class MDB2_Error extends PEAR_Error

/**
 * MDB2_Error implements a class for reporting portable database error
 * messages.
 *
 * @package     MDB2
 * @category    Database
 * @author Stig Bakken <ssb@fast.no>
 */
class MDB2_Error extends PEAR_Error
{
    // {{{ constructor: function MDB2_Error($code = MDB2_ERROR, $mode = PEAR_ERROR_RETURN, $level = E_USER_NOTICE, $debuginfo = null)

    /**
     * MDB2_Error constructor.
     *
     * @param   mixed   MDB2 error code, or string with error message.
     * @param   int     what 'error mode' to operate in
     * @param   int     what error level to use for $mode & PEAR_ERROR_TRIGGER
     * @param   mixed   additional debug info, such as the last query
     */
    function __construct($code = MDB2_ERROR, $mode = PEAR_ERROR_RETURN,
              $level = E_USER_NOTICE, $debuginfo = null, $dummy = null)
    {
        if (null === $code) {
            $code = MDB2_ERROR;
        }
        $this->PEAR_Error('MDB2 Error: '.MDB2::errorMessage($code), $code,
            $mode, $level, $debuginfo);
    }

    // }}}
}

// }}}
// {{{ class MDB2_Driver_Common extends PEAR

/**
 * MDB2_Driver_Common: Base class that is extended by each MDB2 driver
 *
 * @package     MDB2
 * @category    Database
 * @author      Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Driver_Common
{
    // {{{ Variables (Properties)

    /**
     * @var MDB2_Driver_Datatype_Common
     */
    public $datatype;

    /**
     * @var MDB2_Extended
     */
    public $extended;

    /**
     * @var MDB2_Driver_Function_Common
     */
    public $function;

    /**
     * @var MDB2_Driver_Manager_Common
     */
    public $manager;

    /**
     * @var MDB2_Driver_Native_Commonn
     */
    public $native;

    /**
     * @var MDB2_Driver_Reverse_Common
     */
    public $reverse;

    /**
     * index of the MDB2 object within the $GLOBALS['_MDB2_databases'] array
     * @var     int
     * @access  public
     */
    public $db_index = 0;

    /**
     * DSN used for the next query
     * @var     array
     * @access  protected
     */
    public $dsn = array();

    /**
     * DSN that was used to create the current connection
     * @var     array
     * @access  protected
     */
    public $connected_dsn = array();

    /**
     * connection resource
     * @var     mixed
     * @access  protected
     */
    public $connection = 0;

    /**
     * if the current opened connection is a persistent connection
     * @var     bool
     * @access  protected
     */
    public $opened_persistent;

    /**
     * the name of the database for the next query
     * @var     string
     * @access  public
     */
    public $database_name = '';

    /**
     * the name of the database currently selected
     * @var     string
     * @access  protected
     */
    public $connected_database_name = '';

    /**
     * server version information
     * @var     string
     * @access  protected
     */
    public $connected_server_info = '';

    /**
     * list of all supported features of the given driver
     * @var     array
     * @access  public
     */
    public $supported = array(
        'sequences' => false,
        'indexes' => false,
        'affected_rows' => false,
        'summary_functions' => false,
        'order_by_text' => false,
        'transactions' => false,
        'savepoints' => false,
        'current_id' => false,
        'limit_queries' => false,
        'LOBs' => false,
        'replace' => false,
        'sub_selects' => false,
        'triggers' => false,
        'auto_increment' => false,
        'primary_key' => false,
        'result_introspection' => false,
        'prepared_statements' => false,
        'identifier_quoting' => false,
        'pattern_escaping' => false,
        'new_link' => false,
    );

    /**
     * Array of supported options that can be passed to the MDB2 instance.
     *
     * The options can be set during object creation, using
     * MDB2::connect(), MDB2::factory() or MDB2::singleton(). The options can
     * also be set after the object is created, using MDB2::setOptions() or
     * MDB2_Driver_Common::setOption().
     * The list of available option includes:
     * <ul>
     *  <li>$options['ssl'] -> boolean: determines if ssl should be used for connections</li>
     *  <li>$options['field_case'] -> CASE_LOWER|CASE_UPPER: determines what case to force on field/table names</li>
     *  <li>$options['disable_query'] -> boolean: determines if queries should be executed</li>
     *  <li>$options['result_class'] -> string: class used for result sets</li>
     *  <li>$options['buffered_result_class'] -> string: class used for buffered result sets</li>
     *  <li>$options['result_wrap_class'] -> string: class used to wrap result sets into</li>
     *  <li>$options['result_buffering'] -> boolean should results be buffered or not?</li>
     *  <li>$options['fetch_class'] -> string: class to use when fetch mode object is used</li>
     *  <li>$options['persistent'] -> boolean: persistent connection?</li>
     *  <li>$options['debug'] -> integer: numeric debug level</li>
     *  <li>$options['debug_handler'] -> string: function/method that captures debug messages</li>
     *  <li>$options['debug_expanded_output'] -> bool: BC option to determine if more context information should be send to the debug handler</li>
     *  <li>$options['default_text_field_length'] -> integer: default text field length to use</li>
     *  <li>$options['lob_buffer_length'] -> integer: LOB buffer length</li>
     *  <li>$options['log_line_break'] -> string: line-break format</li>
     *  <li>$options['idxname_format'] -> string: pattern for index name</li>
     *  <li>$options['seqname_format'] -> string: pattern for sequence name</li>
     *  <li>$options['savepoint_format'] -> string: pattern for auto generated savepoint names</li>
     *  <li>$options['statement_format'] -> string: pattern for prepared statement names</li>
     *  <li>$options['seqcol_name'] -> string: sequence column name</li>
     *  <li>$options['quote_identifier'] -> boolean: if identifier quoting should be done when check_option is used</li>
     *  <li>$options['use_transactions'] -> boolean: if transaction use should be enabled</li>
     *  <li>$options['decimal_places'] -> integer: number of decimal places to handle</li>
     *  <li>$options['portability'] -> integer: portability constant</li>
     *  <li>$options['modules'] -> array: short to long module name mapping for __call()</li>
     *  <li>$options['emulate_prepared'] -> boolean: force prepared statements to be emulated</li>
     *  <li>$options['datatype_map'] -> array: map user defined datatypes to other primitive datatypes</li>
     *  <li>$options['datatype_map_callback'] -> array: callback function/method that should be called</li>
     *  <li>$options['bindname_format'] -> string: regular expression pattern for named parameters</li>
     *  <li>$options['multi_query'] -> boolean: determines if queries returning multiple result sets should be executed</li>
     *  <li>$options['max_identifiers_length'] -> integer: max identifier length</li>
     *  <li>$options['default_fk_action_onupdate'] -> string: default FOREIGN KEY ON UPDATE action ['RESTRICT'|'NO ACTION'|'SET DEFAULT'|'SET NULL'|'CASCADE']</li>
     *  <li>$options['default_fk_action_ondelete'] -> string: default FOREIGN KEY ON DELETE action ['RESTRICT'|'NO ACTION'|'SET DEFAULT'|'SET NULL'|'CASCADE']</li>
     * </ul>
     *
     * @var     array
     * @access  public
     * @see     MDB2::connect()
     * @see     MDB2::factory()
     * @see     MDB2::singleton()
     * @see     MDB2_Driver_Common::setOption()
     */
    public $options = array(
        'ssl' => false,
        'field_case' => CASE_LOWER,
        'disable_query' => false,
        'result_class' => 'MDB2_Result_%s',
        'buffered_result_class' => 'MDB2_BufferedResult_%s',
        'result_wrap_class' => false,
        'result_buffering' => true,
        'fetch_class' => 'stdClass',
        'persistent' => false,
        'debug' => 0,
        'debug_handler' => 'MDB2_defaultDebugOutput',
        'debug_expanded_output' => false,
        'default_text_field_length' => 4096,
        'lob_buffer_length' => 8192,
        'log_line_break' => "\n",
        'idxname_format' => '%s_idx',
        'seqname_format' => '%s_seq',
        'savepoint_format' => 'MDB2_SAVEPOINT_%s',
        'statement_format' => 'MDB2_STATEMENT_%1$s_%2$s',
        'seqcol_name' => 'sequence',
        'quote_identifier' => false,
        'use_transactions' => true,
        'decimal_places' => 2,
        'portability' => MDB2_PORTABILITY_ALL,
        'modules' => array(
            'ex' => 'Extended',
            'dt' => 'Datatype',
            'mg' => 'Manager',
            'rv' => 'Reverse',
            'na' => 'Native',
            'fc' => 'Function',
        ),
        'emulate_prepared' => false,
        'datatype_map' => array(),
        'datatype_map_callback' => array(),
        'nativetype_map_callback' => array(),
        'lob_allow_url_include' => false,
        'bindname_format' => '(?:\d+)|(?:[a-zA-Z][a-zA-Z0-9_]*)',
        'max_identifiers_length' => 30,
        'default_fk_action_onupdate' => 'RESTRICT',
        'default_fk_action_ondelete' => 'RESTRICT',
    );

    /**
     * string array
     * @var     string
     * @access  public
     */
    public $string_quoting = array(
        'start'  => "'",
        'end'    => "'",
        'escape' => false,
        'escape_pattern' => false,
    );

    /**
     * identifier quoting
     * @var     array
     * @access  public
     */
    public $identifier_quoting = array(
        'start'  => '"',
        'end'    => '"',
        'escape' => '"',
    );

    /**
     * sql comments
     * @var     array
     * @access  protected
     */
    public $sql_comments = array(
        array('start' => '--', 'end' => "\n", 'escape' => false),
        array('start' => '/*', 'end' => '*/', 'escape' => false),
    );

    /**
     * comparision wildcards
     * @var     array
     * @access  protected
     */
    protected $wildcards = array('%', '_');

    /**
     * column alias keyword
     * @var     string
     * @access  protected
     */
    public $as_keyword = ' AS ';

    /**
     * warnings
     * @var     array
     * @access  protected
     */
    public $warnings = array();

    /**
     * string with the debugging information
     * @var     string
     * @access  public
     */
    public $debug_output = '';

    /**
     * determine if there is an open transaction
     * @var     bool
     * @access  protected
     */
    public $in_transaction = false;

    /**
     * the smart transaction nesting depth
     * @var     int
     * @access  protected
     */
    public $nested_transaction_counter = null;

    /**
     * the first error that occured inside a nested transaction
     * @var     MDB2_Error|bool
     * @access  protected
     */
    protected $has_transaction_error = false;

    /**
     * result offset used in the next query
     * @var     int
     * @access  public
     */
    public $offset = 0;

    /**
     * result limit used in the next query
     * @var     int
     * @access  public
     */
    public $limit = 0;

    /**
     * Database backend used in PHP (mysql, odbc etc.)
     * @var     string
     * @access  public
     */
    public $phptype;

    /**
     * Database used with regards to SQL syntax etc.
     * @var     string
     * @access  public
     */
    public $dbsyntax;

    /**
     * the last query sent to the driver
     * @var     string
     * @access  public
     */
    public $last_query;

    /**
     * the default fetchmode used
     * @var     int
     * @access  public
     */
    public $fetchmode = MDB2_FETCHMODE_ORDERED;

    /**
     * array of module instances
     * @var     array
     * @access  protected
     */
    protected $modules = array();

    /**
     * determines of the PHP4 destructor emulation has been enabled yet
     * @var     array
     * @access  protected
     */
    protected $destructor_registered = true;

    /**
     * @var PEAR 
     */
    protected $pear;

    // }}}
    // {{{ constructor: function __construct()

    /**
     * Constructor
     */
    function __construct()
    {
        end($GLOBALS['_MDB2_databases']);
        $db_index = key($GLOBALS['_MDB2_databases']) + 1;
        $GLOBALS['_MDB2_databases'][$db_index] = &$this;
        $this->db_index = $db_index;
        $this->pear = new PEAR;
    }

    // }}}
    // {{{ destructor: function __destruct()

    /**
     *  Destructor
     */
    function __destruct()
    {
        $this->disconnect(false);
    }

    // }}}
    // {{{ function free()

    /**
     * Free the internal references so that the instance can be destroyed
     *
     * @return  bool    true on success, false if result is invalid
     *
     * @access  public
     */
    function free()
    {
        unset($GLOBALS['_MDB2_databases'][$this->db_index]);
        unset($this->db_index);
        return MDB2_OK;
    }

    // }}}
    // {{{ function __toString()

    /**
     * String conversation
     *
     * @return  string representation of the object
     *
     * @access  public
     */
    function __toString()
    {
        $info = get_class($this);
        $info.= ': (phptype = '.$this->phptype.', dbsyntax = '.$this->dbsyntax.')';
        if ($this->connection) {
            $info.= ' [connected]';
        }
        return $info;
    }

    // }}}
    // {{{ function errorInfo($error = null)

    /**
     * This method is used to collect information about an error
     *
     * @param   mixed   error code or resource
     *
     * @return  array   with MDB2 errorcode, native error code, native message
     *
     * @access  public
     */
    function errorInfo($error = null)
    {
        return array($error, null, null);
    }

    // }}}
    // {{{ function &raiseError($code = null, $mode = null, $options = null, $userinfo = null)

    /**
     * This method is used to communicate an error and invoke error
     * callbacks etc.  Basically a wrapper for PEAR::raiseError
     * without the message string.
     *
     * @param mixed  $code     integer error code, or a PEAR error object (all
     *                         other parameters are ignored if this parameter is
     *                         an object
     * @param int    $mode     error mode, see PEAR_Error docs
     * @param mixed  $options  If error mode is PEAR_ERROR_TRIGGER, this is the
     *                         error level (E_USER_NOTICE etc). If error mode is
     *                         PEAR_ERROR_CALLBACK, this is the callback function,
     *                         either as a function name, or as an array of an
     *                         object and method name. For other error modes this
     *                         parameter is ignored.
     * @param string $userinfo Extra debug information. Defaults to the last
     *                         query and native error code.
     * @param string $method   name of the method that triggered the error
     * @param string $dummy1   not used
     * @param bool   $dummy2   not used
     *
     * @return PEAR_Error instance of a PEAR Error object
     * @access public
     * @see    PEAR_Error
     */
    function &raiseError($code = null,
                         $mode = null,
                         $options = null,
                         $userinfo = null,
                         $method = null,
                         $dummy1 = null,
                         $dummy2 = false
    ) {
        $userinfo = "[Error message: $userinfo]\n";
        // The error is yet a MDB2 error object
        if (MDB2::isError($code)) {
            // because we use the static PEAR::raiseError, our global
            // handler should be used if it is set
            if ((null === $mode) && !empty($this->_default_error_mode)) {
                $mode    = $this->_default_error_mode;
                $options = $this->_default_error_options;
            }
            if (null === $userinfo) {
                $userinfo = $code->getUserinfo();
            }
            $code = $code->getCode();
        } elseif ($code == MDB2_ERROR_NOT_FOUND) {
            // extension not loaded: don't call $this->errorInfo() or the script
            // will die
        } elseif (isset($this->connection)) {
            if (!empty($this->last_query)) {
                $userinfo.= "[Last executed query: {$this->last_query}]\n";
            }
            $native_errno = $native_msg = null;
            list($code, $native_errno, $native_msg) = $this->errorInfo($code);
            if ((null !== $native_errno) && $native_errno !== '') {
                $userinfo.= "[Native code: $native_errno]\n";
            }
            if ((null !== $native_msg) && $native_msg !== '') {
                $userinfo.= "[Native message: ". strip_tags($native_msg) ."]\n";
            }
            if (null !== $method) {
                $userinfo = $method.': '.$userinfo;
            }
        }

        $err = $this->pear->raiseError(null, $code, $mode, $options, $userinfo, 'MDB2_Error', true);
        if ($err->getMode() !== PEAR_ERROR_RETURN
            && isset($this->nested_transaction_counter) && !$this->has_transaction_error) {
            $this->has_transaction_error = $err;
        }
        return $err;
    }

    // }}}
    // {{{ function resetWarnings()

    /**
     * reset the warning array
     *
     * @return void
     *
     * @access  public
     */
    function resetWarnings()
    {
        $this->warnings = array();
    }

    // }}}
    // {{{ function getWarnings()

    /**
     * Get all warnings in reverse order.
     * This means that the last warning is the first element in the array
     *
     * @return  array   with warnings
     *
     * @access  public
     * @see     resetWarnings()
     */
    function getWarnings()
    {
        return array_reverse($this->warnings);
    }

    // }}}
    // {{{ function setFetchMode($fetchmode, $object_class = 'stdClass')

    /**
     * Sets which fetch mode should be used by default on queries
     * on this connection
     *
     * @param   int     MDB2_FETCHMODE_ORDERED, MDB2_FETCHMODE_ASSOC
     *                               or MDB2_FETCHMODE_OBJECT
     * @param   string  the class name of the object to be returned
     *                               by the fetch methods when the
     *                               MDB2_FETCHMODE_OBJECT mode is selected.
     *                               If no class is specified by default a cast
     *                               to object from the assoc array row will be
     *                               done.  There is also the possibility to use
     *                               and extend the 'MDB2_row' class.
     *
     * @return  mixed   MDB2_OK or MDB2 Error Object
     *
     * @access  public
     * @see     MDB2_FETCHMODE_ORDERED, MDB2_FETCHMODE_ASSOC, MDB2_FETCHMODE_OBJECT
     */
    function setFetchMode($fetchmode, $object_class = 'stdClass')
    {
        switch ($fetchmode) {
        case MDB2_FETCHMODE_OBJECT:
            $this->options['fetch_class'] = $object_class;
        case MDB2_FETCHMODE_ORDERED:
        case MDB2_FETCHMODE_ASSOC:
            $this->fetchmode = $fetchmode;
            break;
        default:
            return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'invalid fetchmode mode', __FUNCTION__);
        }

        return MDB2_OK;
    }

    // }}}
    // {{{ function setOption($option, $value)

    /**
     * set the option for the db class
     *
     * @param   string  option name
     * @param   mixed   value for the option
     *
     * @return  mixed   MDB2_OK or MDB2 Error Object
     *
     * @access  public
     */
    function setOption($option, $value)
    {
        if (array_key_exists($option, $this->options)) {
            $this->options[$option] = $value;
            return MDB2_OK;
        }
        return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            "unknown option $option", __FUNCTION__);
    }

    // }}}
    // {{{ function getOption($option)

    /**
     * Returns the value of an option
     *
     * @param   string  option name
     *
     * @return  mixed   the option value or error object
     *
     * @access  public
     */
    function getOption($option)
    {
        if (array_key_exists($option, $this->options)) {
            return $this->options[$option];
        }
        return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            "unknown option $option", __FUNCTION__);
    }

    // }}}
    // {{{ function debug($message, $scope = '', $is_manip = null)

    /**
     * set a debug message
     *
     * @param   string  message that should be appended to the debug variable
     * @param   string  usually the method name that triggered the debug call:
     *                  for example 'query', 'prepare', 'execute', 'parameters',
     *                  'beginTransaction', 'commit', 'rollback'
     * @param   array   contains context information about the debug() call
     *                  common keys are: is_manip, time, result etc.
     *
     * @return void
     *
     * @access  public
     */
    function debug($message, $scope = '', $context = array())
    {
        if ($this->options['debug'] && $this->options['debug_handler']) {
            if (!$this->options['debug_expanded_output']) {
                if (!empty($context['when']) && $context['when'] !== 'pre') {
                    return null;
                }
                $context = empty($context['is_manip']) ? false : $context['is_manip'];
            }
            return call_user_func_array($this->options['debug_handler'], array(&$this, $scope, $message, $context));
        }
        return null;
    }

    // }}}
    // {{{ function getDebugOutput()

    /**
     * output debug info
     *
     * @return  string  content of the debug_output class variable
     *
     * @access  public
     */
    function getDebugOutput()
    {
        return $this->debug_output;
    }

    // }}}
    // {{{ function escape($text)

    /**
     * Quotes a string so it can be safely used in a query. It will quote
     * the text so it can safely be used within a query.
     *
     * @param   string  the input string to quote
     * @param   bool    escape wildcards
     *
     * @return  string  quoted string
     *
     * @access  public
     */
    function escape($text, $escape_wildcards = false)
    {
        if ($escape_wildcards) {
            $text = $this->escapePattern($text);
        }

        $text = str_replace($this->string_quoting['end'], $this->string_quoting['escape'] . $this->string_quoting['end'], $text);
        return $text;
    }

    // }}}
    // {{{ function escapePattern($text)

    /**
     * Quotes pattern (% and _) characters in a string)
     *
     * @param   string  the input string to quote
     *
     * @return  string  quoted string
     *
     * @access  public
     */
    function escapePattern($text)
    {
        if ($this->string_quoting['escape_pattern']) {
            $text = str_replace($this->string_quoting['escape_pattern'], $this->string_quoting['escape_pattern'] . $this->string_quoting['escape_pattern'], $text);
            foreach ($this->wildcards as $wildcard) {
                $text = str_replace($wildcard, $this->string_quoting['escape_pattern'] . $wildcard, $text);
            }
        }
        return $text;
    }

    // }}}
    // {{{ function quoteIdentifier($str, $check_option = false)

    /**
     * Quote a string so it can be safely used as a table or column name
     *
     * Delimiting style depends on which database driver is being used.
     *
     * NOTE: just because you CAN use delimited identifiers doesn't mean
     * you SHOULD use them.  In general, they end up causing way more
     * problems than they solve.
     *
     * NOTE: if you have table names containing periods, don't use this method
     * (@see bug #11906)
     *
     * Portability is broken by using the following characters inside
     * delimited identifiers:
     *   + backtick (<kbd>`</kbd>) -- due to MySQL
     *   + double quote (<kbd>"</kbd>) -- due to Oracle
     *   + brackets (<kbd>[</kbd> or <kbd>]</kbd>) -- due to Access
     *
     * Delimited identifiers are known to generally work correctly under
     * the following drivers:
     *   + mssql
     *   + mysql
     *   + mysqli
     *   + oci8
     *   + pgsql
     *   + sqlite
     *
     * InterBase doesn't seem to be able to use delimited identifiers
     * via PHP 4.  They work fine under PHP 5.
     *
     * @param   string  identifier name to be quoted
     * @param   bool    check the 'quote_identifier' option
     *
     * @return  string  quoted identifier string
     *
     * @access  public
     */
    function quoteIdentifier($str, $check_option = false)
    {
        if ($check_option && !$this->options['quote_identifier']) {
            return $str;
        }
        $str = str_replace($this->identifier_quoting['end'], $this->identifier_quoting['escape'] . $this->identifier_quoting['end'], $str);
        $parts = explode('.', $str);
        foreach (array_keys($parts) as $k) {
            $parts[$k] = $this->identifier_quoting['start'] . $parts[$k] . $this->identifier_quoting['end'];
        }
        return implode('.', $parts);
    }

    // }}}
    // {{{ function getAsKeyword()

    /**
     * Gets the string to alias column
     *
     * @return string to use when aliasing a column
     */
    function getAsKeyword()
    {
        return $this->as_keyword;
    }

    // }}}
    // {{{ function getConnection()

    /**
     * Returns a native connection
     *
     * @return  mixed   a valid MDB2 connection object,
     *                  or a MDB2 error object on error
     *
     * @access  public
     */
    function getConnection()
    {
        $result = $this->connect();
        if (MDB2::isError($result)) {
            return $result;
        }
        return $this->connection;
    }

     // }}}
    // {{{ function _fixResultArrayValues(&$row, $mode)

    /**
     * Do all necessary conversions on result arrays to fix DBMS quirks
     *
     * @param   array   the array to be fixed (passed by reference)
     * @param   array   bit-wise addition of the required portability modes
     *
     * @return  void
     *
     * @access  protected
     */
    function _fixResultArrayValues(&$row, $mode)
    {
        switch ($mode) {
        case MDB2_PORTABILITY_EMPTY_TO_NULL:
            foreach ($row as $key => $value) {
                if ($value === '') {
                    $row[$key] = null;
                }
            }
            break;
        case MDB2_PORTABILITY_RTRIM:
            foreach ($row as $key => $value) {
                if (is_string($value)) {
                    $row[$key] = rtrim($value);
                }
            }
            break;
        case MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES:
            $tmp_row = array();
            foreach ($row as $key => $value) {
                $tmp_row[preg_replace('/^(?:.*\.)?([^.]+)$/', '\\1', $key)] = $value;
            }
            $row = $tmp_row;
            break;
        case (MDB2_PORTABILITY_RTRIM + MDB2_PORTABILITY_EMPTY_TO_NULL):
            foreach ($row as $key => $value) {
                if ($value === '') {
                    $row[$key] = null;
                } elseif (is_string($value)) {
                    $row[$key] = rtrim($value);
                }
            }
            break;
        case (MDB2_PORTABILITY_RTRIM + MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES):
            $tmp_row = array();
            foreach ($row as $key => $value) {
                if (is_string($value)) {
                    $value = rtrim($value);
                }
                $tmp_row[preg_replace('/^(?:.*\.)?([^.]+)$/', '\\1', $key)] = $value;
            }
            $row = $tmp_row;
            break;
        case (MDB2_PORTABILITY_EMPTY_TO_NULL + MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES):
            $tmp_row = array();
            foreach ($row as $key => $value) {
                if ($value === '') {
                    $value = null;
                }
                $tmp_row[preg_replace('/^(?:.*\.)?([^.]+)$/', '\\1', $key)] = $value;
            }
            $row = $tmp_row;
            break;
        case (MDB2_PORTABILITY_RTRIM + MDB2_PORTABILITY_EMPTY_TO_NULL + MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES):
            $tmp_row = array();
            foreach ($row as $key => $value) {
                if ($value === '') {
                    $value = null;
                } elseif (is_string($value)) {
                    $value = rtrim($value);
                }
                $tmp_row[preg_replace('/^(?:.*\.)?([^.]+)$/', '\\1', $key)] = $value;
            }
            $row = $tmp_row;
            break;
        }
    }

    // }}}
    // {{{ function loadModule($module, $property = null, $phptype_specific = null)

    /**
     * loads a module
     *
     * @param   string  name of the module that should be loaded
     *                  (only used for error messages)
     * @param   string  name of the property into which the class will be loaded
     * @param   bool    if the class to load for the module is specific to the
     *                  phptype
     *
     * @return  object  on success a reference to the given module is returned
     *                  and on failure a PEAR error
     *
     * @access  public
     */
    function loadModule($module, $property = null, $phptype_specific = null)
    {
        if (!$property) {
            $property = strtolower($module);
        }

        if (!isset($this->{$property})) {
            $version = $phptype_specific;
            if ($phptype_specific !== false) {
                $version = true;
                $class_name = 'MDB2_Driver_'.$module.'_'.$this->phptype;
                $file_name = str_replace('_', DIRECTORY_SEPARATOR, $class_name).'.php';
            }
            if ($phptype_specific === false
                || (!MDB2::classExists($class_name) && !MDB2::fileExists($file_name))
            ) {
                $version = false;
                $class_name = 'MDB2_'.$module;
                $file_name = str_replace('_', DIRECTORY_SEPARATOR, $class_name).'.php';
            }

            $err = MDB2::loadClass($class_name, $this->getOption('debug'));
            if (MDB2::isError($err)) {
                return $err;
            }

            // load module in a specific version
            if ($version) {
                if (method_exists($class_name, 'getClassName')) {
                    $class_name_new = call_user_func(array($class_name, 'getClassName'), $this->db_index);
                    if ($class_name != $class_name_new) {
                        $class_name = $class_name_new;
                        $err = MDB2::loadClass($class_name, $this->getOption('debug'));
                        if (MDB2::isError($err)) {
                            return $err;
                        }
                    }
                }
            }

            if (!MDB2::classExists($class_name)) {
                $err = $this->raiseError(MDB2_ERROR_LOADMODULE, null, null,
                    "unable to load module '$module' into property '$property'", __FUNCTION__);
                return $err;
            }
            $this->{$property} = new $class_name($this->db_index);
            $this->modules[$module] = $this->{$property};
            if ($version) {
                // this will be used in the connect method to determine if the module
                // needs to be loaded with a different version if the server
                // version changed in between connects
                $this->loaded_version_modules[] = $property;
            }
        }

        return $this->{$property};
    }

    // }}}
    // {{{ function __call($method, $params)

    /**
     * Calls a module method using the __call magic method
     *
     * @param   string  Method name.
     * @param   array   Arguments.
     *
     * @return  mixed   Returned value.
     */
    function __call($method, $params)
    {
        $module = null;
        if (preg_match('/^([a-z]+)([A-Z])(.*)$/', $method, $match)
            && isset($this->options['modules'][$match[1]])
        ) {
            $module = $this->options['modules'][$match[1]];
            $method = strtolower($match[2]).$match[3];
            if (!isset($this->modules[$module]) || !is_object($this->modules[$module])) {
                $result = $this->loadModule($module);
                if (MDB2::isError($result)) {
                    return $result;
                }
            }
        } else {
            foreach ($this->modules as $key => $foo) {
                if (is_object($this->modules[$key])
                    && method_exists($this->modules[$key], $method)
                ) {
                    $module = $key;
                    break;
                }
            }
        }
        if (null !== $module) {
            return call_user_func_array(array(&$this->modules[$module], $method), $params);
        }
        trigger_error(sprintf('Call to undefined function: %s::%s().', get_class($this), $method), E_USER_ERROR);
    }

    // }}}
    // {{{ function beginTransaction($savepoint = null)

    /**
     * Start a transaction or set a savepoint.
     *
     * @param   string  name of a savepoint to set
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function beginTransaction($savepoint = null)
    {
        $this->debug('Starting transaction', __FUNCTION__, array('is_manip' => true, 'savepoint' => $savepoint));
        return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'transactions are not supported', __FUNCTION__);
    }

    // }}}
    // {{{ function commit($savepoint = null)

    /**
     * Commit the database changes done during a transaction that is in
     * progress or release a savepoint. This function may only be called when
     * auto-committing is disabled, otherwise it will fail. Therefore, a new
     * transaction is implicitly started after committing the pending changes.
     *
     * @param   string  name of a savepoint to release
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function commit($savepoint = null)
    {
        $this->debug('Committing transaction/savepoint', __FUNCTION__, array('is_manip' => true, 'savepoint' => $savepoint));
        return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'commiting transactions is not supported', __FUNCTION__);
    }

    // }}}
    // {{{ function rollback($savepoint = null)

    /**
     * Cancel any database changes done during a transaction or since a specific
     * savepoint that is in progress. This function may only be called when
     * auto-committing is disabled, otherwise it will fail. Therefore, a new
     * transaction is implicitly started after canceling the pending changes.
     *
     * @param   string  name of a savepoint to rollback to
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function rollback($savepoint = null)
    {
        $this->debug('Rolling back transaction/savepoint', __FUNCTION__, array('is_manip' => true, 'savepoint' => $savepoint));
        return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'rolling back transactions is not supported', __FUNCTION__);
    }

    // }}}
    // {{{ function inTransaction($ignore_nested = false)

    /**
     * If a transaction is currently open.
     *
     * @param   bool    if the nested transaction count should be ignored
     * @return  int|bool    - an integer with the nesting depth is returned if a
     *                      nested transaction is open
     *                      - true is returned for a normal open transaction
     *                      - false is returned if no transaction is open
     *
     * @access  public
     */
    function inTransaction($ignore_nested = false)
    {
        if (!$ignore_nested && isset($this->nested_transaction_counter)) {
            return $this->nested_transaction_counter;
        }
        return $this->in_transaction;
    }

    // }}}
    // {{{ function setTransactionIsolation($isolation)

    /**
     * Set the transacton isolation level.
     *
     * @param   string  standard isolation level
     *                  READ UNCOMMITTED (allows dirty reads)
     *                  READ COMMITTED (prevents dirty reads)
     *                  REPEATABLE READ (prevents nonrepeatable reads)
     *                  SERIALIZABLE (prevents phantom reads)
     * @param   array some transaction options:
     *                  'wait' => 'WAIT' | 'NO WAIT'
     *                  'rw'   => 'READ WRITE' | 'READ ONLY'
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     * @since   2.1.1
     */
    function setTransactionIsolation($isolation, $options = array())
    {
        $this->debug('Setting transaction isolation level', __FUNCTION__, array('is_manip' => true));
        return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'isolation level setting is not supported', __FUNCTION__);
    }

    // }}}
    // {{{ function beginNestedTransaction($savepoint = false)

    /**
     * Start a nested transaction.
     *
     * @return  mixed   MDB2_OK on success/savepoint name, a MDB2 error on failure
     *
     * @access  public
     * @since   2.1.1
     */
    function beginNestedTransaction()
    {
        if ($this->in_transaction) {
            ++$this->nested_transaction_counter;
            $savepoint = sprintf($this->options['savepoint_format'], $this->nested_transaction_counter);
            if ($this->supports('savepoints') && $savepoint) {
                return $this->beginTransaction($savepoint);
            }
            return MDB2_OK;
        }
        $this->has_transaction_error = false;
        $result = $this->beginTransaction();
        $this->nested_transaction_counter = 1;
        return $result;
    }

    // }}}
    // {{{ function completeNestedTransaction($force_rollback = false, $release = false)

    /**
     * Finish a nested transaction by rolling back if an error occured or
     * committing otherwise.
     *
     * @param   bool    if the transaction should be rolled back regardless
     *                  even if no error was set within the nested transaction
     * @return  mixed   MDB_OK on commit/counter decrementing, false on rollback
     *                  and a MDB2 error on failure
     *
     * @access  public
     * @since   2.1.1
     */
    function completeNestedTransaction($force_rollback = false)
    {
        if ($this->nested_transaction_counter > 1) {
            $savepoint = sprintf($this->options['savepoint_format'], $this->nested_transaction_counter);
            if ($this->supports('savepoints') && $savepoint) {
                if ($force_rollback || $this->has_transaction_error) {
                    $result = $this->rollback($savepoint);
                    if (!MDB2::isError($result)) {
                        $result = false;
                        $this->has_transaction_error = false;
                    }
                } else {
                    $result = $this->commit($savepoint);
                }
            } else {
                $result = MDB2_OK;
            }
            --$this->nested_transaction_counter;
            return $result;
        }

        $this->nested_transaction_counter = null;
        $result = MDB2_OK;

        // transaction has not yet been rolled back
        if ($this->in_transaction) {
            if ($force_rollback || $this->has_transaction_error) {
                $result = $this->rollback();
                if (!MDB2::isError($result)) {
                    $result = false;
                }
            } else {
                $result = $this->commit();
            }
        }
        $this->has_transaction_error = false;
        return $result;
    }

    // }}}
    // {{{ function failNestedTransaction($error = null, $immediately = false)

    /**
     * Force setting nested transaction to failed.
     *
     * @param   mixed   value to return in getNestededTransactionError()
     * @param   bool    if the transaction should be rolled back immediately
     * @return  bool    MDB2_OK
     *
     * @access  public
     * @since   2.1.1
     */
    function failNestedTransaction($error = null, $immediately = false)
    {
        if (null !== $error) {
            $error = $this->has_transaction_error ? $this->has_transaction_error : true;
        } elseif (!$error) {
            $error = true;
        }
        $this->has_transaction_error = $error;
        if (!$immediately) {
            return MDB2_OK;
        }
        return $this->rollback();
    }

    // }}}
    // {{{ function getNestedTransactionError()

    /**
     * The first error that occured since the transaction start.
     *
     * @return  MDB2_Error|bool     MDB2 error object if an error occured or false.
     *
     * @access  public
     * @since   2.1.1
     */
    function getNestedTransactionError()
    {
        return $this->has_transaction_error;
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to the database
     *
     * @return true on success, MDB2 Error Object on failure
     */
    function connect()
    {
        return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ databaseExists()

    /**
     * check if given database name is exists?
     *
     * @param string $name    name of the database that should be checked
     *
     * @return mixed true/false on success, a MDB2 error on failure
     * @access public
     */
    function databaseExists($name)
    {
        return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ setCharset($charset, $connection = null)

    /**
     * Set the charset on the current connection
     *
     * @param string    charset
     * @param resource  connection handle
     *
     * @return true on success, MDB2 Error Object on failure
     */
    function setCharset($charset, $connection = null)
    {
        return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ function disconnect($force = true)

    /**
     * Log out and disconnect from the database.
     *
     * @param boolean $force whether the disconnect should be forced even if the
     *                       connection is opened persistently
     *
     * @return mixed true on success, false if not connected and error object on error
     *
     * @access  public
     */
    function disconnect($force = true)
    {
        $this->connection = 0;
        $this->connected_dsn = array();
        $this->connected_database_name = '';
        $this->opened_persistent = null;
        $this->connected_server_info = '';
        $this->in_transaction = null;
        $this->nested_transaction_counter = null;
        return MDB2_OK;
    }

    // }}}
    // {{{ function setDatabase($name)

    /**
     * Select a different database
     *
     * @param   string  name of the database that should be selected
     *
     * @return  string  name of the database previously connected to
     *
     * @access  public
     */
    function setDatabase($name)
    {
        $previous_database_name = (isset($this->database_name)) ? $this->database_name : '';
        $this->database_name = $name;
        if (!empty($this->connected_database_name) && ($this->connected_database_name != $this->database_name)) {
            $this->disconnect(false);
        }
        return $previous_database_name;
    }

    // }}}
    // {{{ function getDatabase()

    /**
     * Get the current database
     *
     * @return  string  name of the database
     *
     * @access  public
     */
    function getDatabase()
    {
        return $this->database_name;
    }

    // }}}
    // {{{ function setDSN($dsn)

    /**
     * set the DSN
     *
     * @param   mixed   DSN string or array
     *
     * @return  MDB2_OK
     *
     * @access  public
     */
    function setDSN($dsn)
    {
        $dsn_default = $GLOBALS['_MDB2_dsninfo_default'];
        $dsn = MDB2::parseDSN($dsn);
        if (array_key_exists('database', $dsn)) {
            $this->database_name = $dsn['database'];
            unset($dsn['database']);
        }
        $this->dsn = array_merge($dsn_default, $dsn);
        return $this->disconnect(false);
    }

    // }}}
    // {{{ function getDSN($type = 'string', $hidepw = false)

    /**
     * return the DSN as a string
     *
     * @param   string  format to return ("array", "string")
     * @param   string  string to hide the password with
     *
     * @return  mixed   DSN in the chosen type
     *
     * @access  public
     */
    function getDSN($type = 'string', $hidepw = false)
    {
        $dsn = array_merge($GLOBALS['_MDB2_dsninfo_default'], $this->dsn);
        $dsn['phptype'] = $this->phptype;
        $dsn['database'] = $this->database_name;
        if ($hidepw) {
            $dsn['password'] = $hidepw;
        }
        switch ($type) {
        // expand to include all possible options
        case 'string':
           $dsn = $dsn['phptype'].
               ($dsn['dbsyntax'] ? ('('.$dsn['dbsyntax'].')') : '').
               '://'.$dsn['username'].':'.
                $dsn['password'].'@'.$dsn['hostspec'].
                ($dsn['port'] ? (':'.$dsn['port']) : '').
                '/'.$dsn['database'];
            break;
        case 'array':
        default:
            break;
        }
        return $dsn;
    }

    // }}}
    // {{{ _isNewLinkSet()

    /**
     * Check if the 'new_link' option is set
     *
     * @return boolean
     *
     * @access protected
     */
    function _isNewLinkSet()
    {
        return (isset($this->dsn['new_link'])
            && ($this->dsn['new_link'] === true
             || (is_string($this->dsn['new_link']) && preg_match('/^true$/i', $this->dsn['new_link']))
             || (is_numeric($this->dsn['new_link']) && 0 != (int)$this->dsn['new_link'])
            )
        );
    }

    // }}}
    // {{{ function &standaloneQuery($query, $types = null, $is_manip = false)

   /**
     * execute a query as database administrator
     *
     * @param   string  the SQL query
     * @param   mixed   array that contains the types of the columns in
     *                        the result set
     * @param   bool    if the query is a manipulation query
     *
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function standaloneQuery($query, $types = null, $is_manip = false)
    {
        $offset = $this->offset;
        $limit = $this->limit;
        $this->offset = $this->limit = 0;
        $query = $this->_modifyQuery($query, $is_manip, $limit, $offset);

        $connection = $this->getConnection();
        if (MDB2::isError($connection)) {
            return $connection;
        }

        $result = $this->_doQuery($query, $is_manip, $connection, false);
        if (MDB2::isError($result)) {
            return $result;
        }

        if ($is_manip) {
            $affected_rows =  $this->_affectedRows($connection, $result);
            return $affected_rows;
        }
        $result = $this->_wrapResult($result, $types, true, true, $limit, $offset);
        return $result;
    }

    // }}}
    // {{{ function _modifyQuery($query, $is_manip, $limit, $offset)

    /**
     * Changes a query string for various DBMS specific reasons
     *
     * @param   string  query to modify
     * @param   bool    if it is a DML query
     * @param   int  limit the number of rows
     * @param   int  start reading from given offset
     *
     * @return  string  modified query
     *
     * @access  protected
     */
    function _modifyQuery($query, $is_manip, $limit, $offset)
    {
        return $query;
    }

    // }}}
    // {{{ function &_doQuery($query, $is_manip = false, $connection = null, $database_name = null)

    /**
     * Execute a query
     * @param   string  query
     * @param   bool    if the query is a manipulation query
     * @param   resource connection handle
     * @param   string  database name
     *
     * @return  result or error object
     *
     * @access  protected
     */
    function _doQuery($query, $is_manip = false, $connection = null, $database_name = null)
    {
        $this->last_query = $query;
        $result = $this->debug($query, 'query', array('is_manip' => $is_manip, 'when' => 'pre'));
        if ($result) {
            if (MDB2::isError($result)) {
                return $result;
            }
            $query = $result;
        }
        $err = MDB2_Driver_Common::raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
        return $err;
    }

    // }}}
    // {{{ function _affectedRows($connection, $result = null)

    /**
     * Returns the number of rows affected
     *
     * @param   resource result handle
     * @param   resource connection handle
     *
     * @return  mixed   MDB2 Error Object or the number of rows affected
     *
     * @access  private
     */
    function _affectedRows($connection, $result = null)
    {
        return MDB2_Driver_Common::raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ function &exec($query)

    /**
     * Execute a manipulation query to the database and return the number of affected rows
     *
     * @param   string  the SQL query
     *
     * @return  mixed   number of affected rows on success, a MDB2 error on failure
     *
     * @access  public
     */
    function exec($query)
    {
        $offset = $this->offset;
        $limit = $this->limit;
        $this->offset = $this->limit = 0;
        $query = $this->_modifyQuery($query, true, $limit, $offset);

        $connection = $this->getConnection();
        if (MDB2::isError($connection)) {
            return $connection;
        }

        $result = $this->_doQuery($query, true, $connection, $this->database_name);
        if (MDB2::isError($result)) {
            return $result;
        }

        $affectedRows = $this->_affectedRows($connection, $result);
        return $affectedRows;
    }

    // }}}
    // {{{ function &query($query, $types = null, $result_class = true, $result_wrap_class = false)

    /**
     * Send a query to the database and return any results
     *
     * @param   string  the SQL query
     * @param   mixed   array that contains the types of the columns in
     *                        the result set
     * @param   mixed   string which specifies which result class to use
     * @param   mixed   string which specifies which class to wrap results in
     *
     * @return mixed   an MDB2_Result handle on success, a MDB2 error on failure
     *
     * @access  public
     */
    function query($query, $types = null, $result_class = true, $result_wrap_class = true)
    {
        $offset = $this->offset;
        $limit = $this->limit;
        $this->offset = $this->limit = 0;
        $query = $this->_modifyQuery($query, false, $limit, $offset);

        $connection = $this->getConnection();
        if (MDB2::isError($connection)) {
            return $connection;
        }

        $result = $this->_doQuery($query, false, $connection, $this->database_name);
        if (MDB2::isError($result)) {
            return $result;
        }

        $result = $this->_wrapResult($result, $types, $result_class, $result_wrap_class, $limit, $offset);
        return $result;
    }

    // }}}
    // {{{ function _wrapResult($result_resource, $types = array(), $result_class = true, $result_wrap_class = false, $limit = null, $offset = null)

    /**
     * wrap a result set into the correct class
     *
     * @param   resource result handle
     * @param   mixed   array that contains the types of the columns in
     *                        the result set
     * @param   mixed   string which specifies which result class to use
     * @param   mixed   string which specifies which class to wrap results in
     * @param   string  number of rows to select
     * @param   string  first row to select
     *
     * @return mixed   an MDB2_Result, a MDB2 error on failure
     *
     * @access  protected
     */
    function _wrapResult($result_resource, $types = array(), $result_class = true,
        $result_wrap_class = true, $limit = null, $offset = null)
    {
        if ($types === true) {
            if ($this->supports('result_introspection')) {
                $this->loadModule('Reverse', null, true);
                $tableInfo = $this->reverse->tableInfo($result_resource);
                if (MDB2::isError($tableInfo)) {
                    return $tableInfo;
                }
                $types = array();
                $types_assoc = array();
                foreach ($tableInfo as $field) {
                    $types[] = $field['mdb2type'];
                    $types_assoc[$field['name']] = $field['mdb2type'];
                }
            } else {
                $types = null;
            }
        }

        if ($result_class === true) {
            $result_class = $this->options['result_buffering']
                ? $this->options['buffered_result_class'] : $this->options['result_class'];
        }

        if ($result_class) {
            $class_name = sprintf($result_class, $this->phptype);
            if (!MDB2::classExists($class_name)) {
                $err = MDB2_Driver_Common::raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                    'result class does not exist '.$class_name, __FUNCTION__);
                return $err;
            }
            $result = new $class_name($this, $result_resource, $limit, $offset);
            if (!MDB2::isResultCommon($result)) {
                $err = MDB2_Driver_Common::raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                    'result class is not extended from MDB2_Result_Common', __FUNCTION__);
                return $err;
            }

            if (!empty($types)) {
                $err = $result->setResultTypes($types);
                if (MDB2::isError($err)) {
                    $result->free();
                    return $err;
                }
            }
            if (!empty($types_assoc)) {
                $err = $result->setResultTypes($types_assoc);
                if (MDB2::isError($err)) {
                    $result->free();
                    return $err;
                }
            }

            if ($result_wrap_class === true) {
                $result_wrap_class = $this->options['result_wrap_class'];
            }
            if ($result_wrap_class) {
                if (!MDB2::classExists($result_wrap_class)) {
                    $err = MDB2_Driver_Common::raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                        'result wrap class does not exist '.$result_wrap_class, __FUNCTION__);
                    return $err;
                }
                $result = new $result_wrap_class($result, $this->fetchmode);
            }

            return $result;
        }

        return $result_resource;
    }

    // }}}
    // {{{ function getServerVersion($native = false)

    /**
     * return version information about the server
     *
     * @param   bool    determines if the raw version string should be returned
     *
     * @return  mixed   array with version information or row string
     *
     * @access  public
     */
    function getServerVersion($native = false)
    {
        return MDB2_Driver_Common::raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ function setLimit($limit, $offset = null)

    /**
     * set the range of the next query
     *
     * @param   string  number of rows to select
     * @param   string  first row to select
     *
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function setLimit($limit, $offset = null)
    {
        if (!$this->supports('limit_queries')) {
            return MDB2_Driver_Common::raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'limit is not supported by this driver', __FUNCTION__);
        }
        $limit = (int)$limit;
        if ($limit < 0) {
            return MDB2_Driver_Common::raiseError(MDB2_ERROR_SYNTAX, null, null,
                'it was not specified a valid selected range row limit', __FUNCTION__);
        }
        $this->limit = $limit;
        if (null !== $offset) {
            $offset = (int)$offset;
            if ($offset < 0) {
                return MDB2_Driver_Common::raiseError(MDB2_ERROR_SYNTAX, null, null,
                    'it was not specified a valid first selected range row', __FUNCTION__);
            }
            $this->offset = $offset;
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ function subSelect($query, $type = false)

    /**
     * simple subselect emulation: leaves the query untouched for all RDBMS
     * that support subselects
     *
     * @param   string  the SQL query for the subselect that may only
     *                      return a column
     * @param   string  determines type of the field
     *
     * @return  string  the query
     *
     * @access  public
     */
    function subSelect($query, $type = false)
    {
        if ($this->supports('sub_selects') === true) {
            return $query;
        }

        if (!$this->supports('sub_selects')) {
            return MDB2_Driver_Common::raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'method not implemented', __FUNCTION__);
        }

        $col = $this->queryCol($query, $type);
        if (MDB2::isError($col)) {
            return $col;
        }
        if (!is_array($col) || count($col) == 0) {
            return 'NULL';
        }
        if ($type) {
            $this->loadModule('Datatype', null, true);
            return $this->datatype->implodeArray($col, $type);
        }
        return implode(', ', $col);
    }

    // }}}
    // {{{ function replace($table, $fields)

    /**
     * Execute a SQL REPLACE query. A REPLACE query is identical to a INSERT
     * query, except that if there is already a row in the table with the same
     * key field values, the old row is deleted before the new row is inserted.
     *
     * The REPLACE type of query does not make part of the SQL standards. Since
     * practically only MySQL and SQLite implement it natively, this type of
     * query isemulated through this method for other DBMS using standard types
     * of queries inside a transaction to assure the atomicity of the operation.
     *
     * @param   string  name of the table on which the REPLACE query will
     *       be executed.
     * @param   array   associative array   that describes the fields and the
     *       values that will be inserted or updated in the specified table. The
     *       indexes of the array are the names of all the fields of the table.
     *       The values of the array are also associative arrays that describe
     *       the values and other properties of the table fields.
     *
     *       Here follows a list of field properties that need to be specified:
     *
     *       value
     *           Value to be assigned to the specified field. This value may be
     *           of specified in database independent type format as this
     *           function can perform the necessary datatype conversions.
     *
     *           Default: this property is required unless the Null property is
     *           set to 1.
     *
     *       type
     *           Name of the type of the field. Currently, all types MDB2
     *           are supported except for clob and blob.
     *
     *           Default: no type conversion
     *
     *       null
     *           bool    property that indicates that the value for this field
     *           should be set to null.
     *
     *           The default value for fields missing in INSERT queries may be
     *           specified the definition of a table. Often, the default value
     *           is already null, but since the REPLACE may be emulated using
     *           an UPDATE query, make sure that all fields of the table are
     *           listed in this function argument array.
     *
     *           Default: 0
     *
     *       key
     *           bool    property that indicates that this field should be
     *           handled as a primary key or at least as part of the compound
     *           unique index of the table that will determine the row that will
     *           updated if it exists or inserted a new row otherwise.
     *
     *           This function will fail if no key field is specified or if the
     *           value of a key field is set to null because fields that are
     *           part of unique index they may not be null.
     *
     *           Default: 0
     *
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function replace($table, $fields)
    {
        if (!$this->supports('replace')) {
            return MDB2_Driver_Common::raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'replace query is not supported', __FUNCTION__);
        }
        $count = count($fields);
        $condition = $values = array();
        for ($colnum = 0, reset($fields); $colnum < $count; next($fields), $colnum++) {
            $name = key($fields);
            if (isset($fields[$name]['null']) && $fields[$name]['null']) {
                $value = 'NULL';
            } else {
                $type = isset($fields[$name]['type']) ? $fields[$name]['type'] : null;
                $value = $this->quote($fields[$name]['value'], $type);
            }
            $values[$name] = $value;
            if (isset($fields[$name]['key']) && $fields[$name]['key']) {
                if ($value === 'NULL') {
                    return MDB2_Driver_Common::raiseError(MDB2_ERROR_CANNOT_REPLACE, null, null,
                        'key value '.$name.' may not be NULL', __FUNCTION__);
                }
                $condition[] = $this->quoteIdentifier($name, true) . '=' . $value;
            }
        }
        if (empty($condition)) {
            return MDB2_Driver_Common::raiseError(MDB2_ERROR_CANNOT_REPLACE, null, null,
                'not specified which fields are keys', __FUNCTION__);
        }

        $result = null;
        $in_transaction = $this->in_transaction;
        if (!$in_transaction && MDB2::isError($result = $this->beginTransaction())) {
            return $result;
        }

        $connection = $this->getConnection();
        if (MDB2::isError($connection)) {
            return $connection;
        }

        $condition = ' WHERE '.implode(' AND ', $condition);
        $query = 'DELETE FROM ' . $this->quoteIdentifier($table, true) . $condition;
        $result = $this->_doQuery($query, true, $connection);
        if (!MDB2::isError($result)) {
            $affected_rows = $this->_affectedRows($connection, $result);
            $insert = '';
            foreach ($values as $key => $value) {
                $insert .= ($insert?', ':'') . $this->quoteIdentifier($key, true);
            }
            $values = implode(', ', $values);
            $query = 'INSERT INTO '. $this->quoteIdentifier($table, true) . "($insert) VALUES ($values)";
            $result = $this->_doQuery($query, true, $connection);
            if (!MDB2::isError($result)) {
                $affected_rows += $this->_affectedRows($connection, $result);;
            }
        }

        if (!$in_transaction) {
            if (MDB2::isError($result)) {
                $this->rollback();
            } else {
                $result = $this->commit();
            }
        }

        if (MDB2::isError($result)) {
            return $result;
        }

        return $affected_rows;
    }

    // }}}
    // {{{ function &prepare($query, $types = null, $result_types = null, $lobs = array())

    /**
     * Prepares a query for multiple execution with execute().
     * With some database backends, this is emulated.
     * prepare() requires a generic query as string like
     * 'INSERT INTO numbers VALUES(?,?)' or
     * 'INSERT INTO numbers VALUES(:foo,:bar)'.
     * The ? and :name and are placeholders which can be set using
     * bindParam() and the query can be sent off using the execute() method.
     * The allowed format for :name can be set with the 'bindname_format' option.
     *
     * @param   string  the query to prepare
     * @param   mixed   array that contains the types of the placeholders
     * @param   mixed   array that contains the types of the columns in
     *                        the result set or MDB2_PREPARE_RESULT, if set to
     *                        MDB2_PREPARE_MANIP the query is handled as a manipulation query
     * @param   mixed   key (field) value (parameter) pair for all lob placeholders
     *
     * @return  mixed   resource handle for the prepared query on success,
     *                  a MDB2 error on failure
     *
     * @access  public
     * @see     bindParam, execute
     */
    function prepare($query, $types = null, $result_types = null, $lobs = array())
    {
        $is_manip = ($result_types === MDB2_PREPARE_MANIP);
        $offset = $this->offset;
        $limit = $this->limit;
        $this->offset = $this->limit = 0;
        $result = $this->debug($query, __FUNCTION__, array('is_manip' => $is_manip, 'when' => 'pre'));
        if ($result) {
            if (MDB2::isError($result)) {
                return $result;
            }
            $query = $result;
        }
        $placeholder_type_guess = $placeholder_type = null;
        $question  = '?';
        $colon     = ':';
        $positions = array();
        $position  = 0;
        while ($position < strlen($query)) {
            $q_position = strpos($query, $question, $position);
            $c_position = strpos($query, $colon, $position);
            if ($q_position && $c_position) {
                $p_position = min($q_position, $c_position);
            } elseif ($q_position) {
                $p_position = $q_position;
            } elseif ($c_position) {
                $p_position = $c_position;
            } else {
                break;
            }
            if (null === $placeholder_type) {
                $placeholder_type_guess = $query[$p_position];
            }

            $new_pos = $this->_skipDelimitedStrings($query, $position, $p_position);
            if (MDB2::isError($new_pos)) {
                return $new_pos;
            }
            if ($new_pos != $position) {
                $position = $new_pos;
                continue; //evaluate again starting from the new position
            }

            if ($query[$position] == $placeholder_type_guess) {
                if (null === $placeholder_type) {
                    $placeholder_type = $query[$p_position];
                    $question = $colon = $placeholder_type;
                    if (!empty($types) && is_array($types)) {
                        if ($placeholder_type == ':') {
                            if (is_int(key($types))) {
                                $types_tmp = $types;
                                $types = array();
                                $count = -1;
                            }
                        } else {
                            $types = array_values($types);
                        }
                    }
                }
                if ($placeholder_type == ':') {
                    $regexp = '/^.{'.($position+1).'}('.$this->options['bindname_format'].').*$/s';
                    $parameter = preg_replace($regexp, '\\1', $query);
                    if ($parameter === '') {
                        $err = MDB2_Driver_Common::raiseError(MDB2_ERROR_SYNTAX, null, null,
                            'named parameter name must match "bindname_format" option', __FUNCTION__);
                        return $err;
                    }
                    $positions[$p_position] = $parameter;
                    $query = substr_replace($query, '?', $position, strlen($parameter)+1);
                    // use parameter name in type array
                    if (isset($count) && isset($types_tmp[++$count])) {
                        $types[$parameter] = $types_tmp[$count];
                    }
                } else {
                    $positions[$p_position] = count($positions);
                }
                $position = $p_position + 1;
            } else {
                $position = $p_position;
            }
        }
        $class_name = 'MDB2_Statement_'.$this->phptype;
        $statement = null;
        $obj = new $class_name($this, $statement, $positions, $query, $types, $result_types, $is_manip, $limit, $offset);
        $this->debug($query, __FUNCTION__, array('is_manip' => $is_manip, 'when' => 'post', 'result' => $obj));
        return $obj;
    }

    // }}}
    // {{{ function _skipDelimitedStrings($query, $position, $p_position)

    /**
     * Utility method, used by prepare() to avoid replacing placeholders within delimited strings.
     * Check if the placeholder is contained within a delimited string.
     * If so, skip it and advance the position, otherwise return the current position,
     * which is valid
     *
     * @param string $query
     * @param integer $position current string cursor position
     * @param integer $p_position placeholder position
     *
     * @return mixed integer $new_position on success
     *               MDB2_Error on failure
     *
     * @access  protected
     */
    function _skipDelimitedStrings($query, $position, $p_position)
    {
        $ignores = array();
        $ignores[] = $this->string_quoting;
        $ignores[] = $this->identifier_quoting;
        $ignores = array_merge($ignores, $this->sql_comments);

        foreach ($ignores as $ignore) {
            if (!empty($ignore['start'])) {
                if (is_int($start_quote = strpos($query, $ignore['start'], $position)) && $start_quote < $p_position) {
                    $end_quote = $start_quote;
                    do {
                        if (!is_int($end_quote = strpos($query, $ignore['end'], $end_quote + 1))) {
                            if ($ignore['end'] === "\n") {
                                $end_quote = strlen($query) - 1;
                            } else {
                                $err = MDB2_Driver_Common::raiseError(MDB2_ERROR_SYNTAX, null, null,
                                    'query with an unterminated text string specified', __FUNCTION__);
                                return $err;
                            }
                        }
                    } while ($ignore['escape']
                        && $end_quote-1 != $start_quote
                        && $query[($end_quote - 1)] == $ignore['escape']
                        && (   $ignore['escape_pattern'] !== $ignore['escape']
                            || $query[($end_quote - 2)] != $ignore['escape'])
                    );

                    $position = $end_quote + 1;
                    return $position;
                }
            }
        }
        return $position;
    }

    // }}}
    // {{{ function quote($value, $type = null, $quote = true)

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param   string  text string value that is intended to be converted.
     * @param   string  type to which the value should be converted to
     * @param   bool    quote
     * @param   bool    escape wildcards
     *
     * @return  string  text string that represents the given argument value in
     *       a DBMS specific format.
     *
     * @access  public
     */
    function quote($value, $type = null, $quote = true, $escape_wildcards = false)
    {
        $result = $this->loadModule('Datatype', null, true);
        if (MDB2::isError($result)) {
            return $result;
        }

        return $this->datatype->quote($value, $type, $quote, $escape_wildcards);
    }

    // }}}
    // {{{ function getDeclaration($type, $name, $field)

    /**
     * Obtain DBMS specific SQL code portion needed to declare
     * of the given type
     *
     * @param   string  type to which the value should be converted to
     * @param   string  name the field to be declared.
     * @param   string  definition of the field
     *
     * @return  string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     *
     * @access  public
     */
    function getDeclaration($type, $name, $field)
    {
        $result = $this->loadModule('Datatype', null, true);
        if (MDB2::isError($result)) {
            return $result;
        }
        return $this->datatype->getDeclaration($type, $name, $field);
    }

    // }}}
    // {{{ function compareDefinition($current, $previous)

    /**
     * Obtain an array of changes that may need to applied
     *
     * @param   array   new definition
     * @param   array   old definition
     *
     * @return  array   containing all changes that will need to be applied
     *
     * @access  public
     */
    function compareDefinition($current, $previous)
    {
        $result = $this->loadModule('Datatype', null, true);
        if (MDB2::isError($result)) {
            return $result;
        }
        return $this->datatype->compareDefinition($current, $previous);
    }

    // }}}
    // {{{ function supports($feature)

    /**
     * Tell whether a DB implementation or its backend extension
     * supports a given feature.
     *
     * @param   string  name of the feature (see the MDB2 class doc)
     *
     * @return  bool|string if this DB implementation supports a given feature
     *                      false means no, true means native,
     *                      'emulated' means emulated
     *
     * @access  public
     */
    function supports($feature)
    {
        if (array_key_exists($feature, $this->supported)) {
            return $this->supported[$feature];
        }
        return MDB2_Driver_Common::raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            "unknown support feature $feature", __FUNCTION__);
    }

    // }}}
    // {{{ function getSequenceName($sqn)

    /**
     * adds sequence name formatting to a sequence name
     *
     * @param   string  name of the sequence
     *
     * @return  string  formatted sequence name
     *
     * @access  public
     */
    function getSequenceName($sqn)
    {
        return sprintf($this->options['seqname_format'],
            preg_replace('/[^a-z0-9_\-\$.]/i', '_', $sqn));
    }

    // }}}
    // {{{ function getIndexName($idx)

    /**
     * adds index name formatting to a index name
     *
     * @param   string  name of the index
     *
     * @return  string  formatted index name
     *
     * @access  public
     */
    function getIndexName($idx)
    {
        return sprintf($this->options['idxname_format'],
            preg_replace('/[^a-z0-9_\-\$.]/i', '_', $idx));
    }

    // }}}
    // {{{ function nextID($seq_name, $ondemand = true)

    /**
     * Returns the next free id of a sequence
     *
     * @param   string  name of the sequence
     * @param   bool    when true missing sequences are automatic created
     *
     * @return  mixed   MDB2 Error Object or id
     *
     * @access  public
     */
    function nextID($seq_name, $ondemand = true)
    {
        return MDB2_Driver_Common::raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ function lastInsertID($table = null, $field = null)

    /**
     * Returns the autoincrement ID if supported or $id or fetches the current
     * ID in a sequence called: $table.(empty($field) ? '' : '_'.$field)
     *
     * @param   string  name of the table into which a new row was inserted
     * @param   string  name of the field into which a new row was inserted
     *
     * @return  mixed   MDB2 Error Object or id
     *
     * @access  public
     */
    function lastInsertID($table = null, $field = null)
    {
        return MDB2_Driver_Common::raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ function currID($seq_name)

    /**
     * Returns the current id of a sequence
     *
     * @param   string  name of the sequence
     *
     * @return  mixed   MDB2 Error Object or id
     *
     * @access  public
     */
    function currID($seq_name)
    {
        $this->warnings[] = 'database does not support getting current
            sequence value, the sequence value was incremented';
        return $this->nextID($seq_name);
    }

    // }}}
    // {{{ function queryOne($query, $type = null, $colnum = 0)

    /**
     * Execute the specified query, fetch the value from the first column of
     * the first row of the result set and then frees
     * the result set.
     *
     * @param string $query  the SELECT query statement to be executed.
     * @param string $type   optional argument that specifies the expected
     *                       datatype of the result set field, so that an eventual
     *                       conversion may be performed. The default datatype is
     *                       text, meaning that no conversion is performed
     * @param mixed  $colnum the column number (or name) to fetch
     *
     * @return  mixed   MDB2_OK or field value on success, a MDB2 error on failure
     *
     * @access  public
     */
    function queryOne($query, $type = null, $colnum = 0)
    {
        $result = $this->query($query, $type);
        if (!MDB2::isResultCommon($result)) {
            return $result;
        }

        $one = $result->fetchOne($colnum);
        $result->free();
        return $one;
    }

    // }}}
    // {{{ function queryRow($query, $types = null, $fetchmode = MDB2_FETCHMODE_DEFAULT)

    /**
     * Execute the specified query, fetch the values from the first
     * row of the result set into an array and then frees
     * the result set.
     *
     * @param   string  the SELECT query statement to be executed.
     * @param   array   optional array argument that specifies a list of
     *       expected datatypes of the result set columns, so that the eventual
     *       conversions may be performed. The default list of datatypes is
     *       empty, meaning that no conversion is performed.
     * @param   int     how the array data should be indexed
     *
     * @return  mixed   MDB2_OK or data array on success, a MDB2 error on failure
     *
     * @access  public
     */
    function queryRow($query, $types = null, $fetchmode = MDB2_FETCHMODE_DEFAULT)
    {
        $result = $this->query($query, $types);
        if (!MDB2::isResultCommon($result)) {
            return $result;
        }

        $row = $result->fetchRow($fetchmode);
        $result->free();
        return $row;
    }

    // }}}
    // {{{ function queryCol($query, $type = null, $colnum = 0)

    /**
     * Execute the specified query, fetch the value from the first column of
     * each row of the result set into an array and then frees the result set.
     *
     * @param string $query  the SELECT query statement to be executed.
     * @param string $type   optional argument that specifies the expected
     *                       datatype of the result set field, so that an eventual
     *                       conversion may be performed. The default datatype is text,
     *                       meaning that no conversion is performed
     * @param mixed  $colnum the column number (or name) to fetch
     *
     * @return  mixed   MDB2_OK or data array on success, a MDB2 error on failure
     * @access  public
     */
    function queryCol($query, $type = null, $colnum = 0)
    {
        $result = $this->query($query, $type);
        if (!MDB2::isResultCommon($result)) {
            return $result;
        }

        $col = $result->fetchCol($colnum);
        $result->free();
        return $col;
    }

    // }}}
    // {{{ function queryAll($query, $types = null, $fetchmode = MDB2_FETCHMODE_DEFAULT, $rekey = false, $force_array = false, $group = false)

    /**
     * Execute the specified query, fetch all the rows of the result set into
     * a two dimensional array and then frees the result set.
     *
     * @param   string  the SELECT query statement to be executed.
     * @param   array   optional array argument that specifies a list of
     *       expected datatypes of the result set columns, so that the eventual
     *       conversions may be performed. The default list of datatypes is
     *       empty, meaning that no conversion is performed.
     * @param   int     how the array data should be indexed
     * @param   bool    if set to true, the $all will have the first
     *       column as its first dimension
     * @param   bool    used only when the query returns exactly
     *       two columns. If true, the values of the returned array will be
     *       one-element arrays instead of scalars.
     * @param   bool    if true, the values of the returned array is
     *       wrapped in another array.  If the same key value (in the first
     *       column) repeats itself, the values will be appended to this array
     *       instead of overwriting the existing values.
     *
     * @return  mixed   MDB2_OK or data array on success, a MDB2 error on failure
     *
     * @access  public
     */
    function queryAll($query, $types = null, $fetchmode = MDB2_FETCHMODE_DEFAULT,
        $rekey = false, $force_array = false, $group = false)
    {
        $result = $this->query($query, $types);
        if (!MDB2::isResultCommon($result)) {
            return $result;
        }

        $all = $result->fetchAll($fetchmode, $rekey, $force_array, $group);
        $result->free();
        return $all;
    }

    // }}}
    // {{{ function delExpect($error_code)

    /**
     * This method deletes all occurences of the specified element from
     * the expected error codes stack.
     *
     * @param  mixed $error_code error code that should be deleted
     * @return mixed list of error codes that were deleted or error
     *
     * @uses PEAR::delExpect()
     */
    public function delExpect($error_code)
    {
        return $this->pear->delExpect($error_code);
    }

    // }}}
    // {{{ function expectError($code)

    /**
     * This method is used to tell which errors you expect to get.
     * Expected errors are always returned with error mode
     * PEAR_ERROR_RETURN.  Expected error codes are stored in a stack,
     * and this method pushes a new element onto it.  The list of
     * expected errors are in effect until they are popped off the
     * stack with the popExpect() method.
     *
     * Note that this method can not be called statically
     *
     * @param mixed $code a single error code or an array of error codes to expect
     *
     * @return int     the new depth of the "expected errors" stack
     *
     * @uses PEAR::expectError()
     */
    public function expectError($code = '*')
    {
        return $this->pear->expectError($code);
    }

    // }}}
    // {{{ function getStaticProperty($class, $var)

    /**
     * If you have a class that's mostly/entirely static, and you need static
     * properties, you can use this method to simulate them. Eg. in your method(s)
     * do this: $myVar = &PEAR::getStaticProperty('myclass', 'myVar');
     * You MUST use a reference, or they will not persist!
     *
     * @param  string $class  The calling classname, to prevent clashes
     * @param  string $var    The variable to retrieve.
     * @return mixed   A reference to the variable. If not set it will be
     *                 auto initialised to NULL.
     *
     * @uses PEAR::getStaticProperty()
     */
    public function &getStaticProperty($class, $var)
    {
        $tmp = $this->pear->getStaticProperty($class, $var);
        return $tmp;
    }

    // }}}
    // {{{ function loadExtension($ext)

    /**
     * OS independant PHP extension load. Remember to take care
     * on the correct extension name for case sensitive OSes.
     *
     * @param string $ext The extension name
     * @return bool Success or not on the dl() call
     *
     * @uses PEAR::loadExtension()
     */
    public function loadExtension($ext)
    {
        return $this->pear->loadExtension($ext);
    }

    // }}}
    // {{{ function popErrorHandling()

    /**
     * Pop the last error handler used
     *
     * @return bool Always true
     *
     * @see PEAR::pushErrorHandling
     * @uses PEAR::popErrorHandling()
     */
    public function popErrorHandling()
    {
        return $this->pear->popErrorHandling();
    }

    // }}}
    // {{{ function popExpect()

    /**
     * This method pops one element off the expected error codes
     * stack.
     *
     * @return array   the list of error codes that were popped
     *
     * @uses PEAR::popExpect()
     */
    public function popExpect()
    {
        return $this->pear->popExpect();
    }

    // }}}
    // {{{ function pushErrorHandling($mode, $options = null)

    /**
     * Push a new error handler on top of the error handler options stack. With this
     * you can easily override the actual error handler for some code and restore
     * it later with popErrorHandling.
     *
     * @param mixed $mode (same as setErrorHandling)
     * @param mixed $options (same as setErrorHandling)
     *
     * @return bool Always true
     *
     * @see PEAR::setErrorHandling
     * @uses PEAR::pushErrorHandling()
     */
    public function pushErrorHandling($mode, $options = null)
    {
        return $this->pear->pushErrorHandling($mode, $options);
    }

    // }}}
    // {{{ function registerShutdownFunc($func, $args = array())

    /**
     * Use this function to register a shutdown method for static
     * classes.
     *
     * @param  mixed $func  The function name (or array of class/method) to call
     * @param  mixed $args  The arguments to pass to the function
     * @return void
     *
     * @uses PEAR::registerShutdownFunc()
     */
    public function registerShutdownFunc($func, $args = array())
    {
        return $this->pear->registerShutdownFunc($func, $args);
    }

    // }}}
    // {{{ function setErrorHandling($mode = null, $options = null)

    /**
     * Sets how errors generated by this object should be handled.
     * Can be invoked both in objects and statically.  If called
     * statically, setErrorHandling sets the default behaviour for all
     * PEAR objects.  If called in an object, setErrorHandling sets
     * the default behaviour for that object.
     *
     * @param int $mode
     *        One of PEAR_ERROR_RETURN, PEAR_ERROR_PRINT,
     *        PEAR_ERROR_TRIGGER, PEAR_ERROR_DIE,
     *        PEAR_ERROR_CALLBACK or PEAR_ERROR_EXCEPTION.
     *
     * @param mixed $options
     *        When $mode is PEAR_ERROR_TRIGGER, this is the error level (one
     *        of E_USER_NOTICE, E_USER_WARNING or E_USER_ERROR).
     *
     *        When $mode is PEAR_ERROR_CALLBACK, this parameter is expected
     *        to be the callback function or method.  A callback
     *        function is a string with the name of the function, a
     *        callback method is an array of two elements: the element
     *        at index 0 is the object, and the element at index 1 is
     *        the name of the method to call in the object.
     *
     *        When $mode is PEAR_ERROR_PRINT or PEAR_ERROR_DIE, this is
     *        a printf format string used when printing the error
     *        message.
     *
     * @access public
     * @return void
     * @see PEAR_ERROR_RETURN
     * @see PEAR_ERROR_PRINT
     * @see PEAR_ERROR_TRIGGER
     * @see PEAR_ERROR_DIE
     * @see PEAR_ERROR_CALLBACK
     * @see PEAR_ERROR_EXCEPTION
     *
     * @since PHP 4.0.5
     * @uses PEAR::setErrorHandling($mode, $options)
     */
    public function setErrorHandling($mode = null, $options = null)
    {
        return $this->pear->setErrorHandling($mode, $options);
    }

    /**
     * @uses PEAR::staticPopErrorHandling() 
     */
    public function staticPopErrorHandling()
    {
        return $this->pear->staticPopErrorHandling();
    }

    // }}}
    // {{{ function staticPushErrorHandling($mode, $options = null)

    /**
     * @uses PEAR::staticPushErrorHandling($mode, $options)
     */
    public function staticPushErrorHandling($mode, $options = null)
    {
        return $this->pear->staticPushErrorHandling($mode, $options);
    }

    // }}}
    // {{{ function &throwError($message = null, $code = null, $userinfo = null)

    /**
     * Simpler form of raiseError with fewer options.  In most cases
     * message, code and userinfo are enough.
     *
     * @param mixed $message a text error message or a PEAR error object
     *
     * @param int $code      a numeric error code (it is up to your class
     *                  to define these if you want to use codes)
     *
     * @param string $userinfo If you need to pass along for example debug
     *                  information, this parameter is meant for that.
     *
     * @return object   a PEAR error object
     * @see PEAR::raiseError
     * @uses PEAR::&throwError()
     */
    public function &throwError($message = null, $code = null, $userinfo = null)
    {
        $tmp = $this->pear->throwError($message, $code, $userinfo);
        return $tmp;
    }

    // }}}
}

// }}}
// {{{ class MDB2_Result

/**
 * The dummy class that all user space result classes should extend from
 *
 * @package     MDB2
 * @category    Database
 * @author      Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Result
{
}

// }}}
// {{{ class MDB2_Result_Common extends MDB2_Result

/**
 * The common result class for MDB2 result objects
 *
 * @package     MDB2
 * @category    Database
 * @author      Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Result_Common extends MDB2_Result
{
    // {{{ Variables (Properties)

    public $db;
    public $result;
    public $rownum = -1;
    public $types = array();
    public $types_assoc = array();
    public $values = array();
    public $offset;
    public $offset_count = 0;
    public $limit;
    public $column_names;

    // }}}
    // {{{ constructor: function __construct($db, &$result, $limit = 0, $offset = 0)

    /**
     * Constructor
     */
    function __construct($db, &$result, $limit = 0, $offset = 0)
    {
        $this->db = $db;
        $this->result = $result;
        $this->offset = $offset;
        $this->limit = max(0, $limit - 1);
    }

    // }}}
    // {{{ function setResultTypes($types)

    /**
     * Define the list of types to be associated with the columns of a given
     * result set.
     *
     * This function may be called before invoking fetchRow(), fetchOne(),
     * fetchCol() and fetchAll() so that the necessary data type
     * conversions are performed on the data to be retrieved by them. If this
     * function is not called, the type of all result set columns is assumed
     * to be text, thus leading to not perform any conversions.
     *
     * @param   array   variable that lists the
     *       data types to be expected in the result set columns. If this array
     *       contains less types than the number of columns that are returned
     *       in the result set, the remaining columns are assumed to be of the
     *       type text. Currently, the types clob and blob are not fully
     *       supported.
     *
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function setResultTypes($types)
    {
        $load = $this->db->loadModule('Datatype', null, true);
        if (MDB2::isError($load)) {
            return $load;
        }
        $types = $this->db->datatype->checkResultTypes($types);
        if (MDB2::isError($types)) {
            return $types;
        }
        foreach ($types as $key => $value) {
            if (is_numeric($key)) {
                $this->types[$key] = $value;
            } else {
                $this->types_assoc[$key] = $value;
            }
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ function seek($rownum = 0)

    /**
     * Seek to a specific row in a result set
     *
     * @param   int     number of the row where the data can be found
     *
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function seek($rownum = 0)
    {
        $target_rownum = $rownum - 1;
        if ($this->rownum > $target_rownum) {
            return MDB2::raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'seeking to previous rows not implemented', __FUNCTION__);
        }
        while ($this->rownum < $target_rownum) {
            $this->fetchRow();
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ function &fetchRow($fetchmode = MDB2_FETCHMODE_DEFAULT, $rownum = null)

    /**
     * Fetch and return a row of data
     *
     * @param   int     how the array data should be indexed
     * @param   int     number of the row where the data can be found
     *
     * @return  int     data array on success, a MDB2 error on failure
     *
     * @access  public
     */
    function fetchRow($fetchmode = MDB2_FETCHMODE_DEFAULT, $rownum = null)
    {
        $err = MDB2::raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
        return $err;
    }

    // }}}
    // {{{ function fetchOne($colnum = 0)

    /**
     * fetch single column from the next row from a result set
     *
     * @param int|string the column number (or name) to fetch
     * @param int        number of the row where the data can be found
     *
     * @return string data on success, a MDB2 error on failure
     * @access  public
     */
    function fetchOne($colnum = 0, $rownum = null)
    {
        $fetchmode = is_numeric($colnum) ? MDB2_FETCHMODE_ORDERED : MDB2_FETCHMODE_ASSOC;
        $row = $this->fetchRow($fetchmode, $rownum);
        if (!is_array($row) || MDB2::isError($row)) {
            return $row;
        }
        if (!array_key_exists($colnum, $row)) {
            return MDB2::raiseError(MDB2_ERROR_TRUNCATED, null, null,
                'column is not defined in the result set: '.$colnum, __FUNCTION__);
        }
        return $row[$colnum];
    }

    // }}}
    // {{{ function fetchCol($colnum = 0)

    /**
     * Fetch and return a column from the current row pointer position
     *
     * @param int|string the column number (or name) to fetch
     *
     * @return  mixed data array on success, a MDB2 error on failure
     * @access  public
     */
    function fetchCol($colnum = 0)
    {
        $column = array();
        $fetchmode = is_numeric($colnum) ? MDB2_FETCHMODE_ORDERED : MDB2_FETCHMODE_ASSOC;
        $row = $this->fetchRow($fetchmode);
        if (is_array($row)) {
            if (!array_key_exists($colnum, $row)) {
                return MDB2::raiseError(MDB2_ERROR_TRUNCATED, null, null,
                    'column is not defined in the result set: '.$colnum, __FUNCTION__);
            }
            do {
                $column[] = $row[$colnum];
            } while (is_array($row = $this->fetchRow($fetchmode)));
        }
        if (MDB2::isError($row)) {
            return $row;
        }
        return $column;
    }

    // }}}
    // {{{ function fetchAll($fetchmode = MDB2_FETCHMODE_DEFAULT, $rekey = false, $force_array = false, $group = false)

    /**
     * Fetch and return all rows from the current row pointer position
     *
     * @param   int     $fetchmode  the fetch mode to use:
     *                            + MDB2_FETCHMODE_ORDERED
     *                            + MDB2_FETCHMODE_ASSOC
     *                            + MDB2_FETCHMODE_ORDERED | MDB2_FETCHMODE_FLIPPED
     *                            + MDB2_FETCHMODE_ASSOC | MDB2_FETCHMODE_FLIPPED
     * @param   bool    if set to true, the $all will have the first
     *       column as its first dimension
     * @param   bool    used only when the query returns exactly
     *       two columns. If true, the values of the returned array will be
     *       one-element arrays instead of scalars.
     * @param   bool    if true, the values of the returned array is
     *       wrapped in another array.  If the same key value (in the first
     *       column) repeats itself, the values will be appended to this array
     *       instead of overwriting the existing values.
     *
     * @return  mixed   data array on success, a MDB2 error on failure
     *
     * @access  public
     * @see     getAssoc()
     */
    function fetchAll($fetchmode = MDB2_FETCHMODE_DEFAULT, $rekey = false,
        $force_array = false, $group = false)
    {
        $all = array();
        $row = $this->fetchRow($fetchmode);
        if (MDB2::isError($row)) {
            return $row;
        } elseif (!$row) {
            return $all;
        }

        $shift_array = $rekey ? false : null;
        if (null !== $shift_array) {
            if (is_object($row)) {
                $colnum = count(get_object_vars($row));
            } else {
                $colnum = count($row);
            }
            if ($colnum < 2) {
                return MDB2::raiseError(MDB2_ERROR_TRUNCATED, null, null,
                    'rekey feature requires atleast 2 column', __FUNCTION__);
            }
            $shift_array = (!$force_array && $colnum == 2);
        }

        if ($rekey) {
            do {
                if (is_object($row)) {
                    $arr = get_object_vars($row);
                    $key = reset($arr);
                    unset($row->{$key});
                } else {
                    if (   $fetchmode == MDB2_FETCHMODE_ASSOC
                        || $fetchmode == MDB2_FETCHMODE_OBJECT
                    ) {
                        $key = reset($row);
                        unset($row[key($row)]);
                    } else {
                        $key = array_shift($row);
                    }
                    if ($shift_array) {
                        $row = array_shift($row);
                    }
                }
                if ($group) {
                    $all[$key][] = $row;
                } else {
                    $all[$key] = $row;
                }
            } while (($row = $this->fetchRow($fetchmode)));
        } elseif ($fetchmode == MDB2_FETCHMODE_FLIPPED) {
            do {
                foreach ($row as $key => $val) {
                    $all[$key][] = $val;
                }
            } while (($row = $this->fetchRow($fetchmode)));
        } else {
            do {
                $all[] = $row;
            } while (($row = $this->fetchRow($fetchmode)));
        }

        return $all;
    }

    // }}}
    // {{{ function rowCount()
    /**
     * Returns the actual row number that was last fetched (count from 0)
     * @return  int
     *
     * @access  public
     */
    function rowCount()
    {
        return $this->rownum + 1;
    }

    // }}}
    // {{{ function numRows()

    /**
     * Returns the number of rows in a result object
     *
     * @return  mixed   MDB2 Error Object or the number of rows
     *
     * @access  public
     */
    function numRows()
    {
        return MDB2::raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ function nextResult()

    /**
     * Move the internal result pointer to the next available result
     *
     * @return  true on success, false if there is no more result set or an error object on failure
     *
     * @access  public
     */
    function nextResult()
    {
        return MDB2::raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ function getColumnNames()

    /**
     * Retrieve the names of columns returned by the DBMS in a query result or
     * from the cache.
     *
     * @param   bool    If set to true the values are the column names,
     *                  otherwise the names of the columns are the keys.
     * @return  mixed   Array variable that holds the names of columns or an
     *                  MDB2 error on failure.
     *                  Some DBMS may not return any columns when the result set
     *                  does not contain any rows.
     *
     * @access  public
     */
    function getColumnNames($flip = false)
    {
        if (!isset($this->column_names)) {
            $result = $this->_getColumnNames();
            if (MDB2::isError($result)) {
                return $result;
            }
            $this->column_names = $result;
        }
        if ($flip) {
            return array_flip($this->column_names);
        }
        return $this->column_names;
    }

    // }}}
    // {{{ function _getColumnNames()

    /**
     * Retrieve the names of columns returned by the DBMS in a query result.
     *
     * @return  mixed   Array variable that holds the names of columns as keys
     *                  or an MDB2 error on failure.
     *                  Some DBMS may not return any columns when the result set
     *                  does not contain any rows.
     *
     * @access  private
     */
    function _getColumnNames()
    {
        return MDB2::raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ function numCols()

    /**
     * Count the number of columns returned by the DBMS in a query result.
     *
     * @return  mixed   integer value with the number of columns, a MDB2 error
     *       on failure
     *
     * @access  public
     */
    function numCols()
    {
        return MDB2::raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ function getResource()

    /**
     * return the resource associated with the result object
     *
     * @return  resource
     *
     * @access  public
     */
    function getResource()
    {
        return $this->result;
    }

    // }}}
    // {{{ function bindColumn($column, &$value, $type = null)

    /**
     * Set bind variable to a column.
     *
     * @param   int     column number or name
     * @param   mixed   variable reference
     * @param   string  specifies the type of the field
     *
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function bindColumn($column, &$value, $type = null)
    {
        if (!is_numeric($column)) {
            $column_names = $this->getColumnNames();
            if ($this->db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                if ($this->db->options['field_case'] == CASE_LOWER) {
                    $column = strtolower($column);
                } else {
                    $column = strtoupper($column);
                }
            }
            $column = $column_names[$column];
        }
        $this->values[$column] =& $value;
        if (null !== $type) {
            $this->types[$column] = $type;
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ function _assignBindColumns($row)

    /**
     * Bind a variable to a value in the result row.
     *
     * @param   array   row data
     *
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  private
     */
    function _assignBindColumns($row)
    {
        $row = array_values($row);
        foreach ($row as $column => $value) {
            if (array_key_exists($column, $this->values)) {
                $this->values[$column] = $value;
            }
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ function free()

    /**
     * Free the internal resources associated with result.
     *
     * @return  bool    true on success, false if result is invalid
     *
     * @access  public
     */
    function free()
    {
        $this->result = false;
        return MDB2_OK;
    }

    // }}}
}

// }}}
// {{{ class MDB2_Row

/**
 * The simple class that accepts row data as an array
 *
 * @package     MDB2
 * @category    Database
 * @author      Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Row
{
    // {{{ constructor: function __construct(&$row)

    /**
     * constructor
     *
     * @param   resource    row data as array
     */
    function __construct(&$row)
    {
        foreach ($row as $key => $value) {
            $this->$key = &$row[$key];
        }
    }

    // }}}
}

// }}}
// {{{ class MDB2_Statement_Common

/**
 * The common statement class for MDB2 statement objects
 *
 * @package     MDB2
 * @category    Database
 * @author      Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Statement_Common
{
    // {{{ Variables (Properties)

    var $db;
    var $statement;
    var $query;
    var $result_types;
    var $types;
    var $values = array();
    var $limit;
    var $offset;
    var $is_manip;

    // }}}
    // {{{ constructor: function __construct($db, $statement, $positions, $query, $types, $result_types, $is_manip = false, $limit = null, $offset = null)

    /**
     * Constructor
     */
    function __construct($db, $statement, $positions, $query, $types, $result_types, $is_manip = false, $limit = null, $offset = null)
    {
        $this->db = $db;
        $this->statement = $statement;
        $this->positions = $positions;
        $this->query = $query;
        $this->types = (array)$types;
        $this->result_types = (array)$result_types;
        $this->limit = $limit;
        $this->is_manip = $is_manip;
        $this->offset = $offset;
    }

    // }}}
    // {{{ function bindValue($parameter, &$value, $type = null)

    /**
     * Set the value of a parameter of a prepared query.
     *
     * @param   int     the order number of the parameter in the query
     *       statement. The order number of the first parameter is 1.
     * @param   mixed   value that is meant to be assigned to specified
     *       parameter. The type of the value depends on the $type argument.
     * @param   string  specifies the type of the field
     *
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function bindValue($parameter, $value, $type = null)
    {
        if (!is_numeric($parameter)) {
            if (strpos($parameter, ':') === 0) {
                $parameter = substr($parameter, 1);
            }
        }
        if (!in_array($parameter, $this->positions)) {
            return MDB2::raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'Unable to bind to missing placeholder: '.$parameter, __FUNCTION__);
        }
        $this->values[$parameter] = $value;
        if (null !== $type) {
            $this->types[$parameter] = $type;
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ function bindValueArray($values, $types = null)

    /**
     * Set the values of multiple a parameter of a prepared query in bulk.
     *
     * @param   array   specifies all necessary information
     *       for bindValue() the array elements must use keys corresponding to
     *       the number of the position of the parameter.
     * @param   array   specifies the types of the fields
     *
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     * @see     bindParam()
     */
    function bindValueArray($values, $types = null)
    {
        $types = is_array($types) ? array_values($types) : array_fill(0, count($values), null);
        $parameters = array_keys($values);
        $this->db->pushErrorHandling(PEAR_ERROR_RETURN);
        $this->db->expectError(MDB2_ERROR_NOT_FOUND);
        foreach ($parameters as $key => $parameter) {
            $err = $this->bindValue($parameter, $values[$parameter], $types[$key]);
            if (MDB2::isError($err)) {
                if ($err->getCode() == MDB2_ERROR_NOT_FOUND) {
                    //ignore (extra value for missing placeholder)
                    continue;
                }
                $this->db->popExpect();
                $this->db->popErrorHandling();
                return $err;
            }
        }
        $this->db->popExpect();
        $this->db->popErrorHandling();
        return MDB2_OK;
    }

    // }}}
    // {{{ function bindParam($parameter, &$value, $type = null)

    /**
     * Bind a variable to a parameter of a prepared query.
     *
     * @param   int     the order number of the parameter in the query
     *       statement. The order number of the first parameter is 1.
     * @param   mixed   variable that is meant to be bound to specified
     *       parameter. The type of the value depends on the $type argument.
     * @param   string  specifies the type of the field
     *
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function bindParam($parameter, &$value, $type = null)
    {
        if (!is_numeric($parameter)) {
            if (strpos($parameter, ':') === 0) {
                $parameter = substr($parameter, 1);
            }
        }
        if (!in_array($parameter, $this->positions)) {
            return MDB2::raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'Unable to bind to missing placeholder: '.$parameter, __FUNCTION__);
        }
        $this->values[$parameter] =& $value;
        if (null !== $type) {
            $this->types[$parameter] = $type;
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ function bindParamArray(&$values, $types = null)

    /**
     * Bind the variables of multiple a parameter of a prepared query in bulk.
     *
     * @param   array   specifies all necessary information
     *       for bindParam() the array elements must use keys corresponding to
     *       the number of the position of the parameter.
     * @param   array   specifies the types of the fields
     *
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     * @see     bindParam()
     */
    function bindParamArray(&$values, $types = null)
    {
        $types = is_array($types) ? array_values($types) : array_fill(0, count($values), null);
        $parameters = array_keys($values);
        foreach ($parameters as $key => $parameter) {
            $err = $this->bindParam($parameter, $values[$parameter], $types[$key]);
            if (MDB2::isError($err)) {
                return $err;
            }
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ function &execute($values = null, $result_class = true, $result_wrap_class = false)

    /**
     * Execute a prepared query statement.
     *
     * @param array specifies all necessary information
     *              for bindParam() the array elements must use keys corresponding
     *              to the number of the position of the parameter.
     * @param mixed specifies which result class to use
     * @param mixed specifies which class to wrap results in
     *
     * @return mixed MDB2_Result or integer (affected rows) on success,
     *               a MDB2 error on failure
     * @access public
     */
    function execute($values = null, $result_class = true, $result_wrap_class = false)
    {
        if (null === $this->positions) {
            return MDB2::raiseError(MDB2_ERROR, null, null,
                'Prepared statement has already been freed', __FUNCTION__);
        }

        $values = (array)$values;
        if (!empty($values)) {
            $err = $this->bindValueArray($values);
            if (MDB2::isError($err)) {
                return MDB2::raiseError(MDB2_ERROR, null, null,
                                            'Binding Values failed with message: ' . $err->getMessage(), __FUNCTION__);
            }
        }
        $result = $this->_execute($result_class, $result_wrap_class);
        return $result;
    }

    // }}}
    // {{{ function _execute($result_class = true, $result_wrap_class = false)

    /**
     * Execute a prepared query statement helper method.
     *
     * @param   mixed   specifies which result class to use
     * @param   mixed   specifies which class to wrap results in
     *
     * @return mixed MDB2_Result or integer (affected rows) on success,
     *               a MDB2 error on failure
     * @access  private
     */
    function _execute($result_class = true, $result_wrap_class = false)
    {
        $this->last_query = $this->query;
        $query = '';
        $last_position = 0;
        foreach ($this->positions as $current_position => $parameter) {
            if (!array_key_exists($parameter, $this->values)) {
                return MDB2::raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                    'Unable to bind to missing placeholder: '.$parameter, __FUNCTION__);
            }
            $value = $this->values[$parameter];
            $query.= substr($this->query, $last_position, $current_position - $last_position);
            if (!isset($value)) {
                $value_quoted = 'NULL';
            } else {
                $type = !empty($this->types[$parameter]) ? $this->types[$parameter] : null;
                $value_quoted = $this->db->quote($value, $type);
                if (MDB2::isError($value_quoted)) {
                    return $value_quoted;
                }
            }
            $query.= $value_quoted;
            $last_position = $current_position + 1;
        }
        $query.= substr($this->query, $last_position);

        $this->db->offset = $this->offset;
        $this->db->limit = $this->limit;
        if ($this->is_manip) {
            $result = $this->db->exec($query);
        } else {
            $result = $this->db->query($query, $this->result_types, $result_class, $result_wrap_class);
        }
        return $result;
    }

    // }}}
    // {{{ function free()

    /**
     * Release resources allocated for the specified prepared query.
     *
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function free()
    {
        if (null === $this->positions) {
            return MDB2::raiseError(MDB2_ERROR, null, null,
                'Prepared statement has already been freed', __FUNCTION__);
        }

        $this->statement = null;
        $this->positions = null;
        $this->query = null;
        $this->types = null;
        $this->result_types = null;
        $this->limit = null;
        $this->is_manip = null;
        $this->offset = null;
        $this->values = null;

        return MDB2_OK;
    }

    // }}}
}

// }}}
// {{{ class MDB2_Module_Common

/**
 * The common modules class for MDB2 module objects
 *
 * @package     MDB2
 * @category    Database
 * @author      Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Module_Common
{
    // {{{ Variables (Properties)

    /**
     * contains the key to the global MDB2 instance array of the associated
     * MDB2 instance
     *
     * @var     int
     * @access  protected
     */
    protected $db_index;

    // }}}
    // {{{ constructor: function __construct($db_index)

    /**
     * Constructor
     */
    function __construct($db_index)
    {
        $this->db_index = $db_index;
    }

    // }}}
    // {{{ function getDBInstance()

    /**
     * Get the instance of MDB2 associated with the module instance
     *
     * @return  object  MDB2 instance or a MDB2 error on failure
     *
     * @access  public
     */
    function getDBInstance()
    {
        if (isset($GLOBALS['_MDB2_databases'][$this->db_index])) {
            $result = $GLOBALS['_MDB2_databases'][$this->db_index];
        } else {
            $result = MDB2::raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'could not find MDB2 instance');
        }
        return $result;
    }

    // }}}
}

// }}}
// {{{ function MDB2_closeOpenTransactions()

/**
 * Close any open transactions form persistent connections
 *
 * @return  void
 *
 * @access  public
 */

function MDB2_closeOpenTransactions()
{
    reset($GLOBALS['_MDB2_databases']);
    while (next($GLOBALS['_MDB2_databases'])) {
        $key = key($GLOBALS['_MDB2_databases']);
        if ($GLOBALS['_MDB2_databases'][$key]->opened_persistent
            && $GLOBALS['_MDB2_databases'][$key]->in_transaction
        ) {
            $GLOBALS['_MDB2_databases'][$key]->rollback();
        }
    }
}

// }}}
// {{{ function MDB2_defaultDebugOutput(&$db, $scope, $message, $is_manip = null)

/**
 * default debug output handler
 *
 * @param   object  reference to an MDB2 database object
 * @param   string  usually the method name that triggered the debug call:
 *                  for example 'query', 'prepare', 'execute', 'parameters',
 *                  'beginTransaction', 'commit', 'rollback'
 * @param   string  message that should be appended to the debug variable
 * @param   array   contains context information about the debug() call
 *                  common keys are: is_manip, time, result etc.
 *
 * @return  void|string optionally return a modified message, this allows
 *                      rewriting a query before being issued or prepared
 *
 * @access  public
 */
function MDB2_defaultDebugOutput(&$db, $scope, $message, $context = array())
{
    $db->debug_output.= $scope.'('.$db->db_index.'): ';
    $db->debug_output.= $message.$db->getOption('log_line_break');
    return $message;
}

// }}}
?>
