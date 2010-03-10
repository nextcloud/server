<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Database independent query interface
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Database
 * @package    DB
 * @author     Stig Bakken <ssb@php.net>
 * @author     Tomas V.V.Cox <cox@idecnet.com>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: DB.php,v 1.80 2005/02/16 02:16:00 danielc Exp $
 * @link       http://pear.php.net/package/DB
 */

/**
 * Obtain the PEAR class so it can be extended from
 */
require_once 'PEAR.php';


// {{{ constants
// {{{ error codes

/**#@+
 * One of PEAR DB's portable error codes.
 * @see DB_common::errorCode(), DB::errorMessage()
 *
 * {@internal If you add an error code here, make sure you also add a textual
 * version of it in DB::errorMessage().}}
 */

/**
 * The code returned by many methods upon success
 */
define('DB_OK', 1);

/**
 * Unkown error
 */
define('DB_ERROR', -1);

/**
 * Syntax error
 */
define('DB_ERROR_SYNTAX', -2);

/**
 * Tried to insert a duplicate value into a primary or unique index
 */
define('DB_ERROR_CONSTRAINT', -3);

/**
 * An identifier in the query refers to a non-existant object
 */
define('DB_ERROR_NOT_FOUND', -4);

/**
 * Tried to create a duplicate object
 */
define('DB_ERROR_ALREADY_EXISTS', -5);

/**
 * The current driver does not support the action you attempted
 */
define('DB_ERROR_UNSUPPORTED', -6);

/**
 * The number of parameters does not match the number of placeholders
 */
define('DB_ERROR_MISMATCH', -7);

/**
 * A literal submitted did not match the data type expected
 */
define('DB_ERROR_INVALID', -8);

/**
 * The current DBMS does not support the action you attempted
 */
define('DB_ERROR_NOT_CAPABLE', -9);

/**
 * A literal submitted was too long so the end of it was removed
 */
define('DB_ERROR_TRUNCATED', -10);

/**
 * A literal number submitted did not match the data type expected
 */
define('DB_ERROR_INVALID_NUMBER', -11);

/**
 * A literal date submitted did not match the data type expected
 */
define('DB_ERROR_INVALID_DATE', -12);

/**
 * Attempt to divide something by zero
 */
define('DB_ERROR_DIVZERO', -13);

/**
 * A database needs to be selected
 */
define('DB_ERROR_NODBSELECTED', -14);

/**
 * Could not create the object requested
 */
define('DB_ERROR_CANNOT_CREATE', -15);

/**
 * Could not drop the database requested because it does not exist
 */
define('DB_ERROR_CANNOT_DROP', -17);

/**
 * An identifier in the query refers to a non-existant table
 */
define('DB_ERROR_NOSUCHTABLE', -18);

/**
 * An identifier in the query refers to a non-existant column
 */
define('DB_ERROR_NOSUCHFIELD', -19);

/**
 * The data submitted to the method was inappropriate
 */
define('DB_ERROR_NEED_MORE_DATA', -20);

/**
 * The attempt to lock the table failed
 */
define('DB_ERROR_NOT_LOCKED', -21);

/**
 * The number of columns doesn't match the number of values
 */
define('DB_ERROR_VALUE_COUNT_ON_ROW', -22);

/**
 * The DSN submitted has problems
 */
define('DB_ERROR_INVALID_DSN', -23);

/**
 * Could not connect to the database
 */
define('DB_ERROR_CONNECT_FAILED', -24);

/**
 * The PHP extension needed for this DBMS could not be found
 */
define('DB_ERROR_EXTENSION_NOT_FOUND',-25);

/**
 * The present user has inadequate permissions to perform the task requestd
 */
define('DB_ERROR_ACCESS_VIOLATION', -26);

/**
 * The database requested does not exist
 */
define('DB_ERROR_NOSUCHDB', -27);

/**
 * Tried to insert a null value into a column that doesn't allow nulls
 */
define('DB_ERROR_CONSTRAINT_NOT_NULL',-29);
/**#@-*/


// }}}
// {{{ prepared statement-related


/**#@+
 * Identifiers for the placeholders used in prepared statements.
 * @see DB_common::prepare()
 */

/**
 * Indicates a scalar (<kbd>?</kbd>) placeholder was used
 *
 * Quote and escape the value as necessary.
 */
