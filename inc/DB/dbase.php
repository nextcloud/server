<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * The PEAR DB driver for PHP's dbase extension
 * for interacting with dBase databases
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
 * @author     Tomas V.V. Cox <cox@idecnet.com>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: dbase.php,v 1.39 2005/02/19 23:25:25 danielc Exp $
 * @link       http://pear.php.net/package/DB
 */

/**
 * Obtain the DB_common class so it can be extended from
 */
require_once 'DB/common.php';

/**
 * The methods PEAR DB uses to interact with PHP's dbase extension
 * for interacting with dBase databases
 *
 * These methods overload the ones declared in DB_common.
 *
 * @category   Database
 * @package    DB
 * @author     Tomas V.V. Cox <cox@idecnet.com>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/DB
 */
class DB_dbase extends DB_common
{
    // {{{ properties

    /**
     * The DB driver type (mysql, oci8, odbc, etc.)
     * @var string
     */
    var $phptype = 'dbase';

    /**
     * The database syntax variant to be used (db2, access, etc.), if any
     * @var string
     */
    var $dbsyntax = 'dbase';

    /**
     * The capabilities of this DB implementation
     *
     * The 'new_link' element contains the PHP version that first provided
     * new_link support for this DBMS.  Contains false if it's unsupported.
     *
     * Meaning of the 'limit' element:
     *   + 'emulate' = emulate with fetch row by number
     *   + 'alter'   = alter the query
     *   + false     = skip rows
     *
     * @var array
     */
    var $features = array(
        'limit'         => false,
        'new_link'      => false,
        'numrows'       => true,
        'pconnect'      => false,
        'prepare'       => false,
        'ssl'           => false,
        'transactions'  => false,
    );

    /**
     * A mapping of native error codes to DB error codes
     * @var array
     */
    var $errorcode_map = array(
    );

    /**
     * The raw database connection created by PHP
     * @var resource
     */
    var $connection;

    /**
     * The DSN information for connecting to a database
     * @var array
     */
    var $dsn = array();


    /**
     * A means of emulating result resources
     * @var array
     */
    var $res_row = array();

    /**
     * The quantity of results so far
     *
     * For emulating result resources.
     *
     * @var integer
     */
    var $result = 0;

    /**
     * Maps dbase data type id's to human readable strings
     *
     * The human readable values are based on the output of PHP's
     * dbase_get_header_info() function.
     *
     * @var array
     * @since Property available since Release 1.7.0
     */
    var $types = array(
        'C' => 'character',
        'D' => 'date',
        'L' => 'boolean',
        'M' => 'memo',
        'N' => 'number',
    );


    // }}}
    // {{{ constructor

