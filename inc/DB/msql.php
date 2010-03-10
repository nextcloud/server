<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * The PEAR DB driver for PHP's msql extension
 * for interacting with Mini SQL databases
 *
 * PHP's mSQL extension did weird things with NULL values prior to PHP
 * 4.3.11 and 5.0.4.  Make sure your version of PHP meets or exceeds
 * those versions.
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
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: msql.php,v 1.57 2005/02/22 07:26:46 danielc Exp $
 * @link       http://pear.php.net/package/DB
 */

/**
 * Obtain the DB_common class so it can be extended from
 */
require_once 'DB/common.php';

/**
 * The methods PEAR DB uses to interact with PHP's msql extension
 * for interacting with Mini SQL databases
 *
 * These methods overload the ones declared in DB_common.
 *
 * PHP's mSQL extension did weird things with NULL values prior to PHP
 * 4.3.11 and 5.0.4.  Make sure your version of PHP meets or exceeds
 * those versions.
 *
 * @category   Database
 * @package    DB
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/DB
 * @since      Class not functional until Release 1.7.0
 */
class DB_msql extends DB_common
{
    // {{{ properties

    /**
     * The DB driver type (mysql, oci8, odbc, etc.)
     * @var string
     */
    var $phptype = 'msql';

    /**
     * The database syntax variant to be used (db2, access, etc.), if any
     * @var string
     */
    var $dbsyntax = 'msql';

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
        'limit'         => 'emulate',
        'new_link'      => false,
        'numrows'       => true,
        'pconnect'      => true,
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
     * The query result resource created by PHP
     *
     * Used to make affectedRows() work.  Only contains the result for
     * data manipulation queries.  Contains false for other queries.
     *
     * @var resource
     * @access private
     */
    var $_result;


    // }}}
    // {{{ constructor

