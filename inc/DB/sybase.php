<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * The PEAR DB driver for PHP's sybase extension
 * for interacting with Sybase databases
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
 * @author     Sterling Hughes <sterling@php.net>
 * @author     Antônio Carlos Venâncio Júnior <floripa@php.net>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: sybase.php,v 1.78 2005/02/20 00:44:48 danielc Exp $
 * @link       http://pear.php.net/package/DB
 */

/**
 * Obtain the DB_common class so it can be extended from
 */
require_once 'DB/common.php';

/**
 * The methods PEAR DB uses to interact with PHP's sybase extension
 * for interacting with Sybase databases
 *
 * These methods overload the ones declared in DB_common.
 *
 * WARNING:  This driver may fail with multiple connections under the
 * same user/pass/host and different databases.
 *
 * @category   Database
 * @package    DB
 * @author     Sterling Hughes <sterling@php.net>
 * @author     Antônio Carlos Venâncio Júnior <floripa@php.net>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/DB
 */
class DB_sybase extends DB_common
{
    // {{{ properties

    /**
     * The DB driver type (mysql, oci8, odbc, etc.)
     * @var string
     */
    var $phptype = 'sybase';

    /**
     * The database syntax variant to be used (db2, access, etc.), if any
     * @var string
     */
    var $dbsyntax = 'sybase';

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
     * The database specified in the DSN
     *
     * It's a fix to allow calls to different databases in the same script.
     *
     * @var string
     * @access private
     */
    var $_db = '';


    // }}}
    // {{{ constructor

    /**
     * This constructor calls <kbd>$this->DB_common()</kbd>
     *
     * @return void
     */
    function DB_sybase()
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
     * PEAR DB's sybase driver supports the following extra DSN options:
     *   + appname       The application name to use on this connection.
     *                   Available since PEAR DB 1.7.0.
     *   + charset       The character set to use on this connection.
     *                   Available since PEAR DB 1.7.0.
     *
     * @param array $dsn         the data source name
     * @param bool  $persistent  should the connection be persistent?
     *
     * @return int  DB_OK on success. A DB_Error object on failure.
     */
    function connect($dsn, $persistent = false)
    {
        if (!PEAR::loadExtension('sybase') &&
            !PEAR::loadExtension('sybase_ct'))
        {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }

        $this->dsn = $dsn;
        if ($dsn['dbsyntax']) {
            $this->dbsyntax = $dsn['dbsyntax'];
        }

        $dsn['hostspec'] = $dsn['hostspec'] ? $dsn['hostspec'] : 'localhost';
        $dsn['password'] = !empty($dsn['password']) ? $dsn['password'] : false;
        $dsn['charset'] = isset($dsn['charset']) ? $dsn['charset'] : false;
        $dsn['appname'] = isset($dsn['appname']) ? $dsn['appname'] : false;

        $connect_function = $persistent ? 'sybase_pconnect' : 'sybase_connect';

        if ($dsn['username']) {
            $this->connection = @$connect_function($dsn['hostspec'],
                                                   $dsn['username'],
                                                   $dsn['password'],
                                                   $dsn['charset'],
                                                   $dsn['appname']);
        } else {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED,
                                     null, null, null,
                                     'The DSN did not contain a username.');
        }