define('DB_PARAM_SCALAR', 1);

/**
 * Indicates an opaque (<kbd>&</kbd>) placeholder was used
 *
 * The value presented is a file name.  Extract the contents of that file
 * and place them in this column.
 */
define('DB_PARAM_OPAQUE', 2);

/**
 * Indicates a misc (<kbd>!</kbd>) placeholder was used
 *
 * The value should not be quoted or escaped.
 */
define('DB_PARAM_MISC',   3);
/**#@-*/


// }}}
// {{{ binary data-related


/**#@+
 * The different ways of returning binary data from queries.
 */

/**
 * Sends the fetched data straight through to output
 */
define('DB_BINMODE_PASSTHRU', 1);

/**
 * Lets you return data as usual
 */
define('DB_BINMODE_RETURN', 2);

/**
 * Converts the data to hex format before returning it
 *
 * For example the string "123" would become "313233".
 */
define('DB_BINMODE_CONVERT', 3);
/**#@-*/


// }}}
// {{{ fetch modes


/**#@+
 * Fetch Modes.
 * @see DB_common::setFetchMode()
 */

/**
 * Indicates the current default fetch mode should be used
 * @see DB_common::$fetchmode
 */
define('DB_FETCHMODE_DEFAULT', 0);

/**
 * Column data indexed by numbers, ordered from 0 and up
 */
define('DB_FETCHMODE_ORDERED', 1);

/**
 * Column data indexed by column names
 */
define('DB_FETCHMODE_ASSOC', 2);

/**
 * Column data as object properties
 */
define('DB_FETCHMODE_OBJECT', 3);

/**
 * For multi-dimensional results, make the column name the first level
 * of the array and put the row number in the second level of the array
 *
 * This is flipped from the normal behavior, which puts the row numbers
 * in the first level of the array and the column names in the second level.
 */
define('DB_FETCHMODE_FLIPPED', 4);
/**#@-*/

/**#@+
 * Old fetch modes.  Left here for compatibility.
 */
define('DB_GETMODE_ORDERED', DB_FETCHMODE_ORDERED);
define('DB_GETMODE_ASSOC',   DB_FETCHMODE_ASSOC);
define('DB_GETMODE_FLIPPED', DB_FETCHMODE_FLIPPED);
/**#@-*/


// }}}
// {{{ tableInfo() && autoPrepare()-related


/**#@+
 * The type of information to return from the tableInfo() method.
 *
 * Bitwised constants, so they can be combined using <kbd>|</kbd>
 * and removed using <kbd>^</kbd>.
 *
 * @see DB_common::tableInfo()
 *
 * {@internal Since the TABLEINFO constants are bitwised, if more of them are
 * added in the future, make sure to adjust DB_TABLEINFO_FULL accordingly.}}
 */
define('DB_TABLEINFO_ORDER', 1);
define('DB_TABLEINFO_ORDERTABLE', 2);
define('DB_TABLEINFO_FULL', 3);
/**#@-*/


/**#@+
 * The type of query to create with the automatic query building methods.
 * @see DB_common::autoPrepare(), DB_common::autoExecute()
 */
define('DB_AUTOQUERY_INSERT', 1);
define('DB_AUTOQUERY_UPDATE', 2);
/**#@-*/


// }}}
// {{{ portability modes


/**#@+
 * Portability Modes.
 *
 * Bitwised constants, so they can be combined using <kbd>|</kbd>
 * and removed using <kbd>^</kbd>.
 *
 * @see DB_common::setOption()
 *
 * {@internal Since the PORTABILITY constants are bitwised, if more of them are
 * added in the future, make sure to adjust DB_PORTABILITY_ALL accordingly.}}
 */

/**
 * Turn off all portability features
 */
define('DB_PORTABILITY_NONE', 0);

/**
 * Convert names of tables and fields to lower case
 * when using the get*(), fetch*() and tableInfo() methods
 */
define('DB_PORTABILITY_LOWERCASE', 1);

/**
 * Right trim the data output by get*() and fetch*()
 */
define('DB_PORTABILITY_RTRIM', 2);

/**
 * Force reporting the number of rows deleted
 */
define('DB_PORTABILITY_DELETE_COUNT', 4);

/**
 * Enable hack that makes numRows() work in Oracle
 */
define('DB_PORTABILITY_NUMROWS', 8);