    /**
     * This constructor calls <kbd>$this->DB_common()</kbd>
     *
     * @return void
     */
    function DB_msql()
    {
        $this->DB_common();
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to the database server, log in and open the database
     *
     * Don't call this method directly.  Use DB::connect() instead.
     *
     * Example of how to connect:
     * <code>
     * require_once 'DB.php';
     * 
     * // $dsn = 'msql://hostname/dbname';  // use a TCP connection
     * $dsn = 'msql:///dbname';             // use a socket
     * $options = array(
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
        if (!PEAR::loadExtension('msql')) {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }

        $this->dsn = $dsn;
        if ($dsn['dbsyntax']) {
            $this->dbsyntax = $dsn['dbsyntax'];
        }

        $params = array();
        if ($dsn['hostspec']) {
            $params[] = $dsn['port']
                        ? $dsn['hostspec'] . ',' . $dsn['port']
                        : $dsn['hostspec'];
        }

        $connect_function = $persistent ? 'msql_pconnect' : 'msql_connect';

        $ini = ini_get('track_errors');
        $php_errormsg = '';
        if ($ini) {
            $this->connection = @call_user_func_array($connect_function,
                                                      $params);
        } else {
            ini_set('track_errors', 1);
            $this->connection = @call_user_func_array($connect_function,
                                                      $params);
            ini_set('track_errors', $ini);
        }

        if (!$this->connection) {
            if (($err = @msql_error()) != '') {
                return $this->raiseError(DB_ERROR_CONNECT_FAILED,
                                         null, null, null,
                                         $err);
            } else {
                return $this->raiseError(DB_ERROR_CONNECT_FAILED,
                                         null, null, null,
                                         $php_errormsg);
            }
        }

        if (!@msql_select_db($dsn['database'], $this->connection)) {
            return $this->msqlRaiseError();
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
        $ret = @msql_close($this->connection);
        $this->connection = null;
        return $ret;
    }

    // }}}
    // {{{ simpleQuery()

    /**
     * Sends a query to the database server
     *
     * @param string  the SQL query string
     *
     * @return mixed  + a PHP result resrouce for successful SELECT queries
     *                + the DB_OK constant for other successful queries
     *                + a DB_Error object on failure
     */
    function simpleQuery($query)
    {
        $this->last_query = $query;
        $query = $this->modifyQuery($query);
        $result = @msql_query($query, $this->connection);
        if (!$result) {
            return $this->msqlRaiseError();
        }
        // Determine which queries that should return data, and which
        // should return an error code only.
        if (DB::isManip($query)) {
            $this->_result = $result;
            return DB_OK;
        } else {
            $this->_result = false;
            return $result;
        }
    }


    // }}}
    // {{{ nextResult()

    /**
     * Move the internal msql result pointer to the next available result
     *
     * @param a valid fbsql result resource
     *
     * @access public
     *
     * @return true if a result is available otherwise return false
     */
    function nextResult($result)
    {
        return false;
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
     * PHP's mSQL extension did weird things with NULL values prior to PHP
     * 4.3.11 and 5.0.4.  Make sure your version of PHP meets or exceeds
     * those versions.
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
        if ($rownum !== null) {
            if (!@msql_data_seek($result, $rownum)) {
                return null;
            }
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $arr = @msql_fetch_array($result, MSQL_ASSOC);
            if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE && $arr) {
                $arr = array_change_key_case($arr, CASE_LOWER);
            }
        } else {
            $arr = @msql_fetch_row($result);
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
    // {{{ freeResult()

    /**
     * Deletes the result set and frees the memory occupied by the result set
     *
     * This method is not meant to be called directly.  Use
     * DB_result::free() instead.  It can't be declared "protected"
     * because DB_result is a separate object.
     *
     * @param resource $result  PHP's query result resource
     *
     * @return bool  TRUE on success, FALSE if $result is invalid
     *
     * @see DB_result::free()
     */
    function freeResult($result)
    {
        return @msql_free_result($result);
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
    function numCols($result)
    {
        $cols = @msql_num_fields($result);
        if (!$cols) {
            return $this->msqlRaiseError();
        }
        return $cols;
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
    function numRows($result)
    {
        $rows = @msql_num_rows($result);
        if ($rows === false) {
            return $this->msqlRaiseError();
        }
        return $rows;
    }

    // }}}
    // {{{ affected()

    /**
     * Determines the number of rows affected by a data maniuplation query
     *
     * 0 is returned for queries that don't manipulate data.
     *
     * @return int  the number of rows.  A DB_Error object on failure.
     */
    function affectedRows()
    {
        if (!$this->_result) {
            return 0;
        }
        return msql_affected_rows($this->_result);
    }

    // }}}
    // {{{ nextId()

    /**
     * Returns the next free id in a sequence
     *
     * @param string  $seq_name  name of the sequence
     * @param boolean $ondemand  when true, the seqence is automatically
     *                            created if it does not exist
     *
     * @return int  the next id number in the sequence.
     *               A DB_Error object on failure.
     *
     * @see DB_common::nextID(), DB_common::getSequenceName(),
     *      DB_msql::createSequence(), DB_msql::dropSequence()
     */
    function nextId($seq_name, $ondemand = true)
    {
        $seqname = $this->getSequenceName($seq_name);
        $repeat = false;
        do {
            $this->pushErrorHandling(PEAR_ERROR_RETURN);
            $result =& $this->query("SELECT _seq FROM ${seqname}");
            $this->popErrorHandling();
            if ($ondemand && DB::isError($result) &&
                $result->getCode() == DB_ERROR_NOSUCHTABLE) {
                $repeat = true;
                $this->pushErrorHandling(PEAR_ERROR_RETURN);
                $result = $this->createSequence($seq_name);
                $this->popErrorHandling();
                if (DB::isError($result)) {
                    return $this->raiseError($result);
                }
            } else {
                $repeat = false;
            }
        } while ($repeat);
        if (DB::isError($result)) {
            return $this->raiseError($result);
        }
        $arr = $result->fetchRow(DB_FETCHMODE_ORDERED);
        $result->free();
        return $arr[0];
    }

    // }}}
    // {{{ createSequence()

    /**
     * Creates a new sequence
     *
     * Also creates a new table to associate the sequence with.  Uses
     * a separate table to ensure portability with other drivers.
     *
     * @param string $seq_name  name of the new sequence
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     *
     * @see DB_common::createSequence(), DB_common::getSequenceName(),
     *      DB_msql::nextID(), DB_msql::dropSequence()
     */
    function createSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        $res = $this->query('CREATE TABLE ' . $seqname
                            . ' (id INTEGER NOT NULL)');
        if (DB::isError($res)) {
            return $res;
        }
        $res = $this->query("CREATE SEQUENCE ON ${seqname}");
        return $res;
    }

    // }}}
    // {{{ dropSequence()

    /**
     * Deletes a sequence
     *
     * @param string $seq_name  name of the sequence to be deleted
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     *
     * @see DB_common::dropSequence(), DB_common::getSequenceName(),
     *      DB_msql::nextID(), DB_msql::createSequence()
     */
    function dropSequence($seq_name)
    {
        return $this->query('DROP TABLE ' . $this->getSequenceName($seq_name));
    }

    // }}}
    // {{{ quoteIdentifier()

    /**
     * mSQL does not support delimited identifiers
     *
     * @param string $str  the identifier name to be quoted
     *
     * @return object  a DB_Error object
     *
     * @see DB_common::quoteIdentifier()
     * @since Method available since Release 1.7.0
     */
    function quoteIdentifier($str)
    {
        return $this->raiseError(DB_ERROR_UNSUPPORTED);
    }

    // }}}
    // {{{ escapeSimple()

    /**
     * Escapes a string according to the current DBMS's standards
     *
     * @param string $str  the string to be escaped
     *
     * @return string  the escaped string
     *
     * @see DB_common::quoteSmart()
     * @since Method available since Release 1.7.0
     */
    function escapeSimple($str)
    {
        return addslashes($str);
    }

    // }}}
    // {{{ msqlRaiseError()

    /**
     * Produces a DB_Error object regarding the current problem
     *
     * @param int $errno  if the error is being manually raised pass a
     *                     DB_ERROR* constant here.  If this isn't passed
     *                     the error information gathered from the DBMS.
     *
     * @return object  the DB_Error object
     *
     * @see DB_common::raiseError(),
     *      DB_msql::errorNative(), DB_msql::errorCode()
     */
    function msqlRaiseError($errno = null)
    {
        $native = $this->errorNative();
        if ($errno === null) {
            $errno = $this->errorCode($native);
        }
        return $this->raiseError($errno, null, null, null, $native);
    }

    // }}}
    // {{{ errorNative()

    /**
     * Gets the DBMS' native error message produced by the last query
     *
     * @return string  the DBMS' error message
     */
    function errorNative()
    {
        return @msql_error();
    }

    // }}}
    // {{{ errorCode()

    /**
     * Determines PEAR::DB error code from the database's text error message
     *
     * @param string $errormsg  the error message returned from the database
     *
     * @return integer  the error number from a DB_ERROR* constant
     */
    function errorCode($errormsg)
    {
        static $error_regexps;
        if (!isset($error_regexps)) {
            $error_regexps = array(
                '/^Access to database denied/i'
                    => DB_ERROR_ACCESS_VIOLATION,
                '/^Bad index name/i'
                    => DB_ERROR_ALREADY_EXISTS,
                '/^Bad order field/i'
                    => DB_ERROR_SYNTAX,
                '/^Bad type for comparison/i'
                    => DB_ERROR_SYNTAX,
                '/^Can\'t perform LIKE on/i'
                    => DB_ERROR_SYNTAX,
                '/^Can\'t use TEXT fields in LIKE comparison/i'
                    => DB_ERROR_SYNTAX,
                '/^Couldn\'t create temporary table/i'
                    => DB_ERROR_CANNOT_CREATE,
                '/^Error creating table file/i'
                    => DB_ERROR_CANNOT_CREATE,
                '/^Field .* cannot be null$/i'
                    => DB_ERROR_CONSTRAINT_NOT_NULL,
                '/^Index (field|condition) .* cannot be null$/i'
                    => DB_ERROR_SYNTAX,
                '/^Invalid date format/i'
                    => DB_ERROR_INVALID_DATE,
                '/^Invalid time format/i'
                    => DB_ERROR_INVALID,
                '/^Literal value for .* is wrong type$/i'
                    => DB_ERROR_INVALID_NUMBER,
                '/^No Database Selected/i'
                    => DB_ERROR_NODBSELECTED,
                '/^No value specified for field/i'
                    => DB_ERROR_VALUE_COUNT_ON_ROW,
                '/^Non unique value for unique index/i'
                    => DB_ERROR_CONSTRAINT,
                '/^Out of memory for temporary table/i'
                    => DB_ERROR_CANNOT_CREATE,
                '/^Permission denied/i'
                    => DB_ERROR_ACCESS_VIOLATION,
                '/^Reference to un-selected table/i'
                    => DB_ERROR_SYNTAX,
                '/^syntax error/i'
                    => DB_ERROR_SYNTAX,
                '/^Table .* exists$/i'
                    => DB_ERROR_ALREADY_EXISTS,
                '/^Unknown database/i'
                    => DB_ERROR_NOSUCHDB,
                '/^Unknown field/i'
                    => DB_ERROR_NOSUCHFIELD,
                '/^Unknown (index|system variable)/i'
                    => DB_ERROR_NOT_FOUND,
                '/^Unknown table/i'
                    => DB_ERROR_NOSUCHTABLE,
                '/^Unqualified field/i'
                    => DB_ERROR_SYNTAX,
            );
        }

        foreach ($error_regexps as $regexp => $code) {
            if (preg_match($regexp, $errormsg)) {
                return $code;
            }
        }
        return DB_ERROR;
    }

    // }}}
    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set
     *
     * @param object|string  $result  DB_result object from a query or a
     *                                 string containing the name of a table.
     *                                 While this also accepts a query result
     *                                 resource identifier, this behavior is
     *                                 deprecated.
     * @param int            $mode    a valid tableInfo mode
     *
     * @return array  an associative array with the information requested.
     *                 A DB_Error object on failure.
     *
     * @see DB_common::setOption()
     */
    function tableInfo($result, $mode = null)
    {
        if (is_string($result)) {
            /*
             * Probably received a table name.
             * Create a result resource identifier.
             */
            $id = @msql_query("SELECT * FROM $result",
                              $this->connection);
            $got_string = true;
        } elseif (isset($result->result)) {
            /*
             * Probably received a result object.
             * Extract the result resource identifier.
             */
            $id = $result->result;
            $got_string = false;
        } else {
            /*
             * Probably received a result resource identifier.
             * Copy it.
             * Deprecated.  Here for compatibility only.
             */
            $id = $result;
            $got_string = false;
        }

        if (!is_resource($id)) {
            return $this->raiseError(DB_ERROR_NEED_MORE_DATA);
        }

        if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE) {
            $case_func = 'strtolower';
        } else {
            $case_func = 'strval';
        }

        $count = @msql_num_fields($id);
        $res   = array();

        if ($mode) {
            $res['num_fields'] = $count;
        }

        for ($i = 0; $i < $count; $i++) {
            $tmp = @msql_fetch_field($id);

            $flags = '';
            if ($tmp->not_null) {
                $flags .= 'not_null ';
            }
            if ($tmp->unique) {
                $flags .= 'unique_key ';
            }
            $flags = trim($flags);

            $res[$i] = array(
                'table' => $case_func($tmp->table),
                'name'  => $case_func($tmp->name),
                'type'  => $tmp->type,
                'len'   => msql_field_len($id, $i),
                'flags' => $flags,
            );

            if ($mode & DB_TABLEINFO_ORDER) {
                $res['order'][$res[$i]['name']] = $i;
            }
            if ($mode & DB_TABLEINFO_ORDERTABLE) {
                $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
            }
        }

        // free the result only if we were called on a table
        if ($got_string) {
            @msql_free_result($id);
        }
        return $res;
    }

    // }}}
    // {{{ getSpecialQuery()

    /**
     * Obtain a list of a given type of objects
     *
     * @param string $type  the kind of objects you want to retrieve
     *
     * @return array  the array containing the list of objects requested
     *
     * @access protected
     * @see DB_common::getListOf()
     */
    function getSpecialQuery($type)
    {
        switch ($type) {
            case 'databases':
                $id = @msql_list_dbs($this->connection);
                break;
            case 'tables':
                $id = @msql_list_tables($this->dsn['database'],
                                        $this->connection);
                break;
            default:
                return null;
        }
        if (!$id) {
            return $this->msqlRaiseError();
        }
        $out = array();
        while ($row = @msql_fetch_row($id)) {
            $out[] = $row[0];
        }
        return $out;
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
