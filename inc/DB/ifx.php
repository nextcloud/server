<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * The PEAR DB driver for PHP's ifx extension
 * for interacting with Informix databases
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
 * @author     Tomas V.V.Cox <cox@idecnet.com>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: ifx.php,v 1.70 2005/02/20 00:44:48 danielc Exp $
 * @link       http://pear.php.net/package/DB
 */

/**
 * Obtain the DB_common class so it can be extended from
 */
require_once 'DB/common.php';

/**
 * The methods PEAR DB uses to interact with PHP's ifx extension
 * for interacting with Informix databases
 *
 * These methods overload the ones declared in DB_common.
 *
 * More info on Informix errors can be found at:
 * http://www.informix.com/answers/english/ierrors.htm
 *
 * TODO:
 *   - set needed env Informix vars on connect
 *   - implement native prepare/execute
 *
 * @category   Database
 * @package    DB
 * @author     Tomas V.V.Cox <cox@idecnet.com>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/DB
 */
class DB_ifx extends DB_common
{
    // {{{ properties

    /**
     * The DB driver type (mysql, oci8, odbc, etc.)
     * @var string
     */
    var $phptype = 'ifx';

    /**
     * The database syntax variant to be used (db2, access, etc.), if any
     * @var string
     */
    var $dbsyntax = 'ifx';

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
        'numrows'       => 'emulate',
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
        '-201'    => DB_ERROR_SYNTAX,
        '-206'    => DB_ERROR_NOSUCHTABLE,
        '-217'    => DB_ERROR_NOSUCHFIELD,
        '-236'    => DB_ERROR_VALUE_COUNT_ON_ROW,
        '-239'    => DB_ERROR_CONSTRAINT,
        '-253'    => DB_ERROR_SYNTAX,
        '-292'    => DB_ERROR_CONSTRAINT_NOT_NULL,
        '-310'    => DB_ERROR_ALREADY_EXISTS,
        '-316'    => DB_ERROR_ALREADY_EXISTS,
        '-319'    => DB_ERROR_NOT_FOUND,
        '-329'    => DB_ERROR_NODBSELECTED,
        '-346'    => DB_ERROR_CONSTRAINT,
        '-386'    => DB_ERROR_CONSTRAINT_NOT_NULL,
        '-391'    => DB_ERROR_CONSTRAINT_NOT_NULL,
        '-554'    => DB_ERROR_SYNTAX,
        '-691'    => DB_ERROR_CONSTRAINT,
        '-692'    => DB_ERROR_CONSTRAINT,
        '-703'    => DB_ERROR_CONSTRAINT_NOT_NULL,
        '-1204'   => DB_ERROR_INVALID_DATE,
        '-1205'   => DB_ERROR_INVALID_DATE,
        '-1206'   => DB_ERROR_INVALID_DATE,
        '-1209'   => DB_ERROR_INVALID_DATE,
        '-1210'   => DB_ERROR_INVALID_DATE,
        '-1212'   => DB_ERROR_INVALID_DATE,
        '-1213'   => DB_ERROR_INVALID_NUMBER,
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
     * @access private
     */
    var $affected = 0;


    // }}}
    // {{{ constructor

    /**
     * This constructor calls <kbd>$this->DB_common()</kbd>
     *
     * @return void
     */
    function DB_ifx()
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
        if (!PEAR::loadExtension('informix') &&
            !PEAR::loadExtension('Informix'))
        {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }

        $this->dsn = $dsn;
        if ($dsn['dbsyntax']) {
            $this->dbsyntax = $dsn['dbsyntax'];
        }

        $dbhost = $dsn['hostspec'] ? '@' . $dsn['hostspec'] : '';
        $dbname = $dsn['database'] ? $dsn['database'] . $dbhost : '';
        $user = $dsn['username'] ? $dsn['username'] : '';
        $pw = $dsn['password'] ? $dsn['password'] : '';

        $connect_function = $persistent ? 'ifx_pconnect' : 'ifx_connect';