/**
 * Makes certain error messages in certain drivers compatible
 * with those from other DBMS's
 *
 * + mysql, mysqli:  change unique/primary key constraints
 *   DB_ERROR_ALREADY_EXISTS -> DB_ERROR_CONSTRAINT
 *
 * + odbc(access):  MS's ODBC driver reports 'no such field' as code
 *   07001, which means 'too few parameters.'  When this option is on
 *   that code gets mapped to DB_ERROR_NOSUCHFIELD.
 */
define('DB_PORTABILITY_ERRORS', 16);

/**
 * Convert null values to empty strings in data output by
 * get*() and fetch*()
 */
define('DB_PORTABILITY_NULL_TO_EMPTY', 32);

/**
 * Turn on all portability features
 */
define('DB_PORTABILITY_ALL', 63);
/**#@-*/

// }}}


// }}}
// {{{ class DB

/**
 * Database independent query interface
 *
 * The main "DB" class is simply a container class with some static
 * methods for creating DB objects as well as some utility functions
 * common to all parts of DB.
 *
 * The object model of DB is as follows (indentation means inheritance):
 * <pre>
 * DB           The main DB class.  This is simply a utility class
 *              with some "static" methods for creating DB objects as
 *              well as common utility functions for other DB classes.
 *
 * DB_common    The base for each DB implementation.  Provides default
 * |            implementations (in OO lingo virtual methods) for
 * |            the actual DB implementations as well as a bunch of
 * |            query utility functions.
 * |
 * +-DB_mysql   The DB implementation for MySQL.  Inherits DB_common.
 *              When calling DB::factory or DB::connect for MySQL
 *              connections, the object returned is an instance of this
 *              class.
 * </pre>
 *
 * @category   Database
 * @package    DB
 * @author     Stig Bakken <ssb@php.net>
 * @author     Tomas V.V.Cox <cox@idecnet.com>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/DB
 */
class DB
{
    // {{{ &factory()

    /**
     * Create a new DB object for the specified database type but don't
     * connect to the database
     *
     * @param string $type     the database type (eg "mysql")
     * @param array  $options  an associative array of option names and values
     *
     * @return object  a new DB object.  A DB_Error object on failure.
     *
     * @see DB_common::setOption()
     */
    function &factory($type, $options = false)
    {
        if (!is_array($options)) {
            $options = array('persistent' => $options);
        }

        if (isset($options['debug']) && $options['debug'] >= 2) {
            // expose php errors with sufficient debug level
            include_once "DB/{$type}.php";
        } else {
            @include_once "DB/{$type}.php";
        }

        $classname = "DB_${type}";

        if (!class_exists($classname)) {
            $tmp = PEAR::raiseError(null, DB_ERROR_NOT_FOUND, null, null,
                                    "Unable to include the DB/{$type}.php"
                                    . " file for '$dsn'",
                                    'DB_Error', true);
            return $tmp;
        }

        @$obj =& new $classname;

        foreach ($options as $option => $value) {
            $test = $obj->setOption($option, $value);
            if (DB::isError($test)) {
                return $test;
            }
        }

        return $obj;
    }

    // }}}
    // {{{ &connect()