        if (!$this->connection) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED,
                                     null, null, null,
                                     @sybase_get_last_message());
        }

        if ($dsn['database']) {
            if (!@sybase_select_db($dsn['database'], $this->connection)) {
                return $this->raiseError(DB_ERROR_NODBSELECTED,
                                         null, null, null,
                                         @sybase_get_last_message());
            }
            $this->_db = $dsn['database'];
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
        $ret = @sybase_close($this->connection);
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
        if (!@sybase_select_db($this->_db, $this->connection)) {
            return $this->sybaseRaiseError(DB_ERROR_NODBSELECTED);
        }
        $query = $this->modifyQuery($query);
        if (!$this->autocommit && $ismanip) {
            if ($this->transaction_opcount == 0) {
                $result = @sybase_query('BEGIN TRANSACTION', $this->connection);
                if (!$result) {
                    return $this->sybaseRaiseError();
                }
            }
            $this->transaction_opcount++;
        }
        $result = @sybase_query($query, $this->connection);
        if (!$result) {
            return $this->sybaseRaiseError();
        }
        if (is_resource($result)) {
            return $result;
        }
        // Determine which queries that should return data, and which
        // should return an error code only.
        return $ismanip ? DB_OK : $result;
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal sybase result pointer to the next available result
     *
     * @param a valid sybase result resource
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
            if (!@sybase_data_seek($result, $rownum)) {
                return null;
            }
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            if (function_exists('sybase_fetch_assoc')) {
                $arr = @sybase_fetch_assoc($result);
            } else {
                if ($arr = @sybase_fetch_array($result)) {
                    foreach ($arr as $key => $value) {
                        if (is_int($key)) {
                            unset($arr[$key]);
                        }
                    }
                }
            }
            if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE && $arr) {
                $arr = array_change_key_case($arr, CASE_LOWER);
            }
        } else {
            $arr = @sybase_fetch_row($result);
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
        return @sybase_free_result($result);
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
        $cols = @sybase_num_fields($result);
        if (!$cols) {
            return $this->sybaseRaiseError();
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
        $rows = @sybase_num_rows($result);
        if ($rows === false) {
            return $this->sybaseRaiseError();
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
            $result = @sybase_affected_rows($this->connection);
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
     *      DB_sybase::createSequence(), DB_sybase::dropSequence()
     */
    function nextId($seq_name, $ondemand = true)
    {
        $seqname = $this->getSequenceName($seq_name);
        if (!@sybase_select_db($this->_db, $this->connection)) {
            return $this->sybaseRaiseError(DB_ERROR_NODBSELECTED);
        }
        $repeat = 0;
        do {
            $this->pushErrorHandling(PEAR_ERROR_RETURN);
            $result = $this->query("INSERT INTO $seqname (vapor) VALUES (0)");
            $this->popErrorHandling();
            if ($ondemand && DB::isError($result) &&
                ($result->getCode() == DB_ERROR || $result->getCode() == DB_ERROR_NOSUCHTABLE))
            {
                $repeat = 1;
                $result = $this->createSequence($seq_name);
                if (DB::isError($result)) {
                    return $this->raiseError($result);
                }
            } elseif (!DB::isError($result)) {
                $result =& $this->query("SELECT @@IDENTITY FROM $seqname");
                $repeat = 0;
            } else {
                $repeat = false;
            }
        } while ($repeat);
        if (DB::isError($result)) {
            return $this->raiseError($result);
        }
        $result = $result->fetchRow(DB_FETCHMODE_ORDERED);
        return $result[0];
    }

    /**
     * Creates a new sequence
     *
     * @param string $seq_name  name of the new sequence
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     *
     * @see DB_common::createSequence(), DB_common::getSequenceName(),
     *      DB_sybase::nextID(), DB_sybase::dropSequence()
     */
    function createSequence($seq_name)
    {
        return $this->query('CREATE TABLE '
                            . $this->getSequenceName($seq_name)
                            . ' (id numeric(10, 0) IDENTITY NOT NULL,'
                            . ' vapor int NULL)');
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
     *      DB_sybase::nextID(), DB_sybase::createSequence()
     */
    function dropSequence($seq_name)
    {
        return $this->query('DROP TABLE ' . $this->getSequenceName($seq_name));
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
            if (!@sybase_select_db($this->_db, $this->connection)) {
                return $this->sybaseRaiseError(DB_ERROR_NODBSELECTED);
            }
            $result = @sybase_query('COMMIT', $this->connection);
            $this->transaction_opcount = 0;
            if (!$result) {
                return $this->sybaseRaiseError();
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
            if (!@sybase_select_db($this->_db, $this->connection)) {
                return $this->sybaseRaiseError(DB_ERROR_NODBSELECTED);
            }
            $result = @sybase_query('ROLLBACK', $this->connection);
            $this->transaction_opcount = 0;
            if (!$result) {
                return $this->sybaseRaiseError();
            }
        }
        return DB_OK;
    }

    // }}}
    // {{{ sybaseRaiseError()

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
     *      DB_sybase::errorNative(), DB_sybase::errorCode()
     */
    function sybaseRaiseError($errno = null)
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
        return @sybase_get_last_message();
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
                '/Incorrect syntax near/'
                    => DB_ERROR_SYNTAX,
                '/^Unclosed quote before the character string [\"\'].*[\"\']\./'
                    => DB_ERROR_SYNTAX,
                '/Implicit conversion (from datatype|of NUMERIC value)/i'
                    => DB_ERROR_INVALID_NUMBER,
                '/Cannot drop the table [\"\'].+[\"\'], because it doesn\'t exist in the system catalogs\./'
                    => DB_ERROR_NOSUCHTABLE,
                '/Only the owner of object [\"\'].+[\"\'] or a user with System Administrator \(SA\) role can run this command\./'
                    => DB_ERROR_ACCESS_VIOLATION,
                '/^.+ permission denied on object .+, database .+, owner .+/'
                    => DB_ERROR_ACCESS_VIOLATION,
                '/^.* permission denied, database .+, owner .+/'
                    => DB_ERROR_ACCESS_VIOLATION,
                '/[^.*] not found\./'
                    => DB_ERROR_NOSUCHTABLE,
                '/There is already an object named/'
                    => DB_ERROR_ALREADY_EXISTS,
                '/Invalid column name/'
                    => DB_ERROR_NOSUCHFIELD,
                '/does not allow null values/'
                    => DB_ERROR_CONSTRAINT_NOT_NULL,
                '/Command has been aborted/'
                    => DB_ERROR_CONSTRAINT,
                '/^Cannot drop the index .* because it doesn\'t exist/i'
                    => DB_ERROR_NOT_FOUND,
                '/^There is already an index/i'
                    => DB_ERROR_ALREADY_EXISTS,
                '/^There are fewer columns in the INSERT statement than values specified/i'
                    => DB_ERROR_VALUE_COUNT_ON_ROW,
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
     * @since Method available since Release 1.6.0
     */
    function tableInfo($result, $mode = null)
    {
        if (is_string($result)) {
            /*
             * Probably received a table name.
             * Create a result resource identifier.
             */
            if (!@sybase_select_db($this->_db, $this->connection)) {
                return $this->sybaseRaiseError(DB_ERROR_NODBSELECTED);
            }
            $id = @sybase_query("SELECT * FROM $result WHERE 1=0",
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
            return $this->sybaseRaiseError(DB_ERROR_NEED_MORE_DATA);
        }

        if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE) {
            $case_func = 'strtolower';
        } else {
            $case_func = 'strval';
        }

        $count = @sybase_num_fields($id);
        $res   = array();

        if ($mode) {
            $res['num_fields'] = $count;
        }

        for ($i = 0; $i < $count; $i++) {
            $f = @sybase_fetch_field($id, $i);
            // column_source is often blank
            $res[$i] = array(
                'table' => $got_string
                           ? $case_func($result)
                           : $case_func($f->column_source),
                'name'  => $case_func($f->name),
                'type'  => $f->type,
                'len'   => $f->max_length,
                'flags' => '',
            );
            if ($res[$i]['table']) {
                $res[$i]['flags'] = $this->_sybase_field_flags(
                        $res[$i]['table'], $res[$i]['name']);
            }
            if ($mode & DB_TABLEINFO_ORDER) {
                $res['order'][$res[$i]['name']] = $i;
            }
            if ($mode & DB_TABLEINFO_ORDERTABLE) {
                $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
            }
        }

        // free the result only if we were called on a table
        if ($got_string) {
            @sybase_free_result($id);
        }
        return $res;
    }

    // }}}
    // {{{ _sybase_field_flags()

    /**
     * Get the flags for a field
     *
     * Currently supports:
     *  + <samp>unique_key</samp>    (unique index, unique check or primary_key)
     *  + <samp>multiple_key</samp>  (multi-key index)
     *
     * @param string  $table   the table name
     * @param string  $column  the field name
     *
     * @return string  space delimited string of flags.  Empty string if none.
     *
     * @access private
     */
    function _sybase_field_flags($table, $column)
    {
        static $tableName = null;
        static $flags = array();

        if ($table != $tableName) {
            $flags = array();
            $tableName = $table;

            // get unique/primary keys
            $res = $this->getAll("sp_helpindex $table", DB_FETCHMODE_ASSOC);

            if (!isset($res[0]['index_description'])) {
                return '';
            }

            foreach ($res as $val) {
                $keys = explode(', ', trim($val['index_keys']));

                if (sizeof($keys) > 1) {
                    foreach ($keys as $key) {
                        $this->_add_flag($flags[$key], 'multiple_key');
                    }
                }

                if (strpos($val['index_description'], 'unique')) {
                    foreach ($keys as $key) {
                        $this->_add_flag($flags[$key], 'unique_key');
                    }
                }
            }

        }

        if (array_key_exists($column, $flags)) {
            return(implode(' ', $flags[$column]));
        }

        return '';
    }

    // }}}
    // {{{ _add_flag()

    /**
     * Adds a string to the flags array if the flag is not yet in there
     * - if there is no flag present the array is created
     *
     * @param array  $array  reference of flags array to add a value to
     * @param mixed  $value  value to add to the flag array
     *
     * @return void
     *
     * @access private
     */
    function _add_flag(&$array, $value)
    {
        if (!is_array($array)) {
            $array = array($value);
        } elseif (!in_array($value, $array)) {
            array_push($array, $value);
        }
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
                return "SELECT name FROM sysobjects WHERE type = 'U'"
                       . ' ORDER BY name';
            case 'views':
                return "SELECT name FROM sysobjects WHERE type = 'V'";
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