        $this->connection = @$connect_function($dbname, $user, $pw);
        if (!is_resource($this->connection)) {
            return $this->ifxRaiseError(DB_ERROR_CONNECT_FAILED);
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
        $ret = @ifx_close($this->connection);
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
        $this->affected   = null;
        if (preg_match('/(SELECT)/i', $query)) {    //TESTME: Use !DB::isManip()?
            // the scroll is needed for fetching absolute row numbers
            // in a select query result
            $result = @ifx_query($query, $this->connection, IFX_SCROLL);
        } else {
            if (!$this->autocommit && $ismanip) {
                if ($this->transaction_opcount == 0) {
                    $result = @ifx_query('BEGIN WORK', $this->connection);
                    if (!$result) {
                        return $this->ifxRaiseError();
                    }
                }
                $this->transaction_opcount++;
            }
            $result = @ifx_query($query, $this->connection);
        }
        if (!$result) {
            return $this->ifxRaiseError();
        }
        $this->affected = @ifx_affected_rows($result);
        // Determine which queries should return data, and which
        // should return an error code only.
        if (preg_match('/(SELECT)/i', $query)) {
            return $result;
        }
        // XXX Testme: free results inside a transaction
        // may cause to stop it and commit the work?

        // Result has to be freed even with a insert or update
        @ifx_free_result($result);

        return DB_OK;
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal ifx result pointer to the next available result
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
            return $this->affected;
        } else {
            return 0;
        }
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
        if (($rownum !== null) && ($rownum < 0)) {
            return null;
        }
        if ($rownum === null) {
            /*
             * Even though fetch_row() should return the next row  if
             * $rownum is null, it doesn't in all cases.  Bug 598.
             */
            $rownum = 'NEXT';
        } else {
            // Index starts at row 1, unlike most DBMS's starting at 0.
            $rownum++;
        }
        if (!$arr = @ifx_fetch_row($result, $rownum)) {
            return null;
        }
        if ($fetchmode !== DB_FETCHMODE_ASSOC) {
            $i=0;
            $order = array();
            foreach ($arr as $val) {
                $order[$i++] = $val;
            }
            $arr = $order;
        } elseif ($fetchmode == DB_FETCHMODE_ASSOC &&
                  $this->options['portability'] & DB_PORTABILITY_LOWERCASE)
        {
            $arr = array_change_key_case($arr, CASE_LOWER);
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
    function numCols($result)
    {
        if (!$cols = @ifx_num_fields($result)) {
            return $this->ifxRaiseError();
        }
        return $cols;
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
        return @ifx_free_result($result);
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
    function autoCommit($onoff = true)
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
            $result = @ifx_query('COMMIT WORK', $this->connection);
            $this->transaction_opcount = 0;
            if (!$result) {
                return $this->ifxRaiseError();
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
            $result = @ifx_query('ROLLBACK WORK', $this->connection);
            $this->transaction_opcount = 0;
            if (!$result) {
                return $this->ifxRaiseError();
            }
        }
        return DB_OK;
    }

    // }}}
    // {{{ ifxRaiseError()

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
     *      DB_ifx::errorNative(), DB_ifx::errorCode()
     */
    function ifxRaiseError($errno = null)
    {
        if ($errno === null) {
            $errno = $this->errorCode(ifx_error());
        }
        return $this->raiseError($errno, null, null, null,
                                 $this->errorNative());
    }

    // }}}
    // {{{ errorNative()

    /**
     * Gets the DBMS' native error code and message produced by the last query
     *
     * @return string  the DBMS' error code and message
     */
    function errorNative()
    {
        return @ifx_error() . ' ' . @ifx_errormsg();
    }

    // }}}
    // {{{ errorCode()

    /**
     * Maps native error codes to DB's portable ones.
     *
     * Requires that the DB implementation's constructor fills
     * in the <var>$errorcode_map</var> property.
     *
     * @param  string  $nativecode  error code returned by the database
     * @return int a portable DB error code, or DB_ERROR if this DB
     * implementation has no mapping for the given error code.
     */
    function errorCode($nativecode)
    {
        if (ereg('SQLCODE=(.*)]', $nativecode, $match)) {
            $code = $match[1];
            if (isset($this->errorcode_map[$code])) {
                return $this->errorcode_map[$code];
            }
        }
        return DB_ERROR;
    }

    // }}}
    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set
     *
     * NOTE: only supports 'table' if <var>$result</var> is a table name.
     *
     * If analyzing a query result and the result has duplicate field names,
     * an error will be raised saying
     * <samp>can't distinguish duplicate field names</samp>.
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
            $id = @ifx_query("SELECT * FROM $result WHERE 1=0",
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
             */
            $id = $result;
            $got_string = false;
        }

        if (!is_resource($id)) {
            return $this->ifxRaiseError(DB_ERROR_NEED_MORE_DATA);
        }

        $flds = @ifx_fieldproperties($id);
        $count = @ifx_num_fields($id);

        if (count($flds) != $count) {
            return $this->raiseError("can't distinguish duplicate field names");
        }

        if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE) {
            $case_func = 'strtolower';
        } else {
            $case_func = 'strval';
        }

        $i   = 0;
        $res = array();

        if ($mode) {
            $res['num_fields'] = $count;
        }

        foreach ($flds as $key => $value) {
            $props = explode(';', $value);
            $res[$i] = array(
                'table' => $got_string ? $case_func($result) : '',
                'name'  => $case_func($key),
                'type'  => $props[0],
                'len'   => $props[1],
                'flags' => $props[4] == 'N' ? 'not_null' : '',
            );
            if ($mode & DB_TABLEINFO_ORDER) {
                $res['order'][$res[$i]['name']] = $i;
            }
            if ($mode & DB_TABLEINFO_ORDERTABLE) {
                $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
            }
            $i++;
        }

        // free the result only if we were called on a table
        if ($got_string) {
            @ifx_free_result($id);
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
                return 'SELECT tabname FROM systables WHERE tabid >= 100';
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