    /**
     * Create a new DB object including a connection to the specified database
     *
     * Example 1.
     * <code>
     * require_once 'DB.php';
     *
     * $dsn = 'pgsql://user:password@host/database';
     * $options = array(
     *     'debug'       => 2,
     *     'portability' => DB_PORTABILITY_ALL,
     * );
     *
     * $db =& DB::connect($dsn, $options);
     * if (PEAR::isError($db)) {
     *     die($db->getMessage());
     * }
     * </code>
     *
     * @param mixed $dsn      the string "data source name" or array in the
     *                         format returned by DB::parseDSN()
     * @param array $options  an associative array of option names and values
     *
     * @return object  a new DB object.  A DB_Error object on failure.
     *
     * @uses DB_dbase::connect(), DB_fbsql::connect(), DB_ibase::connect(),
     *       DB_ifx::connect(), DB_msql::connect(), DB_mssql::connect(),
     *       DB_mysql::connect(), DB_mysqli::connect(), DB_oci8::connect(),
     *       DB_odbc::connect(), DB_pgsql::connect(), DB_sqlite::connect(),
     *       DB_sybase::connect()
     *
     * @uses DB::parseDSN(), DB_common::setOption(), PEAR::isError()
     */
    function &connect($dsn, $options = array())
    {
        $dsninfo = DB::parseDSN($dsn);
        $type = $dsninfo['phptype'];

        if (!is_array($options)) {
            /*
             * For backwards compatibility.  $options used to be boolean,
             * indicating whether the connection should be persistent.
             */
            $options = array('persistent' => $options);
        }

        if (isset($options['debug']) && $options['debug'] >= 2) {
            // expose php errors with sufficient debug level
            include_once "DB/${type}.php";
        } else {
            @include_once "DB/${type}.php";
        }

        $classname = "DB_${type}";
        if (!class_exists($classname)) {
            $tmp = PEAR::raiseError(null, DB_ERROR_NOT_FOUND, null, null,
                                    "Unable to include the DB/{$type}.php"
                                    . " file for '$dsn'",
                                    'DB_Error', true);
            return $tmp;
        }

        @$obj =& new $classname;

        foreach ($options as $option => $value) {
            $test = $obj->setOption($option, $value);
            if (DB::isError($test)) {
                return $test;
            }
        }

        $err = $obj->connect($dsninfo, $obj->getOption('persistent'));
        if (DB::isError($err)) {
            $err->addUserInfo($dsn);
            return $err;
        }

        return $obj;
    }

    // }}}
    // {{{ apiVersion()

    /**
     * Return the DB API version
     *
     * @return string  the DB API version number
     */
    function apiVersion()
    {
        return '@package_version@';
    }

    // }}}
    // {{{ isError()

    /**
     * Determines if a variable is a DB_Error object
     *
     * @param mixed $value  the variable to check
     *
     * @return bool  whether $value is DB_Error object
     */
    function isError($value)
    {
        return is_a($value, 'DB_Error');
    }

    // }}}
    // {{{ isConnection()

    /**
     * Determines if a value is a DB_<driver> object
     *
     * @param mixed $value  the value to test
     *
     * @return bool  whether $value is a DB_<driver> object
     */
    function isConnection($value)
    {
        return (is_object($value) &&
                is_subclass_of($value, 'db_common') &&
                method_exists($value, 'simpleQuery'));
    }

    // }}}
    // {{{ isManip()

    /**
     * Tell whether a query is a data manipulation or data definition query
     *
     * Examples of data manipulation queries are INSERT, UPDATE and DELETE.
     * Examples of data definition queries are CREATE, DROP, ALTER, GRANT,
     * REVOKE.
     *
     * @param string $query  the query
     *
     * @return boolean  whether $query is a data manipulation query
     */
    function isManip($query)
    {
        $manips = 'INSERT|UPDATE|DELETE|REPLACE|'
                . 'CREATE|DROP|'
                . 'LOAD DATA|SELECT .* INTO|COPY|'
                . 'ALTER|GRANT|REVOKE|'
                . 'LOCK|UNLOCK';
        if (preg_match('/^\s*"?(' . $manips . ')\s+/i', $query)) {
            return true;
        }
        return false;
    }

    // }}}
    // {{{ errorMessage()

