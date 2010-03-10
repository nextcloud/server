<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * The PEAR DB driver for PHP's pgsql extension
 * for interacting with PostgreSQL databases
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
 * @author     Rui Hirokawa <hirokawa@php.net>
 * @author     Stig Bakken <ssb@php.net>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: pgsql.php,v 1.126 2005/03/04 23:12:36 danielc Exp $
 * @link       http://pear.php.net/package/DB
 */

/**
 * Obtain the DB_common class so it can be extended from
 */
require_once 'DB/common.php';

/**
 * The methods PEAR DB uses to interact with PHP's pgsql extension
 * for interacting with PostgreSQL databases
 *
 * These methods overload the ones declared in DB_common.
 *
 * @category   Database
 * @package    DB
 * @author     Rui Hirokawa <hirokawa@php.net>
 * @author     Stig Bakken <ssb@php.net>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/DB
 */
class DB_pgsql extends DB_common
{
    // {{{ properties

    /**
     * The DB driver type (mysql, oci8, odbc, etc.)
     * @var string
     */
    var $phptype = 'pgsql';

    /**
     * The database syntax variant to be used (db2, access, etc.), if any
     * @var string
     */
    var $dbsyntax = 'pgsql';

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
        'new_link'      => '4.3.0',
        'numrows'       => true,
        'pconnect'      => true,
        'prepare'       => false,
        'ssl'           => true,
        'transactions'  => true,
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
     * Should data manipulation queries be committed automatically?
     * @var bool
     * @access private
     */
    var $autocommit = true;

    /**
     * The quantity of transactions begun
     *
     * {@internal  While this is private, it can't actually be designated
     * private in PHP 5 because it is directly accessed in the test suite.}}
     *
     * @var integer
     * @access private
     */
    var $transaction_opcount = 0;

    /**
     * The number of rows affected by a data manipulation query
     * @var integer
     */
    var $affected = 0;

    /**
     * The current row being looked at in fetchInto()
     * @var array
     * @access private
     */
    var $row = array();

    /**
     * The number of rows in a given result set
     * @var array
     * @access private
     */
    var $_num_rows = array();


    // }}}
    // {{{ constructor