    /**
     * This constructor calls <kbd>$this->DB_common()</kbd>
     *
     * @return void
     */
    function DB_dbase()
    {
        $this->DB_common();
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to the database and create it if it doesn't exist
     *
     * Don't call this method directly.  Use DB::connect() instead.
     *
     * PEAR DB's dbase driver supports the following extra DSN options:
     *   + mode    An integer specifying the read/write mode to use
     *              (0 = read only, 1 = write only, 2 = read/write).
     *              Available since PEAR DB 1.7.0.
     *   + fields  An array of arrays that PHP's dbase_create() function needs
     *              to create a new database.  This information is used if the
     *              dBase file specified in the "database" segment of the DSN
     *              does not exist.  For more info, see the PHP manual's
     *              {@link http://php.net/dbase_create dbase_create()} page.
     *              Available since PEAR DB 1.7.0.
     *
     * Example of how to connect and establish a new dBase file if necessary:
     * <code>
     * require_once 'DB.php';
     *
     * $dsn = array(
     *     'phptype'  => 'dbase',
     *     'database' => '/path/and/name/of/dbase/file',
     *     'mode'     => 2,
     *     'fields'   => array(
     *         array('a', 'N', 5, 0),
     *         array('b', 'C', 40),
     *         array('c', 'C', 255),
     *         array('d', 'C', 20),
     *     ),
     * );
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
     * @param array $dsn         the data source name
     * @param bool  $persistent  should the connection be persistent?
     *
     * @return int  DB_OK on success. A DB_Error object on failure.
     */
    function connect($dsn, $persistent = false)
    {
        if (!PEAR::loadExtension('dbase')) {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }

        $this->dsn = $dsn;
        if ($dsn['dbsyntax']) {
            $this->dbsyntax = $dsn['dbsyntax'];
        }

        /*
         * Turn track_errors on for entire script since $php_errormsg
         * is the only way to find errors from the dbase extension.
         */
        ini_set('track_errors', 1);
        $php_errormsg = '';

        if (!file_exists($dsn['database'])) {
            $this->dsn['mode'] = 2;
            if (empty($dsn['fields']) || !is_array($dsn['fields'])) {
                return $this->raiseError(DB_ERROR_CONNECT_FAILED,
                                         null, null, null,
                                         'the dbase file does not exist and '
                                         . 'it could not be created because '
                                         . 'the "fields" element of the DSN '
                                         . 'is not properly set');
            }
            $this->connection = @dbase_create($dsn['database'],
                                              $dsn['fields']);
            if (!$this->connection) {
                return $this->raiseError(DB_ERROR_CONNECT_FAILED,
                                         null, null, null,
                                         'the dbase file does not exist and '
                                         . 'the attempt to create it failed: '
                                         . $php_errormsg);
            }
        } else {
            if (!isset($this->dsn['mode'])) {
                $this->dsn['mode'] = 0;
            }
            $this->connection = @dbase_open($dsn['database'],
                                            $this->dsn['mode']);
            if (!$this->connection) {
                return $this->raiseError(DB_ERROR_CONNECT_FAILED,
                                         null, null, null,
                                         $php_errormsg);
            }
        }
        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

    /**
     * Disconnects from the database server
     *
     * @return bool  TRUE on success, FALSE on failure
     */
    function disconnect()
    {
        $ret = @dbase_close($this->connection);
        $this->connection = null;
        return $ret;
    }

    // }}}
    // {{{ &query()

    function &query($query = null)
    {
        // emulate result resources
        $this->res_row[(int)$this->result] = 0;
        $tmp =& new DB_result($this, $this->result++);
        return $tmp;
    }

    // }}}
    // {{{ fetchInto()

    /**
     * Places a row from the result set into the given array
     *
     * Formating of the array and the data therein are configurable.
     * See DB_result::fetchInto() for more information.
     *
     * This method is not meant to be called directly.  Use
     * DB_result::fetchInto() instead.  It can't be declared "protected"
     * because DB_result is a separate object.
     *
     * @param resource $result    the query result resource
     * @param array    $arr       the referenced array to put the data in
     * @param int      $fetchmode how the resulting array should be indexed
     * @param int      $rownum    the row number to fetch (0 = first row)
     *
     * @return mixed  DB_OK on success, NULL when the end of a result set is
     *                 reached or on failure
     *
     * @see DB_result::fetchInto()
     */
    function fetchInto($result, &$arr, $fetchmode, $rownum = null)
    {
        if ($rownum === null) {
            $rownum = $this->res_row[(int)$result]++;
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $arr = @dbase_get_record_with_names($this->connection, $rownum);
            if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE && $arr) {
                $arr = array_change_key_case($arr, CASE_LOWER);
            }
        } else {
            $arr = @dbase_get_record($this->connection, $rownum);
        }
        if (!$arr) {
            return null;
        }
        if ($this->options['portability'] & DB_PORTABILITY_RTRIM) {
            $this->_rtrimArrayValues($arr);
        }
        if ($this->options['portability'] & DB_PORTABILITY_NULL_TO_EMPTY) {
            $this->_convertNullArrayValuesToEmpty($arr);
        }
        return DB_OK;
    }

    // }}}
    // {{{ numCols()

    /**
     * Gets the number of columns in a result set
     *
     * This method is not meant to be called directly.  Use
     * DB_result::numCols() instead.  It can't be declared "protected"
     * because DB_result is a separate object.
     *
     * @param resource $result  PHP's query result resource
     *
     * @return int  the number of columns.  A DB_Error object on failure.
     *
     * @see DB_result::numCols()
     */
    function numCols($foo)
    {
        return @dbase_numfields($this->connection);
    }

    // }}}
    // {{{ numRows()

    /**
     * Gets the number of rows in a result set
     *
     * This method is not meant to be called directly.  Use
     * DB_result::numRows() instead.  It can't be declared "protected"
     * because DB_result is a separate object.
     *
     * @param resource $result  PHP's query result resource
     *
     * @return int  the number of rows.  A DB_Error object on failure.
     *
     * @see DB_result::numRows()
     */
    function numRows($foo)
    {
        return @dbase_numrecords($this->connection);
    }

    // }}}
    // {{{ quoteSmart()

    /**
     * Formats input so it can be safely used in a query
     *
     * @param mixed $in  the data to be formatted
     *
     * @return mixed  the formatted data.  The format depends on the input's
     *                 PHP type:
     *                 + null = the string <samp>NULL</samp>
     *                 + boolean = <samp>T</samp> if true or
     *                   <samp>F</samp> if false.  Use the <kbd>Logical</kbd>
     *                   data type.
     *                 + integer or double = the unquoted number
     *                 + other (including strings and numeric strings) =
     *                   the data with single quotes escaped by preceeding
     *                   single quotes then the whole string is encapsulated
     *                   between single quotes
     *
     * @see DB_common::quoteSmart()
     * @since Method available since Release 1.6.0
     */
    function quoteSmart($in)
    {
        if (is_int($in) || is_double($in)) {
            return $in;
        } elseif (is_bool($in)) {
            return $in ? 'T' : 'F';
        } elseif (is_null($in)) {
            return 'NULL';
        } else {
            return "'" . $this->escapeSimple($in) . "'";
        }
    }

    // }}}
    // {{{ tableInfo()

    /**
     * Returns information about the current database
     *
     * @param mixed $result  THIS IS UNUSED IN DBASE.  The current database
     *                       is examined regardless of what is provided here.
     * @param int   $mode    a valid tableInfo mode
     *
     * @return array  an associative array with the information requested.
     *                 A DB_Error object on failure.
     *
     * @see DB_common::tableInfo()
     * @since Method available since Release 1.7.0
     */
    function tableInfo($result = null, $mode = null)
    {
        if (function_exists('dbase_get_header_info')) {
            $id = @dbase_get_header_info($this->connection);
            if (!$id && $php_errormsg) {
                return $this->raiseError(DB_ERROR,
                                         null, null, null,
                                         $php_errormsg);
            }
        } else {
            /*
             * This segment for PHP 4 is loosely based on code by
             * Hadi Rusiah <deegos@yahoo.com> in the comments on
             * the dBase reference page in the PHP manual.
             */
            $db = @fopen($this->dsn['database'], 'r');
            if (!$db) {
                return $this->raiseError(DB_ERROR_CONNECT_FAILED,
                                         null, null, null,
                                         $php_errormsg);
            }

            $id = array();
            $i  = 0;

            $line = fread($db, 32);
            while (!feof($db)) {
                $line = fread($db, 32);
                if (substr($line, 0, 1) == chr(13)) {
                    break;
                } else {
                    $pos = strpos(substr($line, 0, 10), chr(0));
                    $pos = ($pos == 0 ? 10 : $pos);
                    $id[$i] = array(
                        'name'   => substr($line, 0, $pos),
                        'type'   => $this->types[substr($line, 11, 1)],
                        'length' => ord(substr($line, 16, 1)),
                        'precision' => ord(substr($line, 17, 1)),
                    );
                }
                $i++;
            }

            fclose($db);
        }

        if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE) {
            $case_func = 'strtolower';
        } else {
            $case_func = 'strval';
        }

        $res   = array();
        $count = count($id);

        if ($mode) {
            $res['num_fields'] = $count;
        }

        for ($i = 0; $i < $count; $i++) {
            $res[$i] = array(
                'table' => $this->dsn['database'],
                'name'  => $case_func($id[$i]['name']),
                'type'  => $id[$i]['type'],
                'len'   => $id[$i]['length'],
                'flags' => ''
            );
            if ($mode & DB_TABLEINFO_ORDER) {
                $res['order'][$res[$i]['name']] = $i;
            }
            if ($mode & DB_TABLEINFO_ORDERTABLE) {
                $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
            }
        }

        return $res;
    }

    // }}}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */

?>
