<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * The PEAR DB driver for PHP's fbsql extension
 * for interacting with FrontBase databases
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
 * @author     Frank M. Kromann <frank@frontbase.com>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: fbsql.php,v 1.82 2005/03/04 23:12:36 danielc Exp $
 * @link       http://pear.php.net/package/DB
 */

/**
 * Obtain the DB_common class so it can be extended from
 */
require_once 'DB/common.php';

/**
 * The methods PEAR DB uses to interact with PHP's fbsql extension
 * for interacting with FrontBase databases
 *
 * These methods overload the ones declared in DB_common.
 *
 * @category   Database
 * @package    DB
 * @author     Frank M. Kromann <frank@frontbase.com>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/DB
 * @since      Class functional since Release 1.7.0
 */
class DB_fbsql extends DB_common
{
    // {{{ properties

    /**
     * The DB driver type (mysql, oci8, odbc, etc.)
     * @var string
     */
    var $phptype = 'fbsql';

    /**
     * The database syntax variant to be used (db2, access, etc.), if any
     * @var string
     */
    var $dbsyntax = 'fbsql';

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
        'limit'         => 'alter',
        'new_link'      => false,
        'numrows'       => true,
        'pconnect'      => true,
        'prepare'       => false,
        'ssl'           => false,
        'transactions'  => true,
    );

    /**
     * A mapping of native error codes to DB error codes
     * @var array
     */
    var $errorcode_map = array(
         22 => DB_ERROR_SYNTAX,
         85 => DB_ERROR_ALREADY_EXISTS,
        108 => DB_ERROR_SYNTAX,
        116 => DB_ERROR_NOSUCHTABLE,
        124 => DB_ERROR_VALUE_COUNT_ON_ROW,
        215 => DB_ERROR_NOSUCHFIELD,
        217 => DB_ERROR_INVALID_NUMBER,
        226 => DB_ERROR_NOSUCHFIELD,
        231 => DB_ERROR_INVALID,
        239 => DB_ERROR_TRUNCATED,
        251 => DB_ERROR_SYNTAX,
        266 => DB_ERROR_NOT_FOUND,
        357 => DB_ERROR_CONSTRAINT_NOT_NULL,
        358 => DB_ERROR_CONSTRAINT,
        360 => DB_ERROR_CONSTRAINT,
        361 => DB_ERROR_CONSTRAINT,
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


    // }}}
    // {{{ constructor

    /**
     * This constructor calls <kbd>$this->DB_common()</kbd>
     *
     * @return void
     */
    function DB_fbsql()
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
     * @param array $dsn         the data source name
     * @param bool  $persistent  should the connection be persistent?
     *
     * @return int  DB_OK on success. A DB_Error object on failure.
     */
    function connect($dsn, $persistent = false)
    {
        if (!PEAR::loadExtension('fbsql')) {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }

        $this->dsn = $dsn;
        if ($dsn['dbsyntax']) {
            $this->dbsyntax = $dsn['dbsyntax'];
        }

        $params = array(
            $dsn['hostspec'] ? $dsn['hostspec'] : 'localhost',
            $dsn['username'] ? $dsn['username'] : null,
            $dsn['password'] ? $dsn['password'] : null,
        );

        $connect_function = $persistent ? 'fbsql_pconnect' : 'fbsql_connect';

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
            return $this->raiseError(DB_ERROR_CONNECT_FAILED,
                                     null, null, null,
                                     $php_errormsg);
        }

        if ($dsn['database']) {
            if (!@fbsql_select_db($dsn['database'], $this->connection)) {
                return $this->fbsqlRaiseError();
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
        $ret = @fbsql_close($this->connection);
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
        $result = @fbsql_query("$query;", $this->connection);
        if (!$result) {
            return $this->fbsqlRaiseError();
        }
        // Determine which queries that should return data, and which
        // should return an error code only.
        if (DB::isManip($query)) {
            return DB_OK;
        }
        return $result;
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal fbsql result pointer to the next available result
     *
     * @param a valid fbsql result resource
     *
     * @access public
     *
     * @return true if a result is available otherwise return false
     */
    function nextResult($result)
    {
        return @fbsql_next_result($result);
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
        if ($rownum !== null) {
            if (!@fbsql_data_seek($result, $rownum)) {
                return null;
            }
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $arr = @fbsql_fetch_array($result, FBSQL_ASSOC);
            if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE && $arr) {
                $arr = array_change_key_case($arr, CASE_LOWER);
            }
        } else {
            $arr = @fbsql_fetch_row($result);
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
        return @fbsql_free_result($result);
    }

    // }}}
    // {{{ autoCommit()

    /**
     * Enables or disables automatic commits
     *
     * @param bool $onoff  true turns it on, false turns it off
     *
     * @return int  DB_OK on success.  A DB_Error object if the driver
     *               doesn't support auto-committing transactions.
     */
    function autoCommit($onoff=false)
    {
        if ($onoff) {
            $this->query("SET COMMIT TRUE");
        } else {
            $this->query("SET COMMIT FALSE");
        }
    }

    // }}}
    // {{{ commit()

    /**
     * Commits the current transaction
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     */
    function commit()
    {
        @fbsql_commit();
    }

    // }}}
    // {{{ rollback()

    /**
     * Reverts the current transaction
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     */
    function rollback()
    {
        @fbsql_rollback();
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
        $cols = @fbsql_num_fields($result);
        if (!$cols) {
            return $this->fbsqlRaiseError();
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
        $rows = @fbsql_num_rows($result);
        if ($rows === null) {
            return $this->fbsqlRaiseError();
        }
        return $rows;
    }

    // }}}
    // {{{ affectedRows()

    /**
     * Determines the number of rows affected by a data maniuplation query
     *
     * 0 is returned for queries that don't manipulate data.
     *
     * @return int  the number of rows.  A DB_Error object on failure.
     */
    function affectedRows()
    {
        if (DB::isManip($this->last_query)) {
            $result = @fbsql_affected_rows($this->connection);
        } else {
            $result = 0;
        }
        return $result;
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
     *      DB_fbsql::createSequence(), DB_fbsql::dropSequence()
     */
    function nextId($seq_name, $ondemand = true)
    {
        $seqname = $this->getSequenceName($seq_name);
        do {
            $repeat = 0;
            $this->pushErrorHandling(PEAR_ERROR_RETURN);
            $result = $this->query('SELECT UNIQUE FROM ' . $seqname);
            $this->popErrorHandling();
            if ($ondemand && DB::isError($result) &&
                $result->getCode() == DB_ERROR_NOSUCHTABLE) {
                $repeat = 1;
                $result = $this->createSequence($seq_name);
                if (DB::isError($result)) {
                    return $result;
                }
            } else {
                $repeat = 0;
            }
        } while ($repeat);
        if (DB::isError($result)) {
            return $this->fbsqlRaiseError();
        }
        $result->fetchInto($tmp, DB_FETCHMODE_ORDERED);
        return $tmp[0];
    }

    /**
     * Creates a new sequence
     *
     * @param string $seq_name  name of the new sequence
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     *
     * @see DB_common::createSequence(), DB_common::getSequenceName(),
     *      DB_fbsql::nextID(), DB_fbsql::dropSequence()
     */
    function createSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        $res = $this->query('CREATE TABLE ' . $seqname
                            . ' (id INTEGER NOT NULL,'
                            . ' PRIMARY KEY(id))');
        if ($res) {
            $res = $this->query('SET UNIQUE = 0 FOR ' . $seqname);
        }
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
     *      DB_fbsql::nextID(), DB_fbsql::createSequence()
     */
    function dropSequence($seq_name)
    {
        return $this->query('DROP TABLE ' . $this->getSequenceName($seq_name)
                            . ' RESTRICT');
    }

    // }}}
    // {{{ modifyLimitQuery()

    /**
     * Adds LIMIT clauses to a query string according to current DBMS standards
     *
     * @param string $query   the query to modify
     * @param int    $from    the row to start to fetching (0 = the first row)
     * @param int    $count   the numbers of rows to fetch
     * @param mixed  $params  array, string or numeric data to be used in
     *                         execution of the statement.  Quantity of items
     *                         passed must match quantity of placeholders in
     *                         query:  meaning 1 placeholder for non-array
     *                         parameters or 1 placeholder per array element.
     *
     * @return string  the query string with LIMIT clauses added
     *
     * @access protected
     */
    function modifyLimitQuery($query, $from, $count, $params = array())
    {
        if (DB::isManip($query)) {
            return preg_replace('/^([\s(])*SELECT/i',
                                "\\1SELECT TOP($count)", $query);
        } else {
            return preg_replace('/([\s(])*SELECT/i',
                                "\\1SELECT TOP($from, $count)", $query);
        }
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
     *                 + boolean = string <samp>TRUE</samp> or <samp>FALSE</samp>
     *                 + integer or double = the unquoted number
     *                 + other (including strings and numeric strings) =
     *                   the data escaped according to FrontBase's settings
     *                   then encapsulated between single quotes
     *
     * @see DB_common::quoteSmart()
     * @since Method available since Release 1.6.0
     */
    function quoteSmart($in)
    {
        if (is_int($in) || is_double($in)) {
            return $in;
        } elseif (is_bool($in)) {
            return $in ? 'TRUE' : 'FALSE';
        } elseif (is_null($in)) {
            return 'NULL';
        } else {
            return "'" . $this->escapeSimple($in) . "'";
        }
    }

    // }}}
    // {{{ fbsqlRaiseError()

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
     *      DB_fbsql::errorNative(), DB_common::errorCode()
     */
    function fbsqlRaiseError($errno = null)
    {
        if ($errno === null) {
            $errno = $this->errorCode(fbsql_errno($this->connection));
        }
        return $this->raiseError($errno, null, null, null,
                                 @fbsql_error($this->connection));
    }

    // }}}
    // {{{ errorNative()

    /**
     * Gets the DBMS' native error code produced by the last query
     *
     * @return int  the DBMS' error code
     */
    function errorNative()
    {
        return @fbsql_errno($this->connection);
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
     * @see DB_common::tableInfo()
     */
    function tableInfo($result, $mode = null)
    {
        if (is_string($result)) {
            /*
             * Probably received a table name.
             * Create a result resource identifier.
             */
            $id = @fbsql_list_fields($this->dsn['database'],
                                     $result, $this->connection);
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
            return $this->fbsqlRaiseError(DB_ERROR_NEED_MORE_DATA);
        }

        if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE) {
            $case_func = 'strtolower';
        } else {
            $case_func = 'strval';
        }

        $count = @fbsql_num_fields($id);
        $res   = array();

        if ($mode) {
            $res['num_fields'] = $count;
        }

        for ($i = 0; $i < $count; $i++) {
            $res[$i] = array(
                'table' => $case_func(@fbsql_field_table($id, $i)),
                'name'  => $case_func(@fbsql_field_name($id, $i)),
                'type'  => @fbsql_field_type($id, $i),
                'len'   => @fbsql_field_len($id, $i),
                'flags' => @fbsql_field_flags($id, $i),
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
            @fbsql_free_result($id);
        }
        return $res;
    }

    // }}}
    // {{{ getSpecialQuery()

    /**
     * Obtains the query string needed for listing a given type of objects
     *
     * @param string $type  the kind of objects you want to retrieve
     *
     * @return string  the SQL query string or null if the driver doesn't
     *                  support the object type requested
     *
     * @access protected
     * @see DB_common::getListOf()
     */
    function getSpecialQuery($type)
    {
        switch ($type) {
            case 'tables':
                return 'SELECT "table_name" FROM information_schema.tables'
                       . ' t0, information_schema.schemata t1'
                       . ' WHERE t0.schema_pk=t1.schema_pk AND'
                       . ' "table_type" = \'BASE TABLE\''
                       . ' AND "schema_name" = current_schema';
            case 'views':
                return 'SELECT "table_name" FROM information_schema.tables'
                       . ' t0, information_schema.schemata t1'
                       . ' WHERE t0.schema_pk=t1.schema_pk AND'
                       . ' "table_type" = \'VIEW\''
                       . ' AND "schema_name" = current_schema';
            case 'users':
                return 'SELECT "user_name" from information_schema.users'; 
            case 'functions':
                return 'SELECT "routine_name" FROM'
                       . ' information_schema.psm_routines'
                       . ' t0, information_schema.schemata t1'
                       . ' WHERE t0.schema_pk=t1.schema_pk'
                       . ' AND "routine_kind"=\'FUNCTION\''
                       . ' AND "schema_name" = current_schema';
            case 'procedures':
                return 'SELECT "routine_name" FROM'
                       . ' information_schema.psm_routines'
                       . ' t0, information_schema.schemata t1'
                       . ' WHERE t0.schema_pk=t1.schema_pk'
                       . ' AND "routine_kind"=\'PROCEDURE\''
                       . ' AND "schema_name" = current_schema';
            default:
                return null;
        }
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