    /**
     * Return a textual error message for a DB error code
     *
     * @param integer $value  the DB error code
     *
     * @return string  the error message or false if the error code was
     *                  not recognized
     */
    function errorMessage($value)
    {
        static $errorMessages;
        if (!isset($errorMessages)) {
            $errorMessages = array(
                DB_ERROR                    => 'unknown error',
                DB_ERROR_ACCESS_VIOLATION   => 'insufficient permissions',
                DB_ERROR_ALREADY_EXISTS     => 'already exists',
                DB_ERROR_CANNOT_CREATE      => 'can not create',
                DB_ERROR_CANNOT_DROP        => 'can not drop',
                DB_ERROR_CONNECT_FAILED     => 'connect failed',
                DB_ERROR_CONSTRAINT         => 'constraint violation',
                DB_ERROR_CONSTRAINT_NOT_NULL=> 'null value violates not-null constraint',
                DB_ERROR_DIVZERO            => 'division by zero',
                DB_ERROR_EXTENSION_NOT_FOUND=> 'extension not found',
                DB_ERROR_INVALID            => 'invalid',
                DB_ERROR_INVALID_DATE       => 'invalid date or time',
                DB_ERROR_INVALID_DSN        => 'invalid DSN',
                DB_ERROR_INVALID_NUMBER     => 'invalid number',
                DB_ERROR_MISMATCH           => 'mismatch',
                DB_ERROR_NEED_MORE_DATA     => 'insufficient data supplied',
                DB_ERROR_NODBSELECTED       => 'no database selected',
                DB_ERROR_NOSUCHDB           => 'no such database',
                DB_ERROR_NOSUCHFIELD        => 'no such field',
                DB_ERROR_NOSUCHTABLE        => 'no such table',
                DB_ERROR_NOT_CAPABLE        => 'DB backend not capable',
                DB_ERROR_NOT_FOUND          => 'not found',
                DB_ERROR_NOT_LOCKED         => 'not locked',
                DB_ERROR_SYNTAX             => 'syntax error',
                DB_ERROR_UNSUPPORTED        => 'not supported',
                DB_ERROR_TRUNCATED          => 'truncated',
                DB_ERROR_VALUE_COUNT_ON_ROW => 'value count on row',
                DB_OK                       => 'no error',
            );
        }

        if (DB::isError($value)) {
            $value = $value->getCode();
        }

        return isset($errorMessages[$value]) ? $errorMessages[$value]
                     : $errorMessages[DB_ERROR];
    }

    // }}}
    // {{{ parseDSN()

    /**
     * Parse a data source name
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
     * @param string $dsn Data Source Name to be parsed
     *
     * @return array an associative array with the following keys:
     *  + phptype:  Database backend used in PHP (mysql, odbc etc.)
     *  + dbsyntax: Database used with regards to SQL syntax etc.
     *  + protocol: Communication protocol to use (tcp, unix etc.)
     *  + hostspec: Host specification (hostname[:port])
     *  + database: Database to use on the DBMS server
     *  + username: User name for login
     *  + password: Password for login
     */
    function parseDSN($dsn)
    {
        $parsed = array(
            'phptype'  => false,
            'dbsyntax' => false,
            'username' => false,
            'password' => false,
            'protocol' => false,
            'hostspec' => false,
            'port'     => false,
            'socket'   => false,
            'database' => false,
        );

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

        if (preg_match('|^([^(]+)\((.*?)\)/?(.*?)$|', $dsn, $match)) {
            // $dsn => proto(proto_opts)/database
            $proto       = $match[1];
            $proto_opts  = $match[2] ? $match[2] : false;
            $dsn         = $match[3];

        } else {
            // $dsn => protocol+hostspec/database (old format)
            if (strpos($dsn, '+') !== false) {
                list($proto, $dsn) = explode('+', $dsn, 2);
            }
            if (strpos($dsn, '/') !== false) {
                list($proto_opts, $dsn) = explode('/', $dsn, 2);
            } else {
                $proto_opts = $dsn;
                $dsn = null;
            }
        }

        // process the different protocol options
        $parsed['protocol'] = (!empty($proto)) ? $proto : 'tcp';
        $proto_opts = rawurldecode($proto_opts);
        if ($parsed['protocol'] == 'tcp') {
            if (strpos($proto_opts, ':') !== false) {
                list($parsed['hostspec'],
                     $parsed['port']) = explode(':', $proto_opts);
            } else {
                $parsed['hostspec'] = $proto_opts;
            }
        } elseif ($parsed['protocol'] == 'unix') {
            $parsed['socket'] = $proto_opts;
        }

        // Get dabase if any
        // $dsn => database
        if ($dsn) {
            if (($pos = strpos($dsn, '?')) === false) {
                // /database
                $parsed['database'] = rawurldecode($dsn);
            } else {
                // /database?param1=value1&param2=value2
                $parsed['database'] = rawurldecode(substr($dsn, 0, $pos));
                $dsn = substr($dsn, $pos + 1);
                if (strpos($dsn, '&') !== false) {
                    $opts = explode('&', $dsn);
                } else { // database?param1=value1
                    $opts = array($dsn);
                }
                foreach ($opts as $opt) {
                    list($key, $value) = explode('=', $opt);
                    if (!isset($parsed[$key])) {
                        // don't allow params overwrite
                        $parsed[$key] = rawurldecode($value);
                    }
                }
            }
        }

        return $parsed;
    }

    // }}}
}

