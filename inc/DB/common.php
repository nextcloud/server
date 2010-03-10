<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Contains the DB_common base class
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
 * @author     Tomas V.V. Cox <cox@idecnet.com>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: common.php,v 1.137 2005/04/07 14:27:35 danielc Exp $
 * @link       http://pear.php.net/package/DB
 */

/**
 * Obtain the PEAR class so it can be extended from
 */
require_once 'PEAR.php';

/**
 * DB_common is the base class from which each database driver class extends
 *
 * All common methods are declared here.  If a given DBMS driver contains
 * a particular method, that method will overload the one here.
 *
 * @category   Database
 * @package    DB
 * @author     Stig Bakken <ssb@php.net>
 * @author     Tomas V.V. Cox <cox@idecnet.com>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/DB
 */
class DB_common extends PEAR
{
    // {{{ properties

    /**
     * The current default fetch mode
     * @var integer
     */
    var $fetchmode = DB_FETCHMODE_ORDERED;

    /**
     * The name of the class into which results should be fetched when
     * DB_FETCHMODE_OBJECT is in effect
     *
     * @var string
     */
    var $fetchmode_object_class = 'stdClass';

    /**
     * Was a connection present when the object was serialized()?
     * @var bool
     * @see DB_common::__sleep(), DB_common::__wake()
     */
    var $was_connected = null;

    /**
     * The most recently executed query
     * @var string
     */
    var $last_query = '';

    /**
     * Run-time configuration options
     *
     * The 'optimize' option has been deprecated.  Use the 'portability'
     * option instead.
     *
     * @var array
     * @see DB_common::setOption()
     */
    var $options = array(
        'result_buffering' => 500,
        'persistent' => false,
        'ssl' => false,
        'debug' => 0,
        'seqname_format' => '%s_seq',
        'autofree' => false,
        'portability' => DB_PORTABILITY_NONE,
        'optimize' => 'performance',  // Deprecated.  Use 'portability'.
    );

    /**
     * The parameters from the most recently executed query
     * @var array
     * @since Property available since Release 1.7.0
     */
    var $last_parameters = array();

    /**
     * The elements from each prepared statement
     * @var array
     */
    var $prepare_tokens = array();

    /**
     * The data types of the various elements in each prepared statement
     * @var array
     */
    var $prepare_types = array();

    /**
     * The prepared queries
     * @var array
     */
    var $prepared_queries = array();


    // }}}
    // {{{ DB_common

    /**
     * This constructor calls <kbd>$this->PEAR('DB_Error')</kbd>
     *
     * @return void
     */
    function DB_common()
    {
        $this->PEAR('DB_Error');
    }

    // }}}
    // {{{ __sleep()

    /**
     * Automatically indicates which properties should be saved
     * when PHP's serialize() function is called
     *
     * @return array  the array of properties names that should be saved
     */
    function __sleep()
    {
        if ($this->connection) {
            // Don't disconnect(), people use serialize() for many reasons
            $this->was_connected = true;
        } else {
            $this->was_connected = false;
        }
        if (isset($this->autocommit)) {
            return array('autocommit',
                         'dbsyntax',
                         'dsn',
                         'features',
                         'fetchmode',
                         'fetchmode_object_class',
                         'options',
                         'was_connected',
                   );
        } else {
            return array('dbsyntax',
                         'dsn',
                         'features',
                         'fetchmode',
                         'fetchmode_object_class',
                         'options',
                         'was_connected',
                   );
        }
    }

    // }}}
    // {{{ __wakeup()

    /**
     * Automatically reconnects to the database when PHP's unserialize()
     * function is called
     *
     * The reconnection attempt is only performed if the object was connected
     * at the time PHP's serialize() function was run.
     *
     * @return void
     */
    function __wakeup()
    {
        if ($this->was_connected) {
            $this->connect($this->dsn, $this->options);
        }
    }

    // }}}
    // {{{ __toString()

    /**
     * Automatic string conversion for PHP 5
     *
     * @return string  a string describing the current PEAR DB object
     *
     * @since Method available since Release 1.7.0
     */
    function __toString()
    {
        $info = strtolower(get_class($this));
        $info .=  ': (phptype=' . $this->phptype .
                  ', dbsyntax=' . $this->dbsyntax .
                  ')';
        if ($this->connection) {
            $info .= ' [connected]';
        }
        return $info;
    }

    // }}}
    // {{{ toString()

    /**
     * DEPRECATED:  String conversion method
     *
     * @return string  a string describing the current PEAR DB object
     *
     * @deprecated Method deprecated in Release 1.7.0
     */
    function toString()
    {
        return $this->__toString();
    }

    // }}}
    // {{{ quoteString()

    /**
     * DEPRECATED: Quotes a string so it can be safely used within string
     * delimiters in a query
     *
     * @param string $string  the string to be quoted
     *
     * @return string  the quoted string
     *
     * @see DB_common::quoteSmart(), DB_common::escapeSimple()
     * @deprecated Method deprecated some time before Release 1.2
     */
    function quoteString($string)
    {
        $string = $this->quote($string);
        if ($string{0} == "'") {
            return substr($string, 1, -1);
        }
        return $string;
    }

    // }}}
    // {{{ quote()

    /**
     * DEPRECATED: Quotes a string so it can be safely used in a query
     *
     * @param string $string  the string to quote
     *
     * @return string  the quoted string or the string <samp>NULL</samp>
     *                  if the value submitted is <kbd>null</kbd>.
     *
     * @see DB_common::quoteSmart(), DB_common::escapeSimple()
     * @deprecated Deprecated in release 1.6.0
     */
    function quote($string = null)
    {
        return ($string === null) ? 'NULL'
                                  : "'" . str_replace("'", "''", $string) . "'";
    }

    // }}}
    // {{{ quoteIdentifier()

