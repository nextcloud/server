<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * The PEAR DB driver for PHP's oci8 extension
 * for interacting with Oracle databases
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
 * @author     James L. Pine <jlp@valinux.com>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: oci8.php,v 1.103 2005/04/11 15:10:22 danielc Exp $
 * @link       http://pear.php.net/package/DB
 */

/**
 * Obtain the DB_common class so it can be extended from
 */
require_once 'DB/common.php';

/**
 * The methods PEAR DB uses to interact with PHP's oci8 extension
 * for interacting with Oracle databases
 *
 * Definitely works with versions 8 and 9 of Oracle.
 *
 * These methods overload the ones declared in DB_common.
 *
 * Be aware...  OCIError() only appears to return anything when given a
 * statement, so functions return the generic DB_ERROR instead of more
 * useful errors that have to do with feedback from the database.
 *
 * @category   Database
 * @package    DB
 * @author     James L. Pine <jlp@valinux.com>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/DB
 */
class DB_oci8 extends DB_common
{
    // {{{ properties

    /**
     * The DB driver type (mysql, oci8, odbc, etc.)
     * @var string
     */
    var $phptype = 'oci8';

    /**
     * The database syntax variant to be used (db2, access, etc.), if any
     * @var string
     */
    var $dbsyntax = 'oci8';

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
        'new_link'      => '5.0.0',
        'numrows'       => 'subquery',
        'pconnect'      => true,
        'prepare'       => true,
        'ssl'           => false,
        'transactions'  => true,
    );

    /**
     * A mapping of native error codes to DB error codes
     * @var array
     */
    var $errorcode_map = array(
        1    => DB_ERROR_CONSTRAINT,
        900  => DB_ERROR_SYNTAX,
        904  => DB_ERROR_NOSUCHFIELD,
        913  => DB_ERROR_VALUE_COUNT_ON_ROW,
        921  => DB_ERROR_SYNTAX,
        923  => DB_ERROR_SYNTAX,
        942  => DB_ERROR_NOSUCHTABLE,
        955  => DB_ERROR_ALREADY_EXISTS,
        1400 => DB_ERROR_CONSTRAINT_NOT_NULL,
        1401 => DB_ERROR_INVALID,
        1407 => DB_ERROR_CONSTRAINT_NOT_NULL,
        1418 => DB_ERROR_NOT_FOUND,
        1476 => DB_ERROR_DIVZERO,
        1722 => DB_ERROR_INVALID_NUMBER,
        2289 => DB_ERROR_NOSUCHTABLE,
        2291 => DB_ERROR_CONSTRAINT,
        2292 => DB_ERROR_CONSTRAINT,
        2449 => DB_ERROR_CONSTRAINT,
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
     * Should data manipulation queries be committed automatically?
     * @var bool
     * @access private
     */
    var $autocommit = true;

    /**
     * Stores the $data passed to execute() in the oci8 driver
     *
     * Gets reset to array() when simpleQuery() is run.
     *
     * Needed in case user wants to call numRows() after prepare/execute
     * was used.
     *
     * @var array
     * @access private
     */
    var $_data = array();

    /**
     * The result or statement handle from the most recently executed query
     * @var resource
     */
    var $last_stmt;

    /**
     * Is the given prepared statement a data manipulation query?
     * @var array
     * @access private
     */
    var $manip_query = array();


    // }}}
    // {{{ constructor

    /**
     * This constructor calls <kbd>$this->DB_common()</kbd>
     *
     * @return void
     */
    function DB_oci8()
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
     * If PHP is at version 5.0.0 or greater:
     *   + Generally, oci_connect() or oci_pconnect() are used.
     *   + But if the new_link DSN option is set to true, oci_new_connect()
     *     is used.
     *
     * When using PHP version 4.x, OCILogon() or OCIPLogon() are used.
     *
     * PEAR DB's oci8 driver supports the following extra DSN options:
     *   + charset       The character set to be used on the connection.
     *                    Only used if PHP is at version 5.0.0 or greater
     *                    and the Oracle server is at 9.2 or greater.
     *                    Available since PEAR DB 1.7.0.
     *   + new_link      If set to true, causes subsequent calls to
     *                    connect() to return a new connection link
     *                    instead of the existing one.  WARNING: this is
     *                    not portable to other DBMS's.
     *                    Available since PEAR DB 1.7.0.
     *
     * @param array $dsn         the data source name
     * @param bool  $persistent  should the connection be persistent?
     *
     * @return int  DB_OK on success. A DB_Error object on failure.
     */
    function connect($dsn, $persistent = false)
    {
        if (!PEAR::loadExtension('oci8')) {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }

        $this->dsn = $dsn;
        if ($dsn['dbsyntax']) {
            $this->dbsyntax = $dsn['dbsyntax'];
        }

        if (function_exists('oci_connect')) {
            if (isset($dsn['new_link'])
                && ($dsn['new_link'] == 'true' || $dsn['new_link'] === true))
            {
                $connect_function = 'oci_new_connect';
            } else {
                $connect_function = $persistent ? 'oci_pconnect'
                                    : 'oci_connect';
            }

            // Backwards compatibility with DB < 1.7.0
            if (empty($dsn['database']) && !empty($dsn['hostspec'])) {
                $db = $dsn['hostspec'];
            } else {
                $db = $dsn['database'];
            }

            $char = empty($dsn['charset']) ? null : $dsn['charset'];
            $this->connection = @$connect_function($dsn['username'],
                                                   $dsn['password'],
                                                   $db,
                                                   $char);
            $error = OCIError();
            if (!empty($error) && $error['code'] == 12541) {
                // Couldn't find TNS listener.  Try direct connection.
                $this->connection = @$connect_function($dsn['username'],
                                                       $dsn['password'],
                                                       null,
                                                       $char);
            }
        } else {
            $connect_function = $persistent ? 'OCIPLogon' : 'OCILogon';
            if ($dsn['hostspec']) {
                $this->connection = @$connect_function($dsn['username'],
                                                       $dsn['password'],
                                                       $dsn['hostspec']);
            } elseif ($dsn['username'] || $dsn['password']) {
                $this->connection = @$connect_function($dsn['username'],
                                                       $dsn['password']);
            }
        }

        if (!$this->connection) {
            $error = OCIError();
            $error = (is_array($error)) ? $error['message'] : null;
            return $this->raiseError(DB_ERROR_CONNECT_FAILED,
                                     null, null, null,
                                     $error);
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
        if (function_exists('oci_close')) {
            $ret = @oci_close($this->connection);
        } else {
            $ret = @OCILogOff($this->connection);
        }
        $this->connection = null;
        return $ret;
    }

    // }}}
    // {{{ simpleQuery()

    /**
     * Sends a query to the database server
     *
     * To determine how many rows of a result set get buffered using
     * ocisetprefetch(), see the "result_buffering" option in setOptions().
     * This option was added in Release 1.7.0.
     *
     * @param string  the SQL query string
     *
     * @return mixed  + a PHP result resrouce for successful SELECT queries
     *                + the DB_OK constant for other successful queries
     *                + a DB_Error object on failure
     */
    function simpleQuery($query)
    {
        $this->_data = array();
        $this->last_parameters = array();
        $this->last_query = $query;
        $query = $this->modifyQuery($query);
        $result = @OCIParse($this->connection, $query);
        if (!$result) {
            return $this->oci8RaiseError();
        }
        if ($this->autocommit) {
            $success = @OCIExecute($result,OCI_COMMIT_ON_SUCCESS);
        } else {
            $success = @OCIExecute($result,OCI_DEFAULT);
        }
        if (!$success) {
            return $this->oci8RaiseError($result);
        }
        $this->last_stmt = $result;
        if (DB::isManip($query)) {
            return DB_OK;
        } else {
            @ocisetprefetch($result, $this->options['result_buffering']);
            return $result;
        }
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal oracle result pointer to the next available result
     *
     * @param a valid oci8 result resource
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
            return $this->raiseError(DB_ERROR_NOT_CAPABLE);
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $moredata = @OCIFetchInto($result,$arr,OCI_ASSOC+OCI_RETURN_NULLS+OCI_RETURN_LOBS);
            if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE &&
                $moredata)
            {
                $arr = array_change_key_case($arr, CASE_LOWER);
            }
        } else {
            $moredata = OCIFetchInto($result,$arr,OCI_RETURN_NULLS+OCI_RETURN_LOBS);
        }
        if (!$moredata) {
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
        return @OCIFreeStatement($result);
    }

    /**
     * Frees the internal resources associated with a prepared query
     *
     * @param resource $stmt           the prepared statement's resource
     * @param bool     $free_resource  should the PHP resource be freed too?
     *                                  Use false if you need to get data
     *                                  from the result set later.
     *
     * @return bool  TRUE on success, FALSE if $result is invalid
     *
     * @see DB_oci8::prepare()
     */
    function freePrepared($stmt, $free_resource = true)
    {
        if (!is_resource($stmt)) {
            return false;
        }
        if ($free_resource) {
            @ocifreestatement($stmt);
        }
        if (isset($this->prepare_types[(int)$stmt])) {
            unset($this->prepare_types[(int)$stmt]);
            unset($this->manip_query[(int)$stmt]);
        } else {
            return false;
        }
        return true;
    }

    // }}}
    // {{{ numRows()

    /**
     * Gets the number of rows in a result set
     *
     * Only works if the DB_PORTABILITY_NUMROWS portability option
     * is turned on.
     *
     * This method is not meant to be called directly.  Use
     * DB_result::numRows() instead.  It can't be declared "protected"
     * because DB_result is a separate object.
     *
     * @param resource $result  PHP's query result resource
     *
     * @return int  the number of rows.  A DB_Error object on failure.
     *
     * @see DB_result::numRows(), DB_common::setOption()
     */
    function numRows($result)
    {
        // emulate numRows for Oracle.  yuck.
        if ($this->options['portability'] & DB_PORTABILITY_NUMROWS &&
            $result === $this->last_stmt)
        {
            $countquery = 'SELECT COUNT(*) FROM ('.$this->last_query.')';
            $save_query = $this->last_query;
            $save_stmt = $this->last_stmt;

            if (count($this->_data)) {
                $smt = $this->prepare('SELECT COUNT(*) FROM ('.$this->last_query.')');
                $count = $this->execute($smt, $this->_data);
            } else {
                $count =& $this->query($countquery);
            }

            if (DB::isError($count) ||
                DB::isError($row = $count->fetchRow(DB_FETCHMODE_ORDERED)))
            {
                $this->last_query = $save_query;
                $this->last_stmt = $save_stmt;
                return $this->raiseError(DB_ERROR_NOT_CAPABLE);
            }
            return $row[0];
        }
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
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
        $cols = @OCINumCols($result);
        if (!$cols) {
            return $this->oci8RaiseError($result);
        }
        return $cols;
    }

    // }}}
    // {{{ prepare()

    /**
     * Prepares a query for multiple execution with execute().
     *
     * With oci8, this is emulated.
     *
     * prepare() requires a generic query as string like <code>
     *    INSERT INTO numbers VALUES (?, ?, ?)
     * </code>.  The <kbd>?</kbd> characters are placeholders.
     *
     * Three types of placeholders can be used:
     *   + <kbd>?</kbd>  a quoted scalar value, i.e. strings, integers
     *   + <kbd>!</kbd>  value is inserted 'as is'
     *   + <kbd>&</kbd>  requires a file name.  The file's contents get
     *                     inserted into the query (i.e. saving binary
     *                     data in a db)
     *
     * Use backslashes to escape placeholder characters if you don't want
     * them to be interpreted as placeholders.  Example: <code>
     *    "UPDATE foo SET col=? WHERE col='over \& under'"
     * </code>
     *
     * @param string $query  the query to be prepared
     *
     * @return mixed  DB statement resource on success. DB_Error on failure.
     *
     * @see DB_oci8::execute()
     */
    function prepare($query)
    {
        $tokens   = preg_split('/((?<!\\\)[&?!])/', $query, -1,
                               PREG_SPLIT_DELIM_CAPTURE);
        $binds    = count($tokens) - 1;
        $token    = 0;
        $types    = array();
        $newquery = '';

        foreach ($tokens as $key => $val) {
            switch ($val) {
                case '?':
                    $types[$token++] = DB_PARAM_SCALAR;
                    unset($tokens[$key]);
                    break;
                case '&':
                    $types[$token++] = DB_PARAM_OPAQUE;
                    unset($tokens[$key]);
                    break;
                case '!':
                    $types[$token++] = DB_PARAM_MISC;
                    unset($tokens[$key]);
                    break;
                default:
                    $tokens[$key] = preg_replace('/\\\([&?!])/', "\\1", $val);
                    if ($key != $binds) {
                        $newquery .= $tokens[$key] . ':bind' . $token;
                    } else {
                        $newquery .= $tokens[$key];
                    }
            }
        }

        $this->last_query = $query;
        $newquery = $this->modifyQuery($newquery);
        if (!$stmt = @OCIParse($this->connection, $newquery)) {
            return $this->oci8RaiseError();
        }
        $this->prepare_types[(int)$stmt] = $types;
        $this->manip_query[(int)$stmt] = DB::isManip($query);
        return $stmt;
    }

    // }}}
    // {{{ execute()

    /**
     * Executes a DB statement prepared with prepare().
     *
     * To determine how many rows of a result set get buffered using
     * ocisetprefetch(), see the "result_buffering" option in setOptions().
     * This option was added in Release 1.7.0.
     *
     * @param resource  $stmt  a DB statement resource returned from prepare()
     * @param mixed  $data  array, string or numeric data to be used in
     *                      execution of the statement.  Quantity of items
     *                      passed must match quantity of placeholders in
     *                      query:  meaning 1 for non-array items or the
     *                      quantity of elements in the array.
     *
     * @return mixed  returns an oic8 result resource for successful SELECT
     *                queries, DB_OK for other successful queries.
     *                A DB error object is returned on failure.
     *
     * @see DB_oci8::prepare()
     */
    function &execute($stmt, $data = array())
    {
        $data = (array)$data;
        $this->last_parameters = $data;
        $this->_data = $data;

        $types =& $this->prepare_types[(int)$stmt];
        if (count($types) != count($data)) {
            $tmp =& $this->raiseError(DB_ERROR_MISMATCH);
            return $tmp;
        }

        $i = 0;
        foreach ($data as $key => $value) {
            if ($types[$i] == DB_PARAM_MISC) {
                /*
                 * Oracle doesn't seem to have the ability to pass a
                 * parameter along unchanged, so strip off quotes from start
                 * and end, plus turn two single quotes to one single quote,
                 * in order to avoid the quotes getting escaped by
                 * Oracle and ending up in the database.
                 */
                $data[$key] = preg_replace("/^'(.*)'$/", "\\1", $data[$key]);
                $data[$key] = str_replace("''", "'", $data[$key]);
            } elseif ($types[$i] == DB_PARAM_OPAQUE) {
                $fp = @fopen($data[$key], 'rb');
                if (!$fp) {
                    $tmp =& $this->raiseError(DB_ERROR_ACCESS_VIOLATION);
                    return $tmp;
                }
                $data[$key] = fread($fp, filesize($data[$key]));
                fclose($fp);
            }
            if (!@OCIBindByName($stmt, ':bind' . $i, $data[$key], -1)) {
                $tmp = $this->oci8RaiseError($stmt);
                return $tmp;
            }
            $i++;
        }
        if ($this->autocommit) {
            $success = @OCIExecute($stmt, OCI_COMMIT_ON_SUCCESS);
        } else {
            $success = @OCIExecute($stmt, OCI_DEFAULT);
        }
        if (!$success) {
            $tmp = $this->oci8RaiseError($stmt);
            return $tmp;
        }
        $this->last_stmt = $stmt;
        if ($this->manip_query[(int)$stmt]) {
            $tmp = DB_OK;
        } else {
            @ocisetprefetch($stmt, $this->options['result_buffering']);
            $tmp =& new DB_result($this, $stmt);
        }
        return $tmp;
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
    function autoCommit($onoff = false)
    {
        $this->autocommit = (bool)$onoff;;
        return DB_OK;
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
        $result = @OCICommit($this->connection);
        if (!$result) {
            return $this->oci8RaiseError();
        }
        return DB_OK;
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
        $result = @OCIRollback($this->connection);
        if (!$result) {
            return $this->oci8RaiseError();
        }
        return DB_OK;
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
        if ($this->last_stmt === false) {
            return $this->oci8RaiseError();
        }
        $result = @OCIRowCount($this->last_stmt);
        if ($result === false) {
            return $this->oci8RaiseError($this->last_stmt);
        }
        return $result;
    }

    // }}}
    // {{{ modifyQuery()

    /**
     * Changes a query string for various DBMS specific reasons
     *
     * "SELECT 2+2" must be "SELECT 2+2 FROM dual" in Oracle.
     *
     * @param string $query  the query string to modify
     *
     * @return string  the modified query string
     *
     * @access protected
     */
    function modifyQuery($query)
    {
        if (preg_match('/^\s*SELECT/i', $query) &&
            !preg_match('/\sFROM\s/i', $query)) {
            $query .= ' FROM dual';
        }
        return $query;
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
        // Let Oracle return the name of the columns instead of
        // coding a "home" SQL parser

        if (count($params)) {
            $result = $this->prepare("SELECT * FROM ($query) "
                                     . 'WHERE NULL = NULL');
            $tmp =& $this->execute($result, $params);
        } else {
            $q_fields = "SELECT * FROM ($query) WHERE NULL = NULL";

            if (!$result = @OCIParse($this->connection, $q_fields)) {
                $this->last_query = $q_fields;
                return $this->oci8RaiseError();
            }
            if (!@OCIExecute($result, OCI_DEFAULT)) {
                $this->last_query = $q_fields;
                return $this->oci8RaiseError($result);
            }
        }

        $ncols = OCINumCols($result);
        $cols  = array();
        for ( $i = 1; $i <= $ncols; $i++ ) {
            $cols[] = '"' . OCIColumnName($result, $i) . '"';
        }
        $fields = implode(', ', $cols);
        // XXX Test that (tip by John Lim)
        //if (preg_match('/^\s*SELECT\s+/is', $query, $match)) {
        //    // Introduce the FIRST_ROWS Oracle query optimizer
        //    $query = substr($query, strlen($match[0]), strlen($query));
        //    $query = "SELECT /* +FIRST_ROWS */ " . $query;
        //}

        // Construct the query
        // more at: http://marc.theaimsgroup.com/?l=php-db&m=99831958101212&w=2
        // Perhaps this could be optimized with the use of Unions
        $query = "SELECT $fields FROM".
                 "  (SELECT rownum as linenum, $fields FROM".
                 "      ($query)".
                 '  WHERE rownum <= '. ($from + $count) .
                 ') WHERE linenum >= ' . ++$from;
        return $query;
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
     *      DB_oci8::createSequence(), DB_oci8::dropSequence()
     */
    function nextId($seq_name, $ondemand = true)
    {
        $seqname = $this->getSequenceName($seq_name);
        $repeat = 0;
        do {
            $this->expectError(DB_ERROR_NOSUCHTABLE);
            $result =& $this->query("SELECT ${seqname}.nextval FROM dual");
            $this->popExpect();
            if ($ondemand && DB::isError($result) &&
                $result->getCode() == DB_ERROR_NOSUCHTABLE) {
                $repeat = 1;
                $result = $this->createSequence($seq_name);
                if (DB::isError($result)) {
                    return $this->raiseError($result);
                }
            } else {
                $repeat = 0;
            }
        } while ($repeat);
        if (DB::isError($result)) {
            return $this->raiseError($result);
        }
        $arr = $result->fetchRow(DB_FETCHMODE_ORDERED);
        return $arr[0];
    }

    /**
     * Creates a new sequence
     *
     * @param string $seq_name  name of the new sequence
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     *
     * @see DB_common::createSequence(), DB_common::getSequenceName(),
     *      DB_oci8::nextID(), DB_oci8::dropSequence()
     */
    function createSequence($seq_name)
    {
        return $this->query('CREATE SEQUENCE '
                            . $this->getSequenceName($seq_name));
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
     *      DB_oci8::nextID(), DB_oci8::createSequence()
     */
    function dropSequence($seq_name)
    {
        return $this->query('DROP SEQUENCE '
                            . $this->getSequenceName($seq_name));
    }

    // }}}
    // {{{ oci8RaiseError()

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
     *      DB_oci8::errorNative(), DB_oci8::errorCode()
     */
    function oci8RaiseError($errno = null)
    {
        if ($errno === null) {
            $error = @OCIError($this->connection);
            return $this->raiseError($this->errorCode($error['code']),
                                     null, null, null, $error['message']);
        } elseif (is_resource($errno)) {
            $error = @OCIError($errno);
            return $this->raiseError($this->errorCode($error['code']),
                                     null, null, null, $error['message']);
        }
        return $this->raiseError($this->errorCode($errno));
    }

    // }}}
    // {{{ errorNative()

    /**
     * Gets the DBMS' native error code produced by the last query
     *
     * @return int  the DBMS' error code.  FALSE if the code could not be
     *               determined
     */
    function errorNative()
    {
        if (is_resource($this->last_stmt)) {
            $error = @OCIError($this->last_stmt);
        } else {
            $error = @OCIError($this->connection);
        }
        if (is_array($error)) {
            return $error['code'];
        }
        return false;
    }

    // }}}
    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set
     *
     * NOTE: only supports 'table' and 'flags' if <var>$result</var>
     * is a table name.
     *
     * NOTE: flags won't contain index information.
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
        if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE) {
            $case_func = 'strtolower';
        } else {
            $case_func = 'strval';
        }

        $res = array();

        if (is_string($result)) {
            /*
             * Probably received a table name.
             * Create a result resource identifier.
             */
            $result = strtoupper($result);
            $q_fields = 'SELECT column_name, data_type, data_length, '
                        . 'nullable '
                        . 'FROM user_tab_columns '
                        . "WHERE table_name='$result' ORDER BY column_id";

            $this->last_query = $q_fields;

            if (!$stmt = @OCIParse($this->connection, $q_fields)) {
                return $this->oci8RaiseError(DB_ERROR_NEED_MORE_DATA);
            }
            if (!@OCIExecute($stmt, OCI_DEFAULT)) {
                return $this->oci8RaiseError($stmt);
            }

            $i = 0;
            while (@OCIFetch($stmt)) {
                $res[$i] = array(
                    'table' => $case_func($result),
                    'name'  => $case_func(@OCIResult($stmt, 1)),
                    'type'  => @OCIResult($stmt, 2),
                    'len'   => @OCIResult($stmt, 3),
                    'flags' => (@OCIResult($stmt, 4) == 'N') ? 'not_null' : '',
                );
                if ($mode & DB_TABLEINFO_ORDER) {
                    $res['order'][$res[$i]['name']] = $i;
                }
                if ($mode & DB_TABLEINFO_ORDERTABLE) {
                    $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                }
                $i++;
            }

            if ($mode) {
                $res['num_fields'] = $i;
            }
            @OCIFreeStatement($stmt);

        } else {
            if (isset($result->result)) {
                /*
                 * Probably received a result object.
                 * Extract the result resource identifier.
                 */
                $result = $result->result;
            }

            $res = array();

            if ($result === $this->last_stmt) {
                $count = @OCINumCols($result);
                if ($mode) {
                    $res['num_fields'] = $count;
                }
                for ($i = 0; $i < $count; $i++) {
                    $res[$i] = array(
                        'table' => '',
                        'name'  => $case_func(@OCIColumnName($result, $i+1)),
                        'type'  => @OCIColumnType($result, $i+1),
                        'len'   => @OCIColumnSize($result, $i+1),
                        'flags' => '',
                    );
                    if ($mode & DB_TABLEINFO_ORDER) {
                        $res['order'][$res[$i]['name']] = $i;
                    }
                    if ($mode & DB_TABLEINFO_ORDERTABLE) {
                        $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                    }
                }
            } else {
                return $this->raiseError(DB_ERROR_NOT_CAPABLE);
            }
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
                return 'SELECT table_name FROM user_tables';
            case 'synonyms':
                return 'SELECT synonym_name FROM user_synonyms';
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