// }}}
// {{{ class DB_Error

/**
 * DB_Error implements a class for reporting portable database error
 * messages
 *
 * @category   Database
 * @package    DB
 * @author     Stig Bakken <ssb@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/DB
 */
class DB_Error extends PEAR_Error
{
    // {{{ constructor

    /**
     * DB_Error constructor
     *
     * @param mixed $code       DB error code, or string with error message
     * @param int   $mode       what "error mode" to operate in
     * @param int   $level      what error level to use for $mode &
     *                           PEAR_ERROR_TRIGGER
     * @param mixed $debuginfo  additional debug info, such as the last query
     *
     * @see PEAR_Error
     */
    function DB_Error($code = DB_ERROR, $mode = PEAR_ERROR_RETURN,
                      $level = E_USER_NOTICE, $debuginfo = null)
    {
        if (is_int($code)) {
            $this->PEAR_Error('DB Error: ' . DB::errorMessage($code), $code,
                              $mode, $level, $debuginfo);
        } else {
            $this->PEAR_Error("DB Error: $code", DB_ERROR,
                              $mode, $level, $debuginfo);
        }
    }

    // }}}
}

// }}}
// {{{ class DB_result

/**
 * This class implements a wrapper for a DB result set
 *
 * A new instance of this class will be returned by the DB implementation
 * after processing a query that returns data.
 *
 * @category   Database
 * @package    DB
 * @author     Stig Bakken <ssb@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/DB
 */
class DB_result
{
    // {{{ properties

    /**
     * Should results be freed automatically when there are no more rows?
     * @var boolean
     * @see DB_common::$options
     */
    var $autofree;

    /**
     * A reference to the DB_<driver> object
     * @var object
     */
    var $dbh;

    /**
     * The current default fetch mode
     * @var integer
     * @see DB_common::$fetchmode
     */
    var $fetchmode;

    /**
     * The name of the class into which results should be fetched when
     * DB_FETCHMODE_OBJECT is in effect
     *
     * @var string
     * @see DB_common::$fetchmode_object_class
     */
    var $fetchmode_object_class;

    /**
     * The number of rows to fetch from a limit query
     * @var integer
     */
    var $limit_count = null;

    /**
     * The row to start fetching from in limit queries
     * @var integer
     */
    var $limit_from = null;

    /**
     * The execute parameters that created this result
     * @var array
     * @since Property available since Release 1.7.0
     */
    var $parameters;

    /**
     * The query string that created this result
     *
     * Copied here incase it changes in $dbh, which is referenced
     *
     * @var string
     * @since Property available since Release 1.7.0
     */
    var $query;

    /**
     * The query result resource id created by PHP
     * @var resource
     */
    var $result;

    /**
     * The present row being dealt with
     * @var integer
     */
    var $row_counter = null;

    /**
     * The prepared statement resource id created by PHP in $dbh
     *
     * This resource is only available when the result set was created using
     * a driver's native execute() method, not PEAR DB's emulated one.
     *
     * Copied here incase it changes in $dbh, which is referenced
     *
     * {@internal  Mainly here because the InterBase/Firebird API is only
     * able to retrieve data from result sets if the statemnt handle is
     * still in scope.}}
     *
     * @var resource
     * @since Property available since Release 1.7.0
     */
    var $statement;


    // }}}
    // {{{ constructor