    /**
     * This constructor calls <kbd>$this->DB_common()</kbd>
     *
     * @return void
     */
    function DB_pgsql()
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
     * PEAR DB's pgsql driver supports the following extra DSN options:
     *   + connect_timeout  How many seconds to wait for a connection to
     *                       be established.  Available since PEAR DB 1.7.0.
     *   + new_link         If set to true, causes subsequent calls to
     *                       connect() to return a new connection link
     *                       instead of the existing one.  WARNING: this is
     *                       not portable to other DBMS's.  Available only
     *                       if PHP is >= 4.3.0 and PEAR DB is >= 1.7.0.
     *   + options          Command line options to be sent to the server.
     *                       Available since PEAR DB 1.6.4.
     *   + service          Specifies a service name in pg_service.conf that
     *                       holds additional connection parameters.
     *                       Available since PEAR DB 1.7.0.
     *   + sslmode          How should SSL be used when connecting?  Values:
     *                       disable, allow, prefer or require.
     *                       Available since PEAR DB 1.7.0.
     *   + tty              This was used to specify where to send server
     *                       debug output.  Available since PEAR DB 1.6.4.
     *
     * Example of connecting to a new link via a socket:
     * <code>
     * require_once 'DB.php';
     * 
     * $dsn = 'pgsql://user:pass@unix(/tmp)/dbname?new_link=true';
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
     *
     * @link http://www.postgresql.org/docs/current/static/libpq.html#LIBPQ-CONNECT
     */
    function connect($dsn, $persistent = false)
    {
        if (!PEAR::loadExtension('pgsql')) {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }

        $this->dsn = $dsn;
        if ($dsn['dbsyntax']) {
            $this->dbsyntax = $dsn['dbsyntax'];
        }

        $protocol = $dsn['protocol'] ? $dsn['protocol'] : 'tcp';

        $params = array('');
        if ($protocol == 'tcp') {
            if ($dsn['hostspec']) {
                $params[0] .= 'host=' . $dsn['hostspec'];
            }
            if ($dsn['port']) {
                $params[0] .= ' port=' . $dsn['port'];
            }
        } elseif ($protocol == 'unix') {
            // Allow for pg socket in non-standard locations.
            if ($dsn['socket']) {
                $params[0] .= 'host=' . $dsn['socket'];
            }
            if ($dsn['port']) {
                $params[0] .= ' port=' . $dsn['port'];
            }
        }
        if ($dsn['database']) {
            $params[0] .= ' dbname=\'' . addslashes($dsn['database']) . '\'';
        }
        if ($dsn['username']) {
            $params[0] .= ' user=\'' . addslashes($dsn['username']) . '\'';
        }
        if ($dsn['password']) {
            $params[0] .= ' password=\'' . addslashes($dsn['password']) . '\'';
        }
        if (!empty($dsn['options'])) {
            $params[0] .= ' options=' . $dsn['options'];
        }
        if (!empty($dsn['tty'])) {
            $params[0] .= ' tty=' . $dsn['tty'];
        }
        if (!empty($dsn['connect_timeout'])) {
            $params[0] .= ' connect_timeout=' . $dsn['connect_timeout'];
        }
        if (!empty($dsn['sslmode'])) {
            $params[0] .= ' sslmode=' . $dsn['sslmode'];
        }
        if (!empty($dsn['service'])) {
            $params[0] .= ' service=' . $dsn['service'];
        }

        if (isset($dsn['new_link'])
            && ($dsn['new_link'] == 'true' || $dsn['new_link'] === true))
        {
            if (version_compare(phpversion(), '4.3.0', '>=')) {
                $params[] = PGSQL_CONNECT_FORCE_NEW;
            }
        }

        $connect_function = $persistent ? 'pg_pconnect' : 'pg_connect';

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
        $ret = @pg_close($this->connection);
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
        $ismanip = DB::isManip($query);
        $this->last_query = $query;
        $query = $this->modifyQuery($query);
        if (!$this->autocommit && $ismanip) {
            if ($this->transaction_opcount == 0) {
                $result = @pg_exec($this->connection, 'begin;');
                if (!$result) {
                    return $this->pgsqlRaiseError();
                }
            }
            $this->transaction_opcount++;
        }
        $result = @pg_exec($this->connection, $query);
        if (!$result) {
            return $this->pgsqlRaiseError();
        }
        // Determine which queries that should return data, and which
        // should return an error code only.
        if ($ismanip) {
            $this->affected = @pg_affected_rows($result);
            return DB_OK;
        } elseif (preg_match('/^\s*\(*\s*(SELECT|EXPLAIN|SHOW)\s/si', $query)) {
            /* PostgreSQL commands:
               ABORT, ALTER, BEGIN, CLOSE, CLUSTER, COMMIT, COPY,
               CREATE, DECLARE, DELETE, DROP TABLE, EXPLAIN, FETCH,
               GRANT, INSERT, LISTEN, LOAD, LOCK, MOVE, NOTIFY, RESET,
               REVOKE, ROLLBACK, SELECT, SELECT INTO, SET, SHOW,
               UNLISTEN, UPDATE, VACUUM
            */
            $this->row[(int)$result] = 0; // reset the row counter.
            $numrows = $this->numRows($result);
            if (is_object($numrows)) {
                return $numrows;
            }
            $this->_num_rows[(int)$result] = $numrows;
            $this->affected = 0;
            return $result;
        } else {
            $this->affected = 0;
            return DB_OK;
        }
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal pgsql result pointer to the next available result
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
        $result_int = (int)$result;
        $rownum = ($rownum !== null) ? $rownum : $this->row[$result_int];
        if ($rownum >= $this->_num_rows[$result_int]) {
            return null;
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $arr = @pg_fetch_array($result, $rownum, PGSQL_ASSOC);
            if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE && $arr) {
                $arr = array_change_key_case($arr, CASE_LOWER);
            }
        } else {
            $arr = @pg_fetch_row($result, $rownum);
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
        $this->row[$result_int] = ++$rownum;
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
        if (is_resource($result)) {
            unset($this->row[(int)$result]);
            unset($this->_num_rows[(int)$result]);
            $this->affected = 0;
            return @pg_freeresult($result);
        }
        return false;
    }