    /**
     * Quotes a string so it can be safely used as a table or column name
     *
     * Delimiting style depends on which database driver is being used.
     *
     * NOTE: just because you CAN use delimited identifiers doesn't mean
     * you SHOULD use them.  In general, they end up causing way more
     * problems than they solve.
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
     *   + odbc(access)
     *   + odbc(db2)
     *   + pgsql
     *   + sqlite
     *   + sybase (must execute <kbd>set quoted_identifier on</kbd> sometime
     *     prior to use)
     *
     * InterBase doesn't seem to be able to use delimited identifiers
     * via PHP 4.  They work fine under PHP 5.
     *
     * @param string $str  the identifier name to be quoted
     *
     * @return string  the quoted identifier
     *
     * @since Method available since Release 1.6.0
     */
    function quoteIdentifier($str)
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }

    // }}}
    // {{{ quoteSmart()

    /**
     * Formats input so it can be safely used in a query
     *
     * The output depends on the PHP data type of input and the database
     * type being used.
     *
     * @param mixed $in  the data to be formatted
     *
     * @return mixed  the formatted data.  The format depends on the input's
     *                 PHP type:
     * <ul>
     *  <li>
     *    <kbd>input</kbd> -> <samp>returns</samp>
     *  </li>
     *  <li>
     *    <kbd>null</kbd> -> the string <samp>NULL</samp>
     *  </li>
     *  <li>
     *    <kbd>integer</kbd> or <kbd>double</kbd> -> the unquoted number
     *  </li>
     *  <li>
     *    <kbd>bool</kbd> -> output depends on the driver in use
     *    Most drivers return integers: <samp>1</samp> if
     *    <kbd>true</kbd> or <samp>0</samp> if
     *    <kbd>false</kbd>.
     *    Some return strings: <samp>TRUE</samp> if
     *    <kbd>true</kbd> or <samp>FALSE</samp> if
     *    <kbd>false</kbd>.
     *    Finally one returns strings: <samp>T</samp> if
     *    <kbd>true</kbd> or <samp>F</samp> if
     *    <kbd>false</kbd>. Here is a list of each DBMS,
     *    the values returned and the suggested column type:
     *    <ul>
     *      <li>
     *        <kbd>dbase</kbd> -> <samp>T/F</samp>
     *        (<kbd>Logical</kbd>)
     *      </li>
     *      <li>
     *        <kbd>fbase</kbd> -> <samp>TRUE/FALSE</samp>
     *        (<kbd>BOOLEAN</kbd>)
     *      </li>
     *      <li>
     *        <kbd>ibase</kbd> -> <samp>1/0</samp>
     *        (<kbd>SMALLINT</kbd>) [1]
     *      </li>
     *      <li>
     *        <kbd>ifx</kbd> -> <samp>1/0</samp>
     *        (<kbd>SMALLINT</kbd>) [1]
     *      </li>
     *      <li>
     *        <kbd>msql</kbd> -> <samp>1/0</samp>
     *        (<kbd>INTEGER</kbd>)
     *      </li>
     *      <li>
     *        <kbd>mssql</kbd> -> <samp>1/0</samp>
     *        (<kbd>BIT</kbd>)
     *      </li>
     *      <li>
     *        <kbd>mysql</kbd> -> <samp>1/0</samp>
     *        (<kbd>TINYINT(1)</kbd>)
     *      </li>
     *      <li>
     *        <kbd>mysqli</kbd> -> <samp>1/0</samp>
     *        (<kbd>TINYINT(1)</kbd>)
     *      </li>
     *      <li>
     *        <kbd>oci8</kbd> -> <samp>1/0</samp>
     *        (<kbd>NUMBER(1)</kbd>)
     *      </li>
     *      <li>
     *        <kbd>odbc</kbd> -> <samp>1/0</samp>
     *        (<kbd>SMALLINT</kbd>) [1]
     *      </li>
     *      <li>
     *        <kbd>pgsql</kbd> -> <samp>TRUE/FALSE</samp>
     *        (<kbd>BOOLEAN</kbd>)
     *      </li>
     *      <li>
     *        <kbd>sqlite</kbd> -> <samp>1/0</samp>
     *        (<kbd>INTEGER</kbd>)
     *      </li>
     *      <li>
     *        <kbd>sybase</kbd> -> <samp>1/0</samp>
     *        (<kbd>TINYINT(1)</kbd>)
     *      </li>
     *    </ul>
     *    [1] Accommodate the lowest common denominator because not all
     *    versions of have <kbd>BOOLEAN</kbd>.
     *  </li>
     *  <li>
     *    other (including strings and numeric strings) ->
     *    the data with single quotes escaped by preceeding
     *    single quotes, backslashes are escaped by preceeding
     *    backslashes, then the whole string is encapsulated
     *    between single quotes
     *  </li>
     * </ul>
     *
     * @see DB_common::escapeSimple()
     * @since Method available since Release 1.6.0
     */
    function quoteSmart($in)
    {
        if (is_int($in) || is_double($in)) {
            return $in;
        } elseif (is_bool($in)) {
            return $in ? 1 : 0;
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
     * In SQLite, this makes things safe for inserts/updates, but may
     * cause problems when performing text comparisons against columns
     * containing binary data. See the
     * {@link http://php.net/sqlite_escape_string PHP manual} for more info.
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
        return str_replace("'", "''", $str);
    }

    // }}}
    // {{{ provides()

    /**
     * Tells whether the present driver supports a given feature
     *
     * @param string $feature  the feature you're curious about
     *
     * @return bool  whether this driver supports $feature
     */
    function provides($feature)
    {
        return $this->features[$feature];
    }

    // }}}
    // {{{ setFetchMode()

    /**
     * Sets the fetch mode that should be used by default for query results
     *
     * @param integer $fetchmode    DB_FETCHMODE_ORDERED, DB_FETCHMODE_ASSOC
     *                               or DB_FETCHMODE_OBJECT
     * @param string $object_class  the class name of the object to be returned
     *                               by the fetch methods when the
     *                               DB_FETCHMODE_OBJECT mode is selected.
     *                               If no class is specified by default a cast
     *                               to object from the assoc array row will be
     *                               done.  There is also the posibility to use
     *                               and extend the 'DB_row' class.
     *
     * @see DB_FETCHMODE_ORDERED, DB_FETCHMODE_ASSOC, DB_FETCHMODE_OBJECT
     */
    function setFetchMode($fetchmode, $object_class = 'stdClass')
    {
        switch ($fetchmode) {
            case DB_FETCHMODE_OBJECT:
                $this->fetchmode_object_class = $object_class;
            case DB_FETCHMODE_ORDERED:
            case DB_FETCHMODE_ASSOC:
                $this->fetchmode = $fetchmode;
                break;
            default:
                return $this->raiseError('invalid fetchmode mode');
        }
    }

    // }}}
    // {{{ setOption()

    /**
     * Sets run-time configuration options for PEAR DB
     *
     * Options, their data types, default values and description:
     * <ul>
     * <li>
     * <var>autofree</var> <kbd>boolean</kbd> = <samp>false</samp>
     *      <br />should results be freed automatically when there are no
     *            more rows?
     * </li><li>
     * <var>result_buffering</var> <kbd>integer</kbd> = <samp>500</samp>
     *      <br />how many rows of the result set should be buffered?
     *      <br />In mysql: mysql_unbuffered_query() is used instead of
     *            mysql_query() if this value is 0.  (Release 1.7.0)
     *      <br />In oci8: this value is passed to ocisetprefetch().
     *            (Release 1.7.0)
     * </li><li>
     * <var>debug</var> <kbd>integer</kbd> = <samp>0</samp>
     *      <br />debug level
     * </li><li>
     * <var>persistent</var> <kbd>boolean</kbd> = <samp>false</samp>
     *      <br />should the connection be persistent?
     * </li><li>
     * <var>portability</var> <kbd>integer</kbd> = <samp>DB_PORTABILITY_NONE</samp>
     *      <br />portability mode constant (see below)
     * </li><li>
     * <var>seqname_format</var> <kbd>string</kbd> = <samp>%s_seq</samp>
     *      <br />the sprintf() format string used on sequence names.  This
     *            format is applied to sequence names passed to
     *            createSequence(), nextID() and dropSequence().
     * </li><li>
     * <var>ssl</var> <kbd>boolean</kbd> = <samp>false</samp>
     *      <br />use ssl to connect?
     * </li>
     * </ul>
     *
     * -----------------------------------------
     *
     * PORTABILITY MODES
     *
     * These modes are bitwised, so they can be combined using <kbd>|</kbd>
     * and removed using <kbd>^</kbd>.  See the examples section below on how
     * to do this.
     *
     * <samp>DB_PORTABILITY_NONE</samp>
     * turn off all portability features
     *
     * This mode gets automatically turned on if the deprecated
     * <var>optimize</var> option gets set to <samp>performance</samp>.
     *
     *
     * <samp>DB_PORTABILITY_LOWERCASE</samp>
     * convert names of tables and fields to lower case when using
     * <kbd>get*()</kbd>, <kbd>fetch*()</kbd> and <kbd>tableInfo()</kbd>
     *
     * This mode gets automatically turned on in the following databases
     * if the deprecated option <var>optimize</var> gets set to
     * <samp>portability</samp>:
     * + oci8
     *
     *
     * <samp>DB_PORTABILITY_RTRIM</samp>
     * right trim the data output by <kbd>get*()</kbd> <kbd>fetch*()</kbd>
     *
     *
     * <samp>DB_PORTABILITY_DELETE_COUNT</samp>
     * force reporting the number of rows deleted
     *
     * Some DBMS's don't count the number of rows deleted when performing
     * simple <kbd>DELETE FROM tablename</kbd> queries.  This portability
     * mode tricks such DBMS's into telling the count by adding
     * <samp>WHERE 1=1</samp> to the end of <kbd>DELETE</kbd> queries.
     *
     * This mode gets automatically turned on in the following databases
     * if the deprecated option <var>optimize</var> gets set to
     * <samp>portability</samp>:
     * + fbsql
     * + mysql
     * + mysqli
     * + sqlite
     *
     *
     * <samp>DB_PORTABILITY_NUMROWS</samp>
     * enable hack that makes <kbd>numRows()</kbd> work in Oracle
     *
     * This mode gets automatically turned on in the following databases
     * if the deprecated option <var>optimize</var> gets set to
     * <samp>portability</samp>:
     * + oci8
     *
     *
     * <samp>DB_PORTABILITY_ERRORS</samp>
     * makes certain error messages in certain drivers compatible
     * with those from other DBMS's
     *
     * + mysql, mysqli:  change unique/primary key constraints
     *   DB_ERROR_ALREADY_EXISTS -> DB_ERROR_CONSTRAINT
     *
     * + odbc(access):  MS's ODBC driver reports 'no such field' as code
     *   07001, which means 'too few parameters.'  When this option is on
     *   that code gets mapped to DB_ERROR_NOSUCHFIELD.
     *   DB_ERROR_MISMATCH -> DB_ERROR_NOSUCHFIELD
     *
     * <samp>DB_PORTABILITY_NULL_TO_EMPTY</samp>
     * convert null values to empty strings in data output by get*() and
     * fetch*().  Needed because Oracle considers empty strings to be null,
     * while most other DBMS's know the difference between empty and null.
     *
     *
     * <samp>DB_PORTABILITY_ALL</samp>
     * turn on all portability features
     *
     * -----------------------------------------
     *
     * Example 1. Simple setOption() example
     * <code>
     * $db->setOption('autofree', true);
     * </code>
     *
     * Example 2. Portability for lowercasing and trimming
     * <code>
     * $db->setOption('portability',
     *                 DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_RTRIM);
     * </code>
     *
     * Example 3. All portability options except trimming
     * <code>
     * $db->setOption('portability',
     *                 DB_PORTABILITY_ALL ^ DB_PORTABILITY_RTRIM);
     * </code>
     *
     * @param string $option option name
     * @param mixed  $value value for the option
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     *
     * @see DB_common::$options
     */
    function setOption($option, $value)
    {
        if (isset($this->options[$option])) {
            $this->options[$option] = $value;

            /*
             * Backwards compatibility check for the deprecated 'optimize'
             * option.  Done here in case settings change after connecting.
             */
            if ($option == 'optimize') {
                if ($value == 'portability') {
                    switch ($this->phptype) {
                        case 'oci8':
                            $this->options['portability'] =
                                    DB_PORTABILITY_LOWERCASE |
                                    DB_PORTABILITY_NUMROWS;
                            break;
                        case 'fbsql':
                        case 'mysql':
                        case 'mysqli':
                        case 'sqlite':
                            $this->options['portability'] =
                                    DB_PORTABILITY_DELETE_COUNT;
                            break;
                    }
                } else {
                    $this->options['portability'] = DB_PORTABILITY_NONE;
                }
            }

            return DB_OK;
        }
        return $this->raiseError("unknown option $option");
    }

    // }}}
    // {{{ getOption()

    /**
     * Returns the value of an option
     *
     * @param string $option  the option name you're curious about
     *
     * @return mixed  the option's value
     */
    function getOption($option)
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }
        return $this->raiseError("unknown option $option");
    }

    // }}}
    // {{{ prepare()

    /**
     * Prepares a query for multiple execution with execute()
     *
     * Creates a query that can be run multiple times.  Each time it is run,
     * the placeholders, if any, will be replaced by the contents of
     * execute()'s $data argument.
     *
     * Three types of placeholders can be used:
     *   + <kbd>?</kbd>  scalar value (i.e. strings, integers).  The system
     *                   will automatically quote and escape the data.
     *   + <kbd>!</kbd>  value is inserted 'as is'
     *   + <kbd>&</kbd>  requires a file name.  The file's contents get
     *                   inserted into the query (i.e. saving binary
     *                   data in a db)
     *
     * Example 1.
     * <code>
     * $sth = $db->prepare('INSERT INTO tbl (a, b, c) VALUES (?, !, &)');
     * $data = array(
     *     "John's text",
     *     "'it''s good'",
     *     'filename.txt'
     * );
     * $res = $db->execute($sth, $data);
     * </code>
     *
     * Use backslashes to escape placeholder characters if you don't want
     * them to be interpreted as placeholders:
     * <pre>
     *    "UPDATE foo SET col=? WHERE col='over \& under'"
     * </pre>
     *
     * With some database backends, this is emulated.
     *
     * {@internal ibase and oci8 have their own prepare() methods.}}
     *
     * @param string $query  the query to be prepared
     *
     * @return mixed  DB statement resource on success. A DB_Error object
     *                 on failure.
     *
     * @see DB_common::execute()
     */
    function prepare($query)
    {
        $tokens   = preg_split('/((?<!\\\)[&?!])/', $query, -1,
                               PREG_SPLIT_DELIM_CAPTURE);
        $token     = 0;
        $types     = array();
        $newtokens = array();

        foreach ($tokens as $val) {
            switch ($val) {
                case '?':
                    $types[$token++] = DB_PARAM_SCALAR;
                    break;
                case '&':
                    $types[$token++] = DB_PARAM_OPAQUE;
                    break;
                case '!':
                    $types[$token++] = DB_PARAM_MISC;
                    break;
                default:
                    $newtokens[] = preg_replace('/\\\([&?!])/', "\\1", $val);
            }
        }

        $this->prepare_tokens[] = &$newtokens;
        end($this->prepare_tokens);

        $k = key($this->prepare_tokens);
        $this->prepare_types[$k] = $types;
        $this->prepared_queries[$k] = implode(' ', $newtokens);

        return $k;
    }

    // }}}
    // {{{ autoPrepare()

    /**
     * Automaticaly generates an insert or update query and pass it to prepare()
     *
     * @param string $table         the table name
     * @param array  $table_fields  the array of field names
     * @param int    $mode          a type of query to make:
     *                               DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE
     * @param string $where         for update queries: the WHERE clause to
     *                               append to the SQL statement.  Don't
     *                               include the "WHERE" keyword.
     *
     * @return resource  the query handle
     *
     * @uses DB_common::prepare(), DB_common::buildManipSQL()
     */
    function autoPrepare($table, $table_fields, $mode = DB_AUTOQUERY_INSERT,
                         $where = false)
    {
        $query = $this->buildManipSQL($table, $table_fields, $mode, $where);
        if (DB::isError($query)) {
            return $query;
        }
        return $this->prepare($query);
    }

    // }}}
    // {{{ autoExecute()

    /**
     * Automaticaly generates an insert or update query and call prepare()
     * and execute() with it
     *
     * @param string $table         the table name
     * @param array  $fields_values the associative array where $key is a
     *                               field name and $value its value
     * @param int    $mode          a type of query to make:
     *                               DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE
     * @param string $where         for update queries: the WHERE clause to
     *                               append to the SQL statement.  Don't
     *                               include the "WHERE" keyword.
     *
     * @return mixed  a new DB_result object for successful SELECT queries
     *                 or DB_OK for successul data manipulation queries.
     *                 A DB_Error object on failure.
     *
     * @uses DB_common::autoPrepare(), DB_common::execute()
     */
    function autoExecute($table, $fields_values, $mode = DB_AUTOQUERY_INSERT,
                         $where = false)
    {
        $sth = $this->autoPrepare($table, array_keys($fields_values), $mode,
                                  $where);
        if (DB::isError($sth)) {
            return $sth;
        }
        $ret =& $this->execute($sth, array_values($fields_values));
        $this->freePrepared($sth);
        return $ret;

    }

    // }}}
    // {{{ buildManipSQL()

    /**
     * Produces an SQL query string for autoPrepare()
     *
     * Example:
     * <pre>
     * buildManipSQL('table_sql', array('field1', 'field2', 'field3'),
     *               DB_AUTOQUERY_INSERT);
     * </pre>
     *
     * That returns
     * <samp>
     * INSERT INTO table_sql (field1,field2,field3) VALUES (?,?,?)
     * </samp>
     *
     * NOTES:
     *   - This belongs more to a SQL Builder class, but this is a simple
     *     facility.
     *   - Be carefull! If you don't give a $where param with an UPDATE
     *     query, all the records of the table will be updated!
     *
     * @param string $table         the table name
     * @param array  $table_fields  the array of field names
     * @param int    $mode          a type of query to make:
     *                               DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE
     * @param string $where         for update queries: the WHERE clause to
     *                               append to the SQL statement.  Don't
     *                               include the "WHERE" keyword.
     *
     * @return string  the sql query for autoPrepare()
     */
    function buildManipSQL($table, $table_fields, $mode, $where = false)
    {
        if (count($table_fields) == 0) {
            return $this->raiseError(DB_ERROR_NEED_MORE_DATA);
        }
        $first = true;
        switch ($mode) {
            case DB_AUTOQUERY_INSERT:
                $values = '';
                $names = '';
                foreach ($table_fields as $value) {
                    if ($first) {
                        $first = false;
                    } else {
                        $names .= ',';
                        $values .= ',';
                    }
                    $names .= $value;
                    $values .= '?';
                }
                return "INSERT INTO $table ($names) VALUES ($values)";
            case DB_AUTOQUERY_UPDATE:
                $set = '';
                foreach ($table_fields as $value) {
                    if ($first) {
                        $first = false;
                    } else {
                        $set .= ',';
                    }
                    $set .= "$value = ?";
                }
                $sql = "UPDATE $table SET $set";
                if ($where) {
                    $sql .= " WHERE $where";
                }
                return $sql;
            default:
                return $this->raiseError(DB_ERROR_SYNTAX);
        }
    }

    // }}}
    // {{{ execute()

    /**
     * Executes a DB statement prepared with prepare()
     *
     * Example 1.
     * <code>
     * $sth = $db->prepare('INSERT INTO tbl (a, b, c) VALUES (?, !, &)');
     * $data = array(
     *     "John's text",
     *     "'it''s good'",
     *     'filename.txt'
     * );
     * $res =& $db->execute($sth, $data);
     * </code>
     *
     * @param resource $stmt  a DB statement resource returned from prepare()
     * @param mixed    $data  array, string or numeric data to be used in
     *                         execution of the statement.  Quantity of items
     *                         passed must match quantity of placeholders in
     *                         query:  meaning 1 placeholder for non-array
     *                         parameters or 1 placeholder per array element.
     *
     * @return mixed  a new DB_result object for successful SELECT queries
     *                 or DB_OK for successul data manipulation queries.
     *                 A DB_Error object on failure.
     *
     * {@internal ibase and oci8 have their own execute() methods.}}
     *
     * @see DB_common::prepare()
     */
    function &execute($stmt, $data = array())
    {
        $realquery = $this->executeEmulateQuery($stmt, $data);
        if (DB::isError($realquery)) {
            return $realquery;
        }
        $result = $this->simpleQuery($realquery);

        if ($result === DB_OK || DB::isError($result)) {
            return $result;
        } else {
            $tmp =& new DB_result($this, $result);
            return $tmp;
        }
    }

    // }}}
    // {{{ executeEmulateQuery()

    /**
     * Emulates executing prepared statements if the DBMS not support them
     *
     * @param resource $stmt  a DB statement resource returned from execute()
     * @param mixed    $data  array, string or numeric data to be used in
     *                         execution of the statement.  Quantity of items
     *                         passed must match quantity of placeholders in
     *                         query:  meaning 1 placeholder for non-array
     *                         parameters or 1 placeholder per array element.
     *
     * @return mixed  a string containing the real query run when emulating
     *                 prepare/execute.  A DB_Error object on failure.
     *
     * @access protected
     * @see DB_common::execute()
     */
    function executeEmulateQuery($stmt, $data = array())
    {
        $stmt = (int)$stmt;
        $data = (array)$data;
        $this->last_parameters = $data;

        if (count($this->prepare_types[$stmt]) != count($data)) {
            $this->last_query = $this->prepared_queries[$stmt];
            return $this->raiseError(DB_ERROR_MISMATCH);
        }

        $realquery = $this->prepare_tokens[$stmt][0];

        $i = 0;
        foreach ($data as $value) {
            if ($this->prepare_types[$stmt][$i] == DB_PARAM_SCALAR) {
                $realquery .= $this->quoteSmart($value);
            } elseif ($this->prepare_types[$stmt][$i] == DB_PARAM_OPAQUE) {
                $fp = @fopen($value, 'rb');
                if (!$fp) {
                    return $this->raiseError(DB_ERROR_ACCESS_VIOLATION);
                }
                $realquery .= $this->quoteSmart(fread($fp, filesize($value)));
                fclose($fp);
            } else {
                $realquery .= $value;
            }

            $realquery .= $this->prepare_tokens[$stmt][++$i];
        }

        return $realquery;
    }

    // }}}
    // {{{ executeMultiple()

    /**
     * Performs several execute() calls on the same statement handle
     *
     * $data must be an array indexed numerically
     * from 0, one execute call is done for every "row" in the array.
     *
     * If an error occurs during execute(), executeMultiple() does not
     * execute the unfinished rows, but rather returns that error.
     *
     * @param resource $stmt  query handle from prepare()
     * @param array    $data  numeric array containing the
     *                         data to insert into the query
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     *
     * @see DB_common::prepare(), DB_common::execute()
     */
    function executeMultiple($stmt, $data)
    {
        foreach ($data as $value) {
            $res =& $this->execute($stmt, $value);
            if (DB::isError($res)) {
                return $res;
            }
        }
        return DB_OK;
    }

    // }}}
    // {{{ freePrepared()

    /**
     * Frees the internal resources associated with a prepared query
     *
     * @param resource $stmt           the prepared statement's PHP resource
     * @param bool     $free_resource  should the PHP resource be freed too?
     *                                  Use false if you need to get data
     *                                  from the result set later.
     *
     * @return bool  TRUE on success, FALSE if $result is invalid
     *
     * @see DB_common::prepare()
     */
    function freePrepared($stmt, $free_resource = true)
    {
        $stmt = (int)$stmt;
        if (isset($this->prepare_tokens[$stmt])) {
            unset($this->prepare_tokens[$stmt]);
            unset($this->prepare_types[$stmt]);
            unset($this->prepared_queries[$stmt]);
            return true;
        }
        return false;
    }

    // }}}
    // {{{ modifyQuery()

    /**
     * Changes a query string for various DBMS specific reasons
     *
     * It is defined here to ensure all drivers have this method available.
     *
     * @param string $query  the query string to modify
     *
     * @return string  the modified query string
     *
     * @access protected
     * @see DB_mysql::modifyQuery(), DB_oci8::modifyQuery(),
     *      DB_sqlite::modifyQuery()
     */
    function modifyQuery($query)
    {
        return $query;
    }

    // }}}
    // {{{ modifyLimitQuery()

    /**
     * Adds LIMIT clauses to a query string according to current DBMS standards
     *
     * It is defined here to assure that all implementations
     * have this method defined.
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
        return $query;
    }

    // }}}
    // {{{ query()

    /**
     * Sends a query to the database server
     *
     * The query string can be either a normal statement to be sent directly
     * to the server OR if <var>$params</var> are passed the query can have
     * placeholders and it will be passed through prepare() and execute().
     *
     * @param string $query   the SQL query or the statement to prepare
     * @param mixed  $params  array, string or numeric data to be used in
     *                         execution of the statement.  Quantity of items
     *                         passed must match quantity of placeholders in
     *                         query:  meaning 1 placeholder for non-array
     *                         parameters or 1 placeholder per array element.
     *
     * @return mixed  a new DB_result object for successful SELECT queries
     *                 or DB_OK for successul data manipulation queries.
     *                 A DB_Error object on failure.
     *
     * @see DB_result, DB_common::prepare(), DB_common::execute()
     */
    function &query($query, $params = array())
    {
        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);
            if (DB::isError($sth)) {
                return $sth;
            }
            $ret =& $this->execute($sth, $params);
            $this->freePrepared($sth, false);
            return $ret;
        } else {
            $this->last_parameters = array();
            $result = $this->simpleQuery($query);
            if ($result === DB_OK || DB::isError($result)) {
                return $result;
            } else {
                $tmp =& new DB_result($this, $result);
                return $tmp;
            }
        }
    }

    // }}}
    // {{{ limitQuery()

    /**
     * Generates and executes a LIMIT query
     *
     * @param string $query   the query
     * @param intr   $from    the row to start to fetching (0 = the first row)
     * @param int    $count   the numbers of rows to fetch
     * @param mixed  $params  array, string or numeric data to be used in
     *                         execution of the statement.  Quantity of items
     *                         passed must match quantity of placeholders in
     *                         query:  meaning 1 placeholder for non-array
     *                         parameters or 1 placeholder per array element.
     *
     * @return mixed  a new DB_result object for successful SELECT queries
     *                 or DB_OK for successul data manipulation queries.
     *                 A DB_Error object on failure.
     */
    function &limitQuery($query, $from, $count, $params = array())
    {
        $query = $this->modifyLimitQuery($query, $from, $count, $params);
        if (DB::isError($query)){
            return $query;
        }
        $result =& $this->query($query, $params);
        if (is_a($result, 'DB_result')) {
            $result->setOption('limit_from', $from);
            $result->setOption('limit_count', $count);
        }
        return $result;
    }

    // }}}
    // {{{ getOne()

    /**
     * Fetches the first column of the first row from a query result
     *
     * Takes care of doing the query and freeing the results when finished.
     *
     * @param string $query   the SQL query
     * @param mixed  $params  array, string or numeric data to be used in
     *                         execution of the statement.  Quantity of items
     *                         passed must match quantity of placeholders in
     *                         query:  meaning 1 placeholder for non-array
     *                         parameters or 1 placeholder per array element.
     *
     * @return mixed  the returned value of the query.
     *                 A DB_Error object on failure.
     */
    function &getOne($query, $params = array())
    {
        $params = (array)$params;
        // modifyLimitQuery() would be nice here, but it causes BC issues
        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);
            if (DB::isError($sth)) {
                return $sth;
            }
            $res =& $this->execute($sth, $params);
            $this->freePrepared($sth);
        } else {
            $res =& $this->query($query);
        }

        if (DB::isError($res)) {
            return $res;
        }

        $err = $res->fetchInto($row, DB_FETCHMODE_ORDERED);
        $res->free();

        if ($err !== DB_OK) {
            return $err;
        }

        return $row[0];
    }

    // }}}
    // {{{ getRow()

    /**
     * Fetches the first row of data returned from a query result
     *
     * Takes care of doing the query and freeing the results when finished.
     *
     * @param string $query   the SQL query
     * @param mixed  $params  array, string or numeric data to be used in
     *                         execution of the statement.  Quantity of items
     *                         passed must match quantity of placeholders in
     *                         query:  meaning 1 placeholder for non-array
     *                         parameters or 1 placeholder per array element.
     * @param int $fetchmode  the fetch mode to use
     *
     * @return array  the first row of results as an array.
     *                 A DB_Error object on failure.
     */
    function &getRow($query, $params = array(),
                     $fetchmode = DB_FETCHMODE_DEFAULT)
    {
        // compat check, the params and fetchmode parameters used to
        // have the opposite order
        if (!is_array($params)) {
            if (is_array($fetchmode)) {
                if ($params === null) {
                    $tmp = DB_FETCHMODE_DEFAULT;
                } else {
                    $tmp = $params;
                }
                $params = $fetchmode;
                $fetchmode = $tmp;
            } elseif ($params !== null) {
                $fetchmode = $params;
                $params = array();
            }
        }
        // modifyLimitQuery() would be nice here, but it causes BC issues
        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);
            if (DB::isError($sth)) {
                return $sth;
            }
            $res =& $this->execute($sth, $params);
            $this->freePrepared($sth);
        } else {
            $res =& $this->query($query);
        }

        if (DB::isError($res)) {
            return $res;
        }

        $err = $res->fetchInto($row, $fetchmode);

        $res->free();

        if ($err !== DB_OK) {
            return $err;
        }

        return $row;
    }

    // }}}
    // {{{ getCol()

    /**
     * Fetches a single column from a query result and returns it as an
     * indexed array
     *
     * @param string $query   the SQL query
     * @param mixed  $col     which column to return (integer [column number,
     *                         starting at 0] or string [column name])
     * @param mixed  $params  array, string or numeric data to be used in
     *                         execution of the statement.  Quantity of items
     *                         passed must match quantity of placeholders in
     *                         query:  meaning 1 placeholder for non-array
     *                         parameters or 1 placeholder per array element.
     *
     * @return array  the results as an array.  A DB_Error object on failure.
     *
     * @see DB_common::query()
     */
    function &getCol($query, $col = 0, $params = array())
    {
        $params = (array)$params;
        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);

            if (DB::isError($sth)) {
                return $sth;
            }

            $res =& $this->execute($sth, $params);
            $this->freePrepared($sth);
        } else {
            $res =& $this->query($query);
        }

        if (DB::isError($res)) {
            return $res;
        }

        $fetchmode = is_int($col) ? DB_FETCHMODE_ORDERED : DB_FETCHMODE_ASSOC;

        if (!is_array($row = $res->fetchRow($fetchmode))) {
            $ret = array();
        } else {
            if (!array_key_exists($col, $row)) {
                $ret =& $this->raiseError(DB_ERROR_NOSUCHFIELD);
            } else {
                $ret = array($row[$col]);
                while (is_array($row = $res->fetchRow($fetchmode))) {
                    $ret[] = $row[$col];
                }
            }
        }

        $res->free();

        if (DB::isError($row)) {
            $ret = $row;
        }

        return $ret;
    }

    // }}}
    // {{{ getAssoc()

    /**
     * Fetches an entire query result and returns it as an
     * associative array using the first column as the key
     *
     * If the result set contains more than two columns, the value
     * will be an array of the values from column 2-n.  If the result
     * set contains only two columns, the returned value will be a
     * scalar with the value of the second column (unless forced to an
     * array with the $force_array parameter).  A DB error code is
     * returned on errors.  If the result set contains fewer than two
     * columns, a DB_ERROR_TRUNCATED error is returned.
     *
     * For example, if the table "mytable" contains:
     *
     * <pre>
     *  ID      TEXT       DATE
     * --------------------------------
     *  1       'one'      944679408
     *  2       'two'      944679408
     *  3       'three'    944679408
     * </pre>
     *
     * Then the call getAssoc('SELECT id,text FROM mytable') returns:
     * <pre>
     *   array(
     *     '1' => 'one',
     *     '2' => 'two',
     *     '3' => 'three',
     *   )
     * </pre>
     *
     * ...while the call getAssoc('SELECT id,text,date FROM mytable') returns:
     * <pre>
     *   array(
     *     '1' => array('one', '944679408'),
     *     '2' => array('two', '944679408'),
     *     '3' => array('three', '944679408')
     *   )
     * </pre>
     *
     * If the more than one row occurs with the same value in the
     * first column, the last row overwrites all previous ones by
     * default.  Use the $group parameter if you don't want to
     * overwrite like this.  Example:
     *
     * <pre>
     * getAssoc('SELECT category,id,name FROM mytable', false, null,
     *          DB_FETCHMODE_ASSOC, true) returns:
     *
     *   array(
     *     '1' => array(array('id' => '4', 'name' => 'number four'),
     *                  array('id' => '6', 'name' => 'number six')
     *            ),
     *     '9' => array(array('id' => '4', 'name' => 'number four'),
     *                  array('id' => '6', 'name' => 'number six')
     *            )
     *   )
     * </pre>
     *
     * Keep in mind that database functions in PHP usually return string
     * values for results regardless of the database's internal type.
     *
     * @param string $query        the SQL query
     * @param bool   $force_array  used only when the query returns
     *                              exactly two columns.  If true, the values
     *                              of the returned array will be one-element
     *                              arrays instead of scalars.
     * @param mixed  $params       array, string or numeric data to be used in
     *                              execution of the statement.  Quantity of
     *                              items passed must match quantity of
     *                              placeholders in query:  meaning 1
     *                              placeholder for non-array parameters or
     *                              1 placeholder per array element.
     * @param int   $fetchmode     the fetch mode to use
     * @param bool  $group         if true, the values of the returned array
     *                              is wrapped in another array.  If the same
     *                              key value (in the first column) repeats
     *                              itself, the values will be appended to
     *                              this array instead of overwriting the
     *                              existing values.
     *
     * @return array  the associative array containing the query results.
     *                A DB_Error object on failure.
     */
    function &getAssoc($query, $force_array = false, $params = array(),
                       $fetchmode = DB_FETCHMODE_DEFAULT, $group = false)
    {
        $params = (array)$params;
        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);

            if (DB::isError($sth)) {
                return $sth;
            }

            $res =& $this->execute($sth, $params);
            $this->freePrepared($sth);
        } else {
            $res =& $this->query($query);
        }

        if (DB::isError($res)) {
            return $res;
        }
        if ($fetchmode == DB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        $cols = $res->numCols();

        if ($cols < 2) {
            $tmp =& $this->raiseError(DB_ERROR_TRUNCATED);
            return $tmp;
        }

        $results = array();

        if ($cols > 2 || $force_array) {
            // return array values
            // XXX this part can be optimized
            if ($fetchmode == DB_FETCHMODE_ASSOC) {
                while (is_array($row = $res->fetchRow(DB_FETCHMODE_ASSOC))) {
                    reset($row);
                    $key = current($row);
                    unset($row[key($row)]);
                    if ($group) {
                        $results[$key][] = $row;
                    } else {
                        $results[$key] = $row;
                    }
                }
            } elseif ($fetchmode == DB_FETCHMODE_OBJECT) {
                while ($row = $res->fetchRow(DB_FETCHMODE_OBJECT)) {
                    $arr = get_object_vars($row);
                    $key = current($arr);
                    if ($group) {
                        $results[$key][] = $row;
                    } else {
                        $results[$key] = $row;
                    }
                }
            } else {
                while (is_array($row = $res->fetchRow(DB_FETCHMODE_ORDERED))) {
                    // we shift away the first element to get
                    // indices running from 0 again
                    $key = array_shift($row);
                    if ($group) {
                        $results[$key][] = $row;
                    } else {
                        $results[$key] = $row;
                    }
                }
            }
            if (DB::isError($row)) {
                $results = $row;
            }
        } else {
            // return scalar values
            while (is_array($row = $res->fetchRow(DB_FETCHMODE_ORDERED))) {
                if ($group) {
                    $results[$row[0]][] = $row[1];
                } else {
                    $results[$row[0]] = $row[1];
                }
            }
            if (DB::isError($row)) {
                $results = $row;
            }
        }

        $res->free();

        return $results;
    }

    // }}}
    // {{{ getAll()

    /**
     * Fetches all of the rows from a query result
     *
     * @param string $query      the SQL query
     * @param mixed  $params     array, string or numeric data to be used in
     *                            execution of the statement.  Quantity of
     *                            items passed must match quantity of
     *                            placeholders in query:  meaning 1
     *                            placeholder for non-array parameters or
     *                            1 placeholder per array element.
     * @param int    $fetchmode  the fetch mode to use:
     *                            + DB_FETCHMODE_ORDERED
     *                            + DB_FETCHMODE_ASSOC
     *                            + DB_FETCHMODE_ORDERED | DB_FETCHMODE_FLIPPED
     *                            + DB_FETCHMODE_ASSOC | DB_FETCHMODE_FLIPPED
     *
     * @return array  the nested array.  A DB_Error object on failure.
     */
    function &getAll($query, $params = array(),
                     $fetchmode = DB_FETCHMODE_DEFAULT)
    {
        // compat check, the params and fetchmode parameters used to
        // have the opposite order
        if (!is_array($params)) {
            if (is_array($fetchmode)) {
                if ($params === null) {
                    $tmp = DB_FETCHMODE_DEFAULT;
                } else {
                    $tmp = $params;
                }
                $params = $fetchmode;
                $fetchmode = $tmp;
            } elseif ($params !== null) {
                $fetchmode = $params;
                $params = array();
            }
        }

        if (sizeof($params) > 0) {
            $sth = $this->prepare($query);

            if (DB::isError($sth)) {
                return $sth;
            }

            $res =& $this->execute($sth, $params);
            $this->freePrepared($sth);
        } else {
            $res =& $this->query($query);
        }

        if ($res === DB_OK || DB::isError($res)) {
            return $res;
        }

        $results = array();
        while (DB_OK === $res->fetchInto($row, $fetchmode)) {
            if ($fetchmode & DB_FETCHMODE_FLIPPED) {
                foreach ($row as $key => $val) {
                    $results[$key][] = $val;
                }
            } else {
                $results[] = $row;
            }
        }

        $res->free();

        if (DB::isError($row)) {
            $tmp =& $this->raiseError($row);
            return $tmp;
        }
        return $results;
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
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
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
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
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
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ numRows()

    /**
     * Determines the number of rows in a query result
     *
     * @param resource $result  the query result idenifier produced by PHP
     *
     * @return int  the number of rows.  A DB_Error object on failure.
     */
    function numRows($result)
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
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
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ getSequenceName()

    /**
     * Generates the name used inside the database for a sequence
     *
     * The createSequence() docblock contains notes about storing sequence
     * names.
     *
     * @param string $sqn  the sequence's public name
     *
     * @return string  the sequence's name in the backend
     *
     * @access protected
     * @see DB_common::createSequence(), DB_common::dropSequence(),
     *      DB_common::nextID(), DB_common::setOption()
     */
    function getSequenceName($sqn)
    {
        return sprintf($this->getOption('seqname_format'),
                       preg_replace('/[^a-z0-9_.]/i', '_', $sqn));
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
     * @see DB_common::createSequence(), DB_common::dropSequence(),
     *      DB_common::getSequenceName()
     */
    function nextId($seq_name, $ondemand = true)
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ createSequence()

    /**
     * Creates a new sequence
     *
     * The name of a given sequence is determined by passing the string
     * provided in the <var>$seq_name</var> argument through PHP's sprintf()
     * function using the value from the <var>seqname_format</var> option as
     * the sprintf()'s format argument.
     *
     * <var>seqname_format</var> is set via setOption().
     *
     * @param string $seq_name  name of the new sequence
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     *
     * @see DB_common::dropSequence(), DB_common::getSequenceName(),
     *      DB_common::nextID()
     */
    function createSequence($seq_name)
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
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
     * @see DB_common::createSequence(), DB_common::getSequenceName(),
     *      DB_common::nextID()
     */
    function dropSequence($seq_name)
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ raiseError()

    /**
     * Communicates an error and invoke error callbacks, etc
     *
     * Basically a wrapper for PEAR::raiseError without the message string.
     *
     * @param mixed   integer error code, or a PEAR error object (all
     *                 other parameters are ignored if this parameter is
     *                 an object
     * @param int     error mode, see PEAR_Error docs
     * @param mixed   if error mode is PEAR_ERROR_TRIGGER, this is the
     *                 error level (E_USER_NOTICE etc).  If error mode is
     *                 PEAR_ERROR_CALLBACK, this is the callback function,
     *                 either as a function name, or as an array of an
     *                 object and method name.  For other error modes this
     *                 parameter is ignored.
     * @param string  extra debug information.  Defaults to the last
     *                 query and native error code.
     * @param mixed   native error code, integer or string depending the
     *                 backend
     *
     * @return object  the PEAR_Error object
     *
     * @see PEAR_Error
     */
    function &raiseError($code = DB_ERROR, $mode = null, $options = null,
                         $userinfo = null, $nativecode = null)
    {
        // The error is yet a DB error object
        if (is_object($code)) {
            // because we the static PEAR::raiseError, our global
            // handler should be used if it is set
            if ($mode === null && !empty($this->_default_error_mode)) {
                $mode    = $this->_default_error_mode;
                $options = $this->_default_error_options;
            }
            $tmp = PEAR::raiseError($code, null, $mode, $options,
                                    null, null, true);
            return $tmp;
        }

        if ($userinfo === null) {
            $userinfo = $this->last_query;
        }

        if ($nativecode) {
            $userinfo .= ' [nativecode=' . trim($nativecode) . ']';
        } else {
            $userinfo .= ' [DB Error: ' . DB::errorMessage($code) . ']';
        }

        $tmp = PEAR::raiseError(null, $code, $mode, $options, $userinfo,
                                'DB_Error', true);
        return $tmp;
    }

    // }}}
    // {{{ errorNative()

    /**
     * Gets the DBMS' native error code produced by the last query
     *
     * @return mixed  the DBMS' error code.  A DB_Error object on failure.
     */
    function errorNative()
    {
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ errorCode()

    /**
     * Maps native error codes to DB's portable ones
     *
     * Uses the <var>$errorcode_map</var> property defined in each driver.
     *
     * @param string|int $nativecode  the error code returned by the DBMS
     *
     * @return int  the portable DB error code.  Return DB_ERROR if the
     *               current driver doesn't have a mapping for the
     *               $nativecode submitted.
     */
    function errorCode($nativecode)
    {
        if (isset($this->errorcode_map[$nativecode])) {
            return $this->errorcode_map[$nativecode];
        }
        // Fall back to DB_ERROR if there was no mapping.
        return DB_ERROR;
    }

    // }}}
    // {{{ errorMessage()

    /**
     * Maps a DB error code to a textual message
     *
     * @param integer $dbcode  the DB error code
     *
     * @return string  the error message corresponding to the error code
     *                  submitted.  FALSE if the error code is unknown.
     *
     * @see DB::errorMessage()
     */
    function errorMessage($dbcode)
    {
        return DB::errorMessage($this->errorcode_map[$dbcode]);
    }

    // }}}
    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set
     *
     * The format of the resulting array depends on which <var>$mode</var>
     * you select.  The sample output below is based on this query:
     * <pre>
     *    SELECT tblFoo.fldID, tblFoo.fldPhone, tblBar.fldId
     *    FROM tblFoo
     *    JOIN tblBar ON tblFoo.fldId = tblBar.fldId
     * </pre>
     *
     * <ul>
     * <li>
     *
     * <kbd>null</kbd> (default)
     *   <pre>
     *   [0] => Array (
     *       [table] => tblFoo
     *       [name] => fldId
     *       [type] => int
     *       [len] => 11
     *       [flags] => primary_key not_null
     *   )
     *   [1] => Array (
     *       [table] => tblFoo
     *       [name] => fldPhone
     *       [type] => string
     *       [len] => 20
     *       [flags] =>
     *   )
     *   [2] => Array (
     *       [table] => tblBar
     *       [name] => fldId
     *       [type] => int
     *       [len] => 11
     *       [flags] => primary_key not_null
     *   )
     *   </pre>
     *
     * </li><li>
     *
     * <kbd>DB_TABLEINFO_ORDER</kbd>
     *
     *   <p>In addition to the information found in the default output,
     *   a notation of the number of columns is provided by the
     *   <samp>num_fields</samp> element while the <samp>order</samp>
     *   element provides an array with the column names as the keys and
     *   their location index number (corresponding to the keys in the
     *   the default output) as the values.</p>
     *
     *   <p>If a result set has identical field names, the last one is
     *   used.</p>
     *
     *   <pre>
     *   [num_fields] => 3
     *   [order] => Array (
     *       [fldId] => 2
     *       [fldTrans] => 1
     *   )
     *   </pre>
     *
     * </li><li>
     *
     * <kbd>DB_TABLEINFO_ORDERTABLE</kbd>
     *
     *   <p>Similar to <kbd>DB_TABLEINFO_ORDER</kbd> but adds more
     *   dimensions to the array in which the table names are keys and
     *   the field names are sub-keys.  This is helpful for queries that
     *   join tables which have identical field names.</p>
     *
     *   <pre>
     *   [num_fields] => 3
     *   [ordertable] => Array (
     *       [tblFoo] => Array (
     *           [fldId] => 0
     *           [fldPhone] => 1
     *       )
     *       [tblBar] => Array (
     *           [fldId] => 2
     *       )
     *   )
     *   </pre>
     *
     * </li>
     * </ul>
     *
     * The <samp>flags</samp> element contains a space separated list
     * of extra information about the field.  This data is inconsistent
     * between DBMS's due to the way each DBMS works.
     *   + <samp>primary_key</samp>
     *   + <samp>unique_key</samp>
     *   + <samp>multiple_key</samp>
     *   + <samp>not_null</samp>
     *
     * Most DBMS's only provide the <samp>table</samp> and <samp>flags</samp>
     * elements if <var>$result</var> is a table name.  The following DBMS's
     * provide full information from queries:
     *   + fbsql
     *   + mysql
     *
     * If the 'portability' option has <samp>DB_PORTABILITY_LOWERCASE</samp>
     * turned on, the names of tables and fields will be lowercased.
     *
     * @param object|string  $result  DB_result object from a query or a
     *                                string containing the name of a table.
     *                                While this also accepts a query result
     *                                resource identifier, this behavior is
     *                                deprecated.
     * @param int  $mode   either unused or one of the tableInfo modes:
     *                     <kbd>DB_TABLEINFO_ORDERTABLE</kbd>,
     *                     <kbd>DB_TABLEINFO_ORDER</kbd> or
     *                     <kbd>DB_TABLEINFO_FULL</kbd> (which does both).
     *                     These are bitwise, so the first two can be
     *                     combined using <kbd>|</kbd>.
     *
     * @return array  an associative array with the information requested.
     *                 A DB_Error object on failure.
     *
     * @see DB_common::setOption()
     */
    function tableInfo($result, $mode = null)
    {
        /*
         * If the DB_<driver> class has a tableInfo() method, that one
         * overrides this one.  But, if the driver doesn't have one,
         * this method runs and tells users about that fact.
         */
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ getTables()

    /**
     * Lists the tables in the current database
     *
     * @return array  the list of tables.  A DB_Error object on failure.
     *
     * @deprecated Method deprecated some time before Release 1.2
     */
    function getTables()
    {
        return $this->getListOf('tables');
    }

    // }}}
    // {{{ getListOf()

    /**
     * Lists internal database information
     *
     * @param string $type  type of information being sought.
     *                       Common items being sought are:
     *                       tables, databases, users, views, functions
     *                       Each DBMS's has its own capabilities.
     *
     * @return array  an array listing the items sought.
     *                 A DB DB_Error object on failure.
     */
    function getListOf($type)
    {
        $sql = $this->getSpecialQuery($type);
        if ($sql === null) {
            $this->last_query = '';
            return $this->raiseError(DB_ERROR_UNSUPPORTED);
        } elseif (is_int($sql) || DB::isError($sql)) {
            // Previous error
            return $this->raiseError($sql);
        } elseif (is_array($sql)) {
            // Already the result
            return $sql;
        }
        // Launch this query
        return $this->getCol($sql);
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
        return $this->raiseError(DB_ERROR_UNSUPPORTED);
    }

    // }}}
    // {{{ _rtrimArrayValues()

    /**
     * Right-trims all strings in an array
     *
     * @param array $array  the array to be trimmed (passed by reference)
     *
     * @return void
     *
     * @access protected
     */
    function _rtrimArrayValues(&$array)
    {
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $array[$key] = rtrim($value);
            }
        }
    }

    // }}}
    // {{{ _convertNullArrayValuesToEmpty()

    /**
     * Converts all null values in an array to empty strings
     *
     * @param array  $array  the array to be de-nullified (passed by reference)
     *
     * @return void
     *
     * @access protected
     */
    function _convertNullArrayValuesToEmpty(&$array)
    {
        foreach ($array as $key => $value) {
            if (is_null($value)) {
                $array[$key] = '';
            }
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