    /**
     * This constructor sets the object's properties
     *
     * @param object   &$dbh     the DB object reference
     * @param resource $result   the result resource id
     * @param array    $options  an associative array with result options
     *
     * @return void
     */
    function DB_result(&$dbh, $result, $options = array())
    {
        $this->autofree    = $dbh->options['autofree'];
        $this->dbh         = &$dbh;
        $this->fetchmode   = $dbh->fetchmode;
        $this->fetchmode_object_class = $dbh->fetchmode_object_class;
        $this->parameters  = $dbh->last_parameters;
        $this->query       = $dbh->last_query;
        $this->result      = $result;
        $this->statement   = empty($dbh->last_stmt) ? null : $dbh->last_stmt;
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }
    }

    /**
     * Set options for the DB_result object
     *
     * @param string $key    the option to set
     * @param mixed  $value  the value to set the option to
     *
     * @return void
     */
    function setOption($key, $value = null)
    {
        switch ($key) {
            case 'limit_from':
                $this->limit_from = $value;
                break;
            case 'limit_count':
                $this->limit_count = $value;
        }
    }

    // }}}
    // {{{ fetchRow()

    /**
     * Fetch a row of data and return it by reference into an array
     *
     * The type of array returned can be controlled either by setting this
     * method's <var>$fetchmode</var> parameter or by changing the default
     * fetch mode setFetchMode() before calling this method.
     *
     * There are two options for standardizing the information returned
     * from databases, ensuring their values are consistent when changing
     * DBMS's.  These portability options can be turned on when creating a
     * new DB object or by using setOption().
     *
     *   + <var>DB_PORTABILITY_LOWERCASE</var>
     *     convert names of fields to lower case
     *
     *   + <var>DB_PORTABILITY_RTRIM</var>
     *     right trim the data
     *
     * @param int $fetchmode  the constant indicating how to format the data
     * @param int $rownum     the row number to fetch (index starts at 0)
     *
     * @return mixed  an array or object containing the row's data,
     *                 NULL when the end of the result set is reached
     *                 or a DB_Error object on failure.
     *
     * @see DB_common::setOption(), DB_common::setFetchMode()
     */
    function &fetchRow($fetchmode = DB_FETCHMODE_DEFAULT, $rownum = null)
    {
        if ($fetchmode === DB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        if ($fetchmode === DB_FETCHMODE_OBJECT) {
            $fetchmode = DB_FETCHMODE_ASSOC;
            $object_class = $this->fetchmode_object_class;
        }
        if ($this->limit_from !== null) {
            if ($this->row_counter === null) {
                $this->row_counter = $this->limit_from;
                // Skip rows
                if ($this->dbh->features['limit'] === false) {
                    $i = 0;
                    while ($i++ < $this->limit_from) {
                        $this->dbh->fetchInto($this->result, $arr, $fetchmode);
                    }
                }
            }
            if ($this->row_counter >= ($this->limit_from + $this->limit_count))
            {
                if ($this->autofree) {
                    $this->free();
                }
                $tmp = null;
                return $tmp;
            }
            if ($this->dbh->features['limit'] === 'emulate') {
                $rownum = $this->row_counter;
            }
            $this->row_counter++;
        }
        $res = $this->dbh->fetchInto($this->result, $arr, $fetchmode, $rownum);
        if ($res === DB_OK) {
            if (isset($object_class)) {
                // The default mode is specified in the
                // DB_common::fetchmode_object_class property
                if ($object_class == 'stdClass') {
                    $arr = (object) $arr;
                } else {
                    $arr = &new $object_class($arr);
                }
            }
            return $arr;
        }
        if ($res == null && $this->autofree) {
            $this->free();
        }
        return $res;
    }

    // }}}
    // {{{ fetchInto()

    /**
     * Fetch a row of data into an array which is passed by reference
     *
     * The type of array returned can be controlled either by setting this
     * method's <var>$fetchmode</var> parameter or by changing the default
     * fetch mode setFetchMode() before calling this method.
     *
     * There are two options for standardizing the information returned
     * from databases, ensuring their values are consistent when changing
     * DBMS's.  These portability options can be turned on when creating a
     * new DB object or by using setOption().
     *
     *   + <var>DB_PORTABILITY_LOWERCASE</var>
     *     convert names of fields to lower case
     *
     *   + <var>DB_PORTABILITY_RTRIM</var>
     *     right trim the data
     *
     * @param array &$arr       the variable where the data should be placed
     * @param int   $fetchmode  the constant indicating how to format the data
     * @param int   $rownum     the row number to fetch (index starts at 0)
     *
     * @return mixed  DB_OK if a row is processed, NULL when the end of the
     *                 result set is reached or a DB_Error object on failure
     *
     * @see DB_common::setOption(), DB_common::setFetchMode()
     */
    function fetchInto(&$arr, $fetchmode = DB_FETCHMODE_DEFAULT, $rownum = null)
    {
        if ($fetchmode === DB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        if ($fetchmode === DB_FETCHMODE_OBJECT) {
            $fetchmode = DB_FETCHMODE_ASSOC;
            $object_class = $this->fetchmode_object_class;
        }
        if ($this->limit_from !== null) {
            if ($this->row_counter === null) {
                $this->row_counter = $this->limit_from;
                // Skip rows
                if ($this->dbh->features['limit'] === false) {
                    $i = 0;
                    while ($i++ < $this->limit_from) {
                        $this->dbh->fetchInto($this->result, $arr, $fetchmode);
                    }
                }
            }
            if ($this->row_counter >= (
                    $this->limit_from + $this->limit_count))
            {
                if ($this->autofree) {
                    $this->free();
                }
                return null;
            }
            if ($this->dbh->features['limit'] === 'emulate') {
                $rownum = $this->row_counter;
            }

            $this->row_counter++;
        }
        $res = $this->dbh->fetchInto($this->result, $arr, $fetchmode, $rownum);
        if ($res === DB_OK) {
            if (isset($object_class)) {
                // default mode specified in the
                // DB_common::fetchmode_object_class property
                if ($object_class == 'stdClass') {
                    $arr = (object) $arr;
                } else {
                    $arr = new $object_class($arr);
                }
            }
            return DB_OK;
        }
        if ($res == null && $this->autofree) {
            $this->free();
        }
        return $res;
    }

    // }}}
    // {{{ numCols()

    /**
     * Get the the number of columns in a result set
     *
     * @return int  the number of columns.  A DB_Error object on failure.
     */
    function numCols()
    {
        return $this->dbh->numCols($this->result);
    }

    // }}}
    // {{{ numRows()

    /**
     * Get the number of rows in a result set
     *
     * @return int  the number of rows.  A DB_Error object on failure.
     */
    function numRows()
    {
        if ($this->dbh->features['numrows'] === 'emulate'
            && $this->dbh->options['portability'] & DB_PORTABILITY_NUMROWS)
        {
            if ($this->dbh->features['prepare']) {
                $res = $this->dbh->query($this->query, $this->parameters);
            } else {
                $res = $this->dbh->query($this->query);
            }
            if (DB::isError($res)) {
                return $res;
            }
            $i = 0;
            while ($res->fetchInto($tmp, DB_FETCHMODE_ORDERED)) {
                $i++;
            }
            return $i;
        } else {
            return $this->dbh->numRows($this->result);
        }
    }

    // }}}
    // {{{ nextResult()

    /**
     * Get the next result if a batch of queries was executed
     *
     * @return bool  true if a new result is available or false if not
     */
    function nextResult()
    {
        return $this->dbh->nextResult($this->result);
    }

    // }}}
    // {{{ free()

    /**
     * Frees the resources allocated for this result set
     *
     * @return bool  true on success.  A DB_Error object on failure.
     */
    function free()
    {
        $err = $this->dbh->freeResult($this->result);
        if (DB::isError($err)) {
            return $err;
        }
        $this->result = false;
        $this->statement = false;
        return true;
    }

    // }}}
    // {{{ tableInfo()

    /**
     * @see DB_common::tableInfo()
     * @deprecated Method deprecated some time before Release 1.2
     */
    function tableInfo($mode = null)
    {
        if (is_string($mode)) {
            return $this->dbh->raiseError(DB_ERROR_NEED_MORE_DATA);
        }
        return $this->dbh->tableInfo($this, $mode);
    }

    // }}}
    // {{{ getQuery()

    /**
     * Determine the query string that created this result
     *
     * @return string  the query string
     *
     * @since Method available since Release 1.7.0
     */
    function getQuery()
    {
        return $this->query;
    }

    // }}}
    // {{{ getRowCounter()

    /**
     * Tells which row number is currently being processed
     *
     * @return integer  the current row being looked at.  Starts at 1.
     */
    function getRowCounter()
    {
        return $this->row_counter;
    }

    // }}}
}

// }}}
// {{{ class DB_row

/**
 * PEAR DB Row Object
 *
 * The object contains a row of data from a result set.  Each column's data
 * is placed in a property named for the column.
 *
 * @category   Database
 * @package    DB
 * @author     Stig Bakken <ssb@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/DB
 * @see        DB_common::setFetchMode()
 */
class DB_row
{
    // {{{ constructor

    /**
     * The constructor places a row's data into properties of this object
     *
     * @param array  the array containing the row's data
     *
     * @return void
     */
    function DB_row(&$arr)
    {
        foreach ($arr as $key => $value) {
            $this->$key = &$arr[$key];
        }
    }

    // }}}
}

// }}}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */

?>