    // }}}
    // {{{ quote()

    /**
     * @deprecated  Deprecated in release 1.6.0
     * @internal
     */
    function quote($str)
    {
        return $this->quoteSmart($str);
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
     *                   the data escaped according to MySQL's settings
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
    // {{{ escapeSimple()

    /**
     * Escapes a string according to the current DBMS's standards
     *
     * {@internal PostgreSQL treats a backslash as an escape character,
     * so they are escaped as well.
     *
     * Not using pg_escape_string() yet because it requires PostgreSQL
     * to be at version 7.2 or greater.}}
     *
     * @param string $str  the string to be escaped
     *
     * @return string  the escaped string
     *
     * @see DB_common::quoteSmart()
     * @since Method available since Release 1.6.0
     */
    function escapeSimple($str)
    {
        return str_replace("'", "''", str_replace('\\', '\\\\', $str));
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
        $cols = @pg_numfields($result);
        if (!$cols) {
            return $this->pgsqlRaiseError();
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
        $rows = @pg_numrows($result);
        if ($rows === null) {
            return $this->pgsqlRaiseError();
        }
        return $rows;
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
        // XXX if $this->transaction_opcount > 0, we should probably
        // issue a warning here.
        $this->autocommit = $onoff ? true : false;
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
        if ($this->transaction_opcount > 0) {
            // (disabled) hack to shut up error messages from libpq.a
            //@fclose(@fopen("php://stderr", "w"));
            $result = @pg_exec($this->connection, 'end;');
            $this->transaction_opcount = 0;
            if (!$result) {
                return $this->pgsqlRaiseError();
            }
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
        if ($this->transaction_opcount > 0) {
            $result = @pg_exec($this->connection, 'abort;');
            $this->transaction_opcount = 0;
            if (!$result) {
                return $this->pgsqlRaiseError();
            }
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
        return $this->affected;
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
     *      DB_pgsql::createSequence(), DB_pgsql::dropSequence()
     */
    function nextId($seq_name, $ondemand = true)
    {
        $seqname = $this->getSequenceName($seq_name);
        $repeat = false;
        do {
            $this->pushErrorHandling(PEAR_ERROR_RETURN);
            $result =& $this->query("SELECT NEXTVAL('${seqname}')");
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
     * @param string $seq_name  name of the new sequence
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     *
     * @see DB_common::createSequence(), DB_common::getSequenceName(),
     *      DB_pgsql::nextID(), DB_pgsql::dropSequence()
     */
    function createSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        $result = $this->query("CREATE SEQUENCE ${seqname}");
        return $result;
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
     *      DB_pgsql::nextID(), DB_pgsql::createSequence()
     */
    function dropSequence($seq_name)
    {
        return $this->query('DROP SEQUENCE '
                            . $this->getSequenceName($seq_name));
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
        return "$query LIMIT $count OFFSET $from";
    }

    // }}}
    // {{{ pgsqlRaiseError()

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
     *      DB_pgsql::errorNative(), DB_pgsql::errorCode()
     */
    function pgsqlRaiseError($errno = null)
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
     * {@internal Error messages are used instead of error codes 
     * in order to support older versions of PostgreSQL.}}
     *
     * @return string  the DBMS' error message
     */
    function errorNative()
    {
        return @pg_errormessage($this->connection);
    }

    // }}}
    // {{{ errorCode()

    /**
     * Determines PEAR::DB error code from the database's text error message.
     *
     * @param  string  $errormsg  error message returned from the database
     * @return integer  an error number from a DB error constant
     */
    function errorCode($errormsg)
    {
        static $error_regexps;
        if (!isset($error_regexps)) {
            $error_regexps = array(
                '/(relation|sequence|table).*does not exist|class .* not found/i'
                    => DB_ERROR_NOSUCHTABLE,
                '/index .* does not exist/'
                    => DB_ERROR_NOT_FOUND,
                '/column .* does not exist/i'
                    => DB_ERROR_NOSUCHFIELD,
                '/relation .* already exists/i'
                    => DB_ERROR_ALREADY_EXISTS,
                '/(divide|division) by zero$/i'
                    => DB_ERROR_DIVZERO,
                '/pg_atoi: error in .*: can\'t parse /i'
                    => DB_ERROR_INVALID_NUMBER,
                '/invalid input syntax for( type)? (integer|numeric)/i'
                    => DB_ERROR_INVALID_NUMBER,
                '/value .* is out of range for type \w*int/i'
                    => DB_ERROR_INVALID_NUMBER,
                '/integer out of range/i'
                    => DB_ERROR_INVALID_NUMBER,
                '/value too long for type character/i'
                    => DB_ERROR_INVALID,
                '/attribute .* not found|relation .* does not have attribute/i'
                    => DB_ERROR_NOSUCHFIELD,
                '/column .* specified in USING clause does not exist in (left|right) table/i'
                    => DB_ERROR_NOSUCHFIELD,
                '/parser: parse error at or near/i'
                    => DB_ERROR_SYNTAX,
                '/syntax error at/'
                    => DB_ERROR_SYNTAX,
                '/column reference .* is ambiguous/i'
                    => DB_ERROR_SYNTAX,
                '/permission denied/'
                    => DB_ERROR_ACCESS_VIOLATION,
                '/violates not-null constraint/'
                    => DB_ERROR_CONSTRAINT_NOT_NULL,
                '/violates [\w ]+ constraint/'
                    => DB_ERROR_CONSTRAINT,
                '/referential integrity violation/'
                    => DB_ERROR_CONSTRAINT,
                '/more expressions than target columns/i'
                    => DB_ERROR_VALUE_COUNT_ON_ROW,
            );
        }
        foreach ($error_regexps as $regexp => $code) {
            if (preg_match($regexp, $errormsg)) {
                return $code;
            }
        }
        // Fall back to DB_ERROR if there was no mapping.
        return DB_ERROR;
    }

    // }}}
    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set
     *
     * NOTE: only supports 'table' and 'flags' if <var>$result</var>
     * is a table name.
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
            $id = @pg_exec($this->connection, "SELECT * FROM $result LIMIT 0");
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
            return $this->pgsqlRaiseError(DB_ERROR_NEED_MORE_DATA);
        }

        if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE) {
            $case_func = 'strtolower';
        } else {
            $case_func = 'strval';
        }

        $count = @pg_numfields($id);
        $res   = array();

        if ($mode) {
            $res['num_fields'] = $count;
        }

        for ($i = 0; $i < $count; $i++) {
            $res[$i] = array(
                'table' => $got_string ? $case_func($result) : '',
                'name'  => $case_func(@pg_fieldname($id, $i)),
                'type'  => @pg_fieldtype($id, $i),
                'len'   => @pg_fieldsize($id, $i),
                'flags' => $got_string
                           ? $this->_pgFieldFlags($id, $i, $result)
                           : '',
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
            @pg_freeresult($id);
        }
        return $res;
    }

    // }}}
    // {{{ _pgFieldFlags()

    /**
     * Get a column's flags
     *
     * Supports "not_null", "default_value", "primary_key", "unique_key"
     * and "multiple_key".  The default value is passed through
     * rawurlencode() in case there are spaces in it.
     *
     * @param int $resource   the PostgreSQL result identifier
     * @param int $num_field  the field number
     *
     * @return string  the flags
     *
     * @access private
     */
    function _pgFieldFlags($resource, $num_field, $table_name)
    {
        $field_name = @pg_fieldname($resource, $num_field);

        $result = @pg_exec($this->connection, "SELECT f.attnotnull, f.atthasdef
                                FROM pg_attribute f, pg_class tab, pg_type typ
                                WHERE tab.relname = typ.typname
                                AND typ.typrelid = f.attrelid
                                AND f.attname = '$field_name'
                                AND tab.relname = '$table_name'");
        if (@pg_numrows($result) > 0) {
            $row = @pg_fetch_row($result, 0);
            $flags  = ($row[0] == 't') ? 'not_null ' : '';

            if ($row[1] == 't') {
                $result = @pg_exec($this->connection, "SELECT a.adsrc
                                    FROM pg_attribute f, pg_class tab, pg_type typ, pg_attrdef a
                                    WHERE tab.relname = typ.typname AND typ.typrelid = f.attrelid
                                    AND f.attrelid = a.adrelid AND f.attname = '$field_name'
                                    AND tab.relname = '$table_name' AND f.attnum = a.adnum");
                $row = @pg_fetch_row($result, 0);
                $num = preg_replace("/'(.*)'::\w+/", "\\1", $row[0]);
                $flags .= 'default_' . rawurlencode($num) . ' ';
            }
        } else {
            $flags = '';
        }
        $result = @pg_exec($this->connection, "SELECT i.indisunique, i.indisprimary, i.indkey
                                FROM pg_attribute f, pg_class tab, pg_type typ, pg_index i
                                WHERE tab.relname = typ.typname
                                AND typ.typrelid = f.attrelid
                                AND f.attrelid = i.indrelid
                                AND f.attname = '$field_name'
                                AND tab.relname = '$table_name'");
        $count = @pg_numrows($result);

        for ($i = 0; $i < $count ; $i++) {
            $row = @pg_fetch_row($result, $i);
            $keys = explode(' ', $row[2]);

            if (in_array($num_field + 1, $keys)) {
                $flags .= ($row[0] == 't' && $row[1] == 'f') ? 'unique_key ' : '';
                $flags .= ($row[1] == 't') ? 'primary_key ' : '';
                if (count($keys) > 1)
                    $flags .= 'multiple_key ';
            }
        }

        return trim($flags);
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
                return 'SELECT c.relname AS "Name"'
                        . ' FROM pg_class c, pg_user u'
                        . ' WHERE c.relowner = u.usesysid'
                        . " AND c.relkind = 'r'"
                        . ' AND NOT EXISTS'
                        . ' (SELECT 1 FROM pg_views'
                        . '  WHERE viewname = c.relname)'
                        . " AND c.relname !~ '^(pg_|sql_)'"
                        . ' UNION'
                        . ' SELECT c.relname AS "Name"'
                        . ' FROM pg_class c'
                        . " WHERE c.relkind = 'r'"
                        . ' AND NOT EXISTS'
                        . ' (SELECT 1 FROM pg_views'
                        . '  WHERE viewname = c.relname)'
                        . ' AND NOT EXISTS'
                        . ' (SELECT 1 FROM pg_user'
                        . '  WHERE usesysid = c.relowner)'
                        . " AND c.relname !~ '^pg_'";
            case 'schema.tables':
                return "SELECT schemaname || '.' || tablename"
                        . ' AS "Name"'
                        . ' FROM pg_catalog.pg_tables'
                        . ' WHERE schemaname NOT IN'
                        . " ('pg_catalog', 'information_schema', 'pg_toast')";
            case 'views':
                // Table cols: viewname | viewowner | definition
                return 'SELECT viewname from pg_views WHERE schemaname'
                        . " NOT IN ('information_schema', 'pg_catalog')";
            case 'users':
                // cols: usename |usesysid|usecreatedb|usetrace|usesuper|usecatupd|passwd  |valuntil
                return 'SELECT usename FROM pg_user';
            case 'databases':
                return 'SELECT datname FROM pg_database';
            case 'functions':
            case 'procedures':
                return 'SELECT proname FROM pg_proc WHERE proowner <> 1';
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
