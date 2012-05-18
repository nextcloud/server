<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2011 Robin Appelman icewind1991@gmail.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * MDB2 SQLite3 driver
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Driver_sqlite3 extends MDB2_Driver_Common
{
    // {{{ properties
    public $string_quoting = array('start' => "'", 'end' => "'", 'escape' => "'", 'escape_pattern' => false);

    public $identifier_quoting = array('start' => '"', 'end' => '"', 'escape' => '"');

    public $_lasterror = '';

    public $fix_assoc_fields_names = false;

    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct();

        $this->phptype = 'sqlite3';
        $this->dbsyntax = 'sqlite';

        $this->supported['sequences'] = 'emulated';
        $this->supported['indexes'] = true;
        $this->supported['affected_rows'] = true;
        $this->supported['summary_functions'] = true;
        $this->supported['order_by_text'] = true;
        $this->supported['current_id'] = 'emulated';
        $this->supported['limit_queries'] = true;
        $this->supported['LOBs'] = true;
        $this->supported['replace'] = true;
        $this->supported['transactions'] = false;
        $this->supported['savepoints'] = false;
        $this->supported['sub_selects'] = true;
        $this->supported['triggers'] = true;
        $this->supported['auto_increment'] = true;
        $this->supported['primary_key'] = false; // requires alter table implementation
        $this->supported['result_introspection'] = false; // not implemented
        $this->supported['prepared_statements'] = true;
        $this->supported['identifier_quoting'] = true;
        $this->supported['pattern_escaping'] = false;
        $this->supported['new_link'] = false;

        $this->options['DBA_username'] = false;
        $this->options['DBA_password'] = false;
        $this->options['base_transaction_name'] = '___php_MDB2_sqlite_auto_commit_off';
        $this->options['fixed_float'] = 0;
        $this->options['database_path'] = '';
        $this->options['database_extension'] = '';
        $this->options['server_version'] = '';
        $this->options['max_identifiers_length'] = 128; //no real limit
    }

    // }}}
    // {{{ errorInfo()

    /**
     * This method is used to collect information about an error
     *
     * @param integer $error
     * @return array
     * @access public
     */
    function errorInfo($error = null)
    {
        $native_code = null;
        if ($this->connection) {
            $native_code = $this->connection->lastErrorCode();
        }
        $native_msg = $this->_lasterror
            ? html_entity_decode($this->_lasterror) : $this->connection->lastErrorMsg();

        // PHP 5.2+ prepends the function name to $php_errormsg, so we need
        // this hack to work around it, per bug #9599.
        $native_msg = preg_replace('/^sqlite[a-z_]+\(\)[^:]*: /', '', $native_msg);

        if (is_null($error)) {
            static $error_regexps;
            if (empty($error_regexps)) {
                $error_regexps = array(
                    '/^no such table:/' => MDB2_ERROR_NOSUCHTABLE,
                    '/^no such index:/' => MDB2_ERROR_NOT_FOUND,
                    '/^(table|index) .* already exists$/' => MDB2_ERROR_ALREADY_EXISTS,
                    '/PRIMARY KEY must be unique/i' => MDB2_ERROR_CONSTRAINT,
                    '/is not unique/' => MDB2_ERROR_CONSTRAINT,
                    '/columns .* are not unique/i' => MDB2_ERROR_CONSTRAINT,
                    '/uniqueness constraint failed/' => MDB2_ERROR_CONSTRAINT,
                    '/may not be NULL/' => MDB2_ERROR_CONSTRAINT_NOT_NULL,
                    '/^no such column:/' => MDB2_ERROR_NOSUCHFIELD,
                    '/no column named/' => MDB2_ERROR_NOSUCHFIELD,
                    '/column not present in both tables/i' => MDB2_ERROR_NOSUCHFIELD,
                    '/^near ".*": syntax error$/' => MDB2_ERROR_SYNTAX,
                    '/[0-9]+ values for [0-9]+ columns/i' => MDB2_ERROR_VALUE_COUNT_ON_ROW,
                 );
            }
            foreach ($error_regexps as $regexp => $code) {
                if (preg_match($regexp, $native_msg)) {
                    $error = $code;
                    break;
                }
            }
        }
        return array($error, $native_code, $native_msg);
    }

    // }}}
    // {{{ escape()

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
    public function escape($text, $escape_wildcards = false)
    {
		if($this->connection){
			return $this->connection->escapeString($text);
		}else{
			return str_replace("'","''",$text);//TODO; more
		}
    }

    // }}}
    // {{{ beginTransaction()

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
        $this->debug('Starting transaction/savepoint', __FUNCTION__, array('is_manip' => true, 'savepoint' => $savepoint));
        if (!is_null($savepoint)) {
            return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'savepoints are not supported', __FUNCTION__);
        } elseif ($this->in_transaction) {
            return MDB2_OK;  //nothing to do
        }
        if (!$this->destructor_registered && $this->opened_persistent) {
            $this->destructor_registered = true;
            register_shutdown_function('MDB2_closeOpenTransactions');
        }
        $query = 'BEGIN TRANSACTION '.$this->options['base_transaction_name'];
        $result =$this->_doQuery($query, true);
        if (PEAR::isError($result)) {
            return $result;
        }
        $this->in_transaction = true;
        return MDB2_OK;
    }

    // }}}
    // {{{ commit()

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
        if (!$this->in_transaction) {
            return $this->raiseError(MDB2_ERROR_INVALID, null, null,
                'commit/release savepoint cannot be done changes are auto committed', __FUNCTION__);
        }
        if (!is_null($savepoint)) {
            return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'savepoints are not supported', __FUNCTION__);
        }

        $query = 'COMMIT TRANSACTION '.$this->options['base_transaction_name'];
        $result =$this->_doQuery($query, true);
        if (PEAR::isError($result)) {
            return $result;
        }
        $this->in_transaction = false;
        return MDB2_OK;
    }

    // }}}
    // {{{

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
        if (!$this->in_transaction) {
            return $this->raiseError(MDB2_ERROR_INVALID, null, null,
                'rollback cannot be done changes are auto committed', __FUNCTION__);
        }
        if (!is_null($savepoint)) {
            return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'savepoints are not supported', __FUNCTION__);
        }

        $query = 'ROLLBACK TRANSACTION '.$this->options['base_transaction_name'];
        $result =$this->_doQuery($query, true);
        if (PEAR::isError($result)) {
            return $result;
        }
        $this->in_transaction = false;
        return MDB2_OK;
    }

    // }}}
    // {{{ function setTransactionIsolation()

    /**
     * Set the transacton isolation level.
     *
     * @param   string  standard isolation level
     *                  READ UNCOMMITTED (allows dirty reads)
     *                  READ COMMITTED (prevents dirty reads)
     *                  REPEATABLE READ (prevents nonrepeatable reads)
     *                  SERIALIZABLE (prevents phantom reads)
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     * @since   2.1.1
     */
    function setTransactionIsolation($isolation,$options=array())
    {
        $this->debug('Setting transaction isolation level', __FUNCTION__, array('is_manip' => true));
        switch ($isolation) {
        case 'READ UNCOMMITTED':
            $isolation = 0;
            break;
        case 'READ COMMITTED':
        case 'REPEATABLE READ':
        case 'SERIALIZABLE':
            $isolation = 1;
            break;
        default:
            return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'isolation level is not supported: '.$isolation, __FUNCTION__);
        }

        $query = "PRAGMA read_uncommitted=$isolation";
        return $this->_doQuery($query, true);
    }

    // }}}
    // {{{ getDatabaseFile()

    /**
     * Builds the string with path+dbname+extension
     *
     * @return string full database path+file
     * @access protected
     */
    function _getDatabaseFile($database_name)
    {
        if ($database_name === '' || $database_name === ':memory:') {
            return $database_name;
        }
        return $this->options['database_path'].$database_name.$this->options['database_extension'];
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to the database
     *
     * @return true on success, MDB2 Error Object on failure
     **/
    function connect()
    {
		if($this->connection instanceof SQLite3){
			return MDB2_OK;
		}
		$datadir=OC_Config::getValue( "datadirectory", OC::$SERVERROOT."/data" );
        $database_file = $this->_getDatabaseFile($this->database_name);
        if (is_resource($this->connection)) {
            //if (count(array_diff($this->connected_dsn, $this->dsn)) == 0
            if (MDB2::areEquals($this->connected_dsn, $this->dsn)
                && $this->connected_database_name == $database_file
                && $this->opened_persistent == $this->options['persistent']
            ) {
                return MDB2_OK;
            }
            $this->disconnect(false);
        }

        if (!PEAR::loadExtension($this->phptype)) {
            return $this->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'extension '.$this->phptype.' is not compiled into PHP', __FUNCTION__);
        }

        if (empty($this->database_name)) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED, null, null,
            'unable to establish a connection', __FUNCTION__);
        }

        if ($database_file !== ':memory:') {
			if(!strpos($database_file,'.db')){
				$database_file="$datadir/$database_file.db";
			}
            if (!file_exists($database_file)) {
                if (!touch($database_file)) {
                    return $this->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                        'Could not create database file', __FUNCTION__);
                }
                if (!isset($this->dsn['mode'])
                    || !is_numeric($this->dsn['mode'])
                ) {
                    $mode = 0644;
                } else {
                    $mode = octdec($this->dsn['mode']);
                }
                if (!chmod($database_file, $mode)) {
                    return $this->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                        'Could not be chmodded database file', __FUNCTION__);
                }
                if (!file_exists($database_file)) {
                    return $this->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                        'Could not be found database file', __FUNCTION__);
                }
            }
            if (!is_file($database_file)) {
                return $this->raiseError(MDB2_ERROR_INVALID, null, null,
                        'Database is a directory name', __FUNCTION__);
            }
            if (!is_readable($database_file)) {
                return $this->raiseError(MDB2_ERROR_ACCESS_VIOLATION, null, null,
                        'Could not read database file', __FUNCTION__);
            }
        }

        $php_errormsg = '';
		$this->connection = new SQLite3($database_file);
		if(is_callable(array($this->connection,'busyTimeout'))){//busy timout is only available in php>=5.3
			$this->connection->busyTimeout(100);
		}
        $this->_lasterror = $this->connection->lastErrorMsg();
        if (!$this->connection) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED, null, null,
            'unable to establish a connection', __FUNCTION__);
        }

        if ($this->fix_assoc_fields_names ||
            $this->options['portability'] & MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES)
        {
            $this->connection->exec("PRAGMA short_column_names = 1");
            $this->fix_assoc_fields_names = true;
        }

        $this->connected_dsn = $this->dsn;
        $this->connected_database_name = $database_file;
        $this->opened_persistent = $this->getoption('persistent');
        $this->dbsyntax = $this->dsn['dbsyntax'] ? $this->dsn['dbsyntax'] : $this->phptype;

        return MDB2_OK;
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
        $database_file = $this->_getDatabaseFile($name);
        $result = file_exists($database_file);
        return $result;
    }

    // }}}
    // {{{ disconnect()

    /**
     * Log out and disconnect from the database.
     *
     * @param  boolean $force if the disconnect should be forced even if the
     *                        connection is opened persistently
     * @return mixed true on success, false if not connected and error
     *                object on error
     * @access public
     */
    function disconnect($force = true)
    {
        if ($this->connection instanceof  SQLite3) {
            if ($this->in_transaction) {
                $dsn = $this->dsn;
                $database_name = $this->database_name;
                $persistent = $this->options['persistent'];
                $this->dsn = $this->connected_dsn;
                $this->database_name = $this->connected_database_name;
                $this->options['persistent'] = $this->opened_persistent;
                $this->rollback();
                $this->dsn = $dsn;
                $this->database_name = $database_name;
                $this->options['persistent'] = $persistent;
            }

            if (!$this->opened_persistent || $force) {
                $this->connection->close();
            }
        } else {
            return false;
        }
        return parent::disconnect($force);
    }

    // }}}
    // {{{ _doQuery()

    /**
     * Execute a query
     * @param string $query  query
     * @param boolean $is_manip  if the query is a manipulation query
     * @param resource $connection
     * @param string $database_name
     * @return result or error object
     * @access protected
     */
    function _doQuery($query, $is_manip = false, $connection = null, $database_name = null)
    {
        $this->last_query = $query;
        $result = $this->debug($query, 'query', array('is_manip' => $is_manip, 'when' => 'pre'));
        if ($result) {
            if (PEAR::isError($result)) {
                return $result;
            }
            $query = $result;
        }
        if ($this->options['disable_query']) {
            $result = $is_manip ? 0 : null;
            return $result;
        }
		$result=$this->connection->query($query.';');
        $this->_lasterror = $this->connection->lastErrorMsg();

        if (!$result) {
            $err =$this->raiseError(null, null, null,
                'Could not execute statement', __FUNCTION__);
            return $err;
        }

        $this->debug($query, 'query', array('is_manip' => $is_manip, 'when' => 'post', 'result' => $result));
        return $result;
    }

    // }}}
    // {{{ _affectedRows()

    /**
     * Returns the number of rows affected
     *
     * @param resource $result
     * @param resource $connection
     * @return mixed MDB2 Error Object or the number of rows affected
     * @access private
     */
    function _affectedRows($connection, $result = null)
    {
        return $this->connection->changes();
    }

    // }}}
    // {{{ _modifyQuery()

    /**
     * Changes a query string for various DBMS specific reasons
     *
     * @param string $query  query to modify
     * @param boolean $is_manip  if it is a DML query
     * @param integer $limit  limit the number of rows
     * @param integer $offset  start reading from given offset
     * @return string modified query
     * @access protected
     */
    function _modifyQuery($query, $is_manip, $limit, $offset)
    {
        if ($this->options['portability'] & MDB2_PORTABILITY_DELETE_COUNT) {
            if (preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $query)) {
                $query = preg_replace('/^\s*DELETE\s+FROM\s+(\S+)\s*$/',
                                      'DELETE FROM \1 WHERE 1=1', $query);
            }
        }
        if ($limit > 0
            && !preg_match('/LIMIT\s*\d(?:\s*(?:,|OFFSET)\s*\d+)?(?:[^\)]*)?$/i', $query)
        ) {
            $query = rtrim($query);
            if (substr($query, -1) == ';') {
                $query = substr($query, 0, -1);
            }
            if ($is_manip) {
                $query.= " LIMIT $limit";
            } else {
                $query.= " LIMIT $offset,$limit";
            }
        }
        return $query;
    }

    // }}}
    // {{{ getServerVersion()

    /**
     * return version information about the server
     *
     * @param bool   $native  determines if the raw version string should be returned
     * @return mixed array/string with version information or MDB2 error object
     * @access public
     */
    function getServerVersion($native = false)
    {
        $server_info = false;
        if ($this->connected_server_info) {
            $server_info = $this->connected_server_info;
        } elseif ($this->options['server_version']) {
            $server_info = $this->options['server_version'];
        } elseif (function_exists('sqlite_libversion')) {
            $server_info = @sqlite_libversion();
        }
        if (!$server_info) {
            return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'Requires either the "server_version" option or the sqlite_libversion() function', __FUNCTION__);
        }
        // cache server_info
        $this->connected_server_info = $server_info;
        if (!$native) {
            $tmp = explode('.', $server_info, 3);
            $server_info = array(
                'major' => isset($tmp[0]) ? $tmp[0] : null,
                'minor' => isset($tmp[1]) ? $tmp[1] : null,
                'patch' => isset($tmp[2]) ? $tmp[2] : null,
                'extra' => null,
                'native' => $server_info,
            );
        }
        return $server_info;
    }

    // }}}
    // {{{ replace()

    /**
     * Execute a SQL REPLACE query. A REPLACE query is identical to a INSERT
     * query, except that if there is already a row in the table with the same
     * key field values, the old row is deleted before the new row is inserted.
     *
     * The REPLACE type of query does not make part of the SQL standards. Since
     * practically only SQLite implements it natively, this type of query is
     * emulated through this method for other DBMS using standard types of
     * queries inside a transaction to assure the atomicity of the operation.
     *
     * @access public
     *
     * @param string $table name of the table on which the REPLACE query will
     *  be executed.
     * @param array $fields associative array that describes the fields and the
     *  values that will be inserted or updated in the specified table. The
     *  indexes of the array are the names of all the fields of the table. The
     *  values of the array are also associative arrays that describe the
     *  values and other properties of the table fields.
     *
     *  Here follows a list of field properties that need to be specified:
     *
     *    value:
     *          Value to be assigned to the specified field. This value may be
     *          of specified in database independent type format as this
     *          function can perform the necessary datatype conversions.
     *
     *    Default:
     *          this property is required unless the Null property
     *          is set to 1.
     *
     *    type
     *          Name of the type of the field. Currently, all types Metabase
     *          are supported except for clob and blob.
     *
     *    Default: no type conversion
     *
     *    null
     *          Boolean property that indicates that the value for this field
     *          should be set to null.
     *
     *          The default value for fields missing in INSERT queries may be
     *          specified the definition of a table. Often, the default value
     *          is already null, but since the REPLACE may be emulated using
     *          an UPDATE query, make sure that all fields of the table are
     *          listed in this function argument array.
     *
     *    Default: 0
     *
     *    key
     *          Boolean property that indicates that this field should be
     *          handled as a primary key or at least as part of the compound
     *          unique index of the table that will determine the row that will
     *          updated if it exists or inserted a new row otherwise.
     *
     *          This function will fail if no key field is specified or if the
     *          value of a key field is set to null because fields that are
     *          part of unique index they may not be null.
     *
     *    Default: 0
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     */
    function replace($table, $fields)
    {
        $count = count($fields);
        $query = $values = '';
        $keys = $colnum = 0;
        for (reset($fields); $colnum < $count; next($fields), $colnum++) {
            $name = key($fields);
            if ($colnum > 0) {
                $query .= ',';
                $values.= ',';
            }
            $query.= $this->quoteIdentifier($name, true);
            if (isset($fields[$name]['null']) && $fields[$name]['null']) {
                $value = 'NULL';
            } else {
                $type = isset($fields[$name]['type']) ? $fields[$name]['type'] : null;
                $value = $this->quote($fields[$name]['value'], $type);
                if (PEAR::isError($value)) {
                    return $value;
                }
            }
            $values.= $value;
            if (isset($fields[$name]['key']) && $fields[$name]['key']) {
                if ($value === 'NULL') {
                    return $this->raiseError(MDB2_ERROR_CANNOT_REPLACE, null, null,
                        'key value '.$name.' may not be NULL', __FUNCTION__);
                }
                $keys++;
            }
        }
        if ($keys == 0) {
            return $this->raiseError(MDB2_ERROR_CANNOT_REPLACE, null, null,
                'not specified which fields are keys', __FUNCTION__);
        }

        $connection = $this->getConnection();
        if (PEAR::isError($connection)) {
            return $connection;
        }

        $table = $this->quoteIdentifier($table, true);
        $query = "REPLACE INTO $table ($query) VALUES ($values)";
        $result =$this->_doQuery($query, true, $connection);
        if (PEAR::isError($result)) {
            return $result;
        }
        return $this->_affectedRows($connection, $result);
    }

    // }}}
    // {{{ nextID()

    /**
     * Returns the next free id of a sequence
     *
     * @param string $seq_name name of the sequence
     * @param boolean $ondemand when true the sequence is
     *                          automatic created, if it
     *                          not exists
     *
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function nextID($seq_name, $ondemand = true)
    {
        $sequence_name = $this->quoteIdentifier($this->getSequenceName($seq_name), true);
        $seqcol_name = $this->options['seqcol_name'];
        $query = "INSERT INTO $sequence_name ($seqcol_name) VALUES (NULL)";
        $this->pushErrorHandling(PEAR_ERROR_RETURN);
        $this->expectError(MDB2_ERROR_NOSUCHTABLE);
        $result =$this->_doQuery($query, true);
        $this->popExpect();
        $this->popErrorHandling();
        if (PEAR::isError($result)) {
            if ($ondemand && $result->getCode() == MDB2_ERROR_NOSUCHTABLE) {
                $this->loadModule('Manager', null, true);
                $result = $this->manager->createSequence($seq_name);
                if (PEAR::isError($result)) {
                    return $this->raiseError($result, null, null,
                        'on demand sequence '.$seq_name.' could not be created', __FUNCTION__);
                } else {
                    return $this->nextID($seq_name, false);
                }
            }
            return $result;
        }
        $value = $this->lastInsertID();
        if (is_numeric($value)) {
            $query = "DELETE FROM $sequence_name WHERE $seqcol_name < $value";
            $result =$this->_doQuery($query, true);
            if (PEAR::isError($result)) {
                $this->warnings[] = 'nextID: could not delete previous sequence table values from '.$seq_name;
            }
        }
        return $value;
    }

    // }}}
    // {{{ lastInsertID()

    /**
     * Returns the autoincrement ID if supported or $id or fetches the current
     * ID in a sequence called: $table.(empty($field) ? '' : '_'.$field)
     *
     * @param string $table name of the table into which a new row was inserted
     * @param string $field name of the field into which a new row was inserted
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function lastInsertID($table = null, $field = null)
    {
        return $this->connection->lastInsertRowID();
    }

    // }}}
    // {{{ currID()

    /**
     * Returns the current id of a sequence
     *
     * @param string $seq_name name of the sequence
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function currID($seq_name)
    {
        $sequence_name = $this->quoteIdentifier($this->getSequenceName($seq_name), true);
        $seqcol_name = $this->quoteIdentifier($this->options['seqcol_name'], true);
        $query = "SELECT MAX($seqcol_name) FROM $sequence_name";
        return $this->queryOne($query, 'integer');
    }

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
     * @param string $query the query to prepare
     * @param mixed   $types  array that contains the types of the placeholders
     * @param mixed   $result_types  array that contains the types of the columns in
     *                        the result set or MDB2_PREPARE_RESULT, if set to
     *                        MDB2_PREPARE_MANIP the query is handled as a manipulation query
     * @param mixed   $lobs   key (field) value (parameter) pair for all lob placeholders
     * @return mixed resource handle for the prepared query on success, a MDB2
     *        error on failure
     * @access public
     * @see bindParam, execute
     */
    function prepare($query, $types = null, $result_types = null, $lobs = array())
    {
        if ($this->options['emulate_prepared']
            || $this->supported['prepared_statements'] !== true
        ) {
            $obj =& parent::prepare($query, $types, $result_types, $lobs);
            return $obj;
        }
        $this->last_query = $query;
        $is_manip = ($result_types === MDB2_PREPARE_MANIP);
        $offset = $this->offset;
        $limit = $this->limit;
        $this->offset = $this->limit = 0;
        $query = $this->_modifyQuery($query, $is_manip, $limit, $offset);
        $result = $this->debug($query, __FUNCTION__, array('is_manip' => $is_manip, 'when' => 'pre'));
        if ($result) {
            if (PEAR::isError($result)) {
                return $result;
            }
            $query = $result;
        }
        $placeholder_type_guess = $placeholder_type = null;
        $question = '?';
        $colon = ':';
        $positions = array();
        $position = 0;
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
            if (is_null($placeholder_type)) {
                $placeholder_type_guess = $query[$p_position];
            }

            $new_pos = $this->_skipDelimitedStrings($query, $position, $p_position);
            if (PEAR::isError($new_pos)) {
                return $new_pos;
            }
            if ($new_pos != $position) {
                $position = $new_pos;
                continue; //evaluate again starting from the new position
            }


            if ($query[$position] == $placeholder_type_guess) {
                if (is_null($placeholder_type)) {
                    $placeholder_type = $query[$p_position];
                    $question = $colon = $placeholder_type;
                }
                if ($placeholder_type == ':') {
                    $regexp = '/^.{'.($position+1).'}('.$this->options['bindname_format'].').*$/s';
                    $parameter = preg_replace($regexp, '\\1', $query);
                    if ($parameter === '') {
                        $err =& $this->raiseError(MDB2_ERROR_SYNTAX, null, null,
                            'named parameter name must match "bindname_format" option', __FUNCTION__);
                        return $err;
                    }
                    $positions[$p_position] = $parameter;
                    $query = substr_replace($query, '?', $position, strlen($parameter)+1);
                } else {
                    $positions[$p_position] = count($positions);
                }
                $position = $p_position + 1;
            } else {
                $position = $p_position;
            }
        }
        $connection = $this->getConnection();
        if (PEAR::isError($connection)) {
            return $connection;
		}
        $statement =$this->connection->prepare($query);
        if (!$statement) {
            return $this->db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                        'unable to prepare statement: '.$query);
        }

        $class_name = 'MDB2_Statement_'.$this->phptype;
        $obj = new $class_name($this, $statement, $positions, $query, $types, $result_types, $is_manip, $limit, $offset);
        $this->debug($query, __FUNCTION__, array('is_manip' => $is_manip, 'when' => 'post', 'result' => $obj));
        return $obj;
    }
}

/**
 * MDB2 SQLite result driver
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Result_sqlite3 extends MDB2_Result_Common
{
    // }}}
    // {{{ fetchRow()

    /**
     * Fetch a row and insert the data into an existing array.
     *
     * @param int       $fetchmode  how the array data should be indexed
     * @param int    $rownum    number of the row where the data can be found
     * @return int data array on success, a MDB2 error on failure
     * @access public
     */
    function fetchRow($fetchmode = MDB2_FETCHMODE_DEFAULT, $rownum = null)
    {
        if (!is_null($rownum)) {
            $seek = $this->seek($rownum);
            if (PEAR::isError($seek)) {
                return $seek;
            }
        }
        if ($fetchmode == MDB2_FETCHMODE_DEFAULT) {
            $fetchmode = $this->db->fetchmode;
        }
        if ($fetchmode & MDB2_FETCHMODE_ASSOC) {
            //$row = @sqlite_fetch_array($this->result, SQLITE_ASSOC);
            $row=$this->result->fetchArray(SQLITE3_ASSOC);
            if (is_array($row)
                && $this->db->options['portability'] & MDB2_PORTABILITY_FIX_CASE
            ) {
                $row = array_change_key_case($row, $this->db->options['field_case']);
            }
        } else {
           $row=$this->result->fetchArray(SQLITE3_NUM);
        }
        if (!$row) {
            if ($this->result === false) {
                $err =$this->db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'resultset has already been freed', __FUNCTION__);
                return $err;
            }
            $null = null;
            return $null;
        }
        $mode = $this->db->options['portability'] & MDB2_PORTABILITY_EMPTY_TO_NULL;
        $rtrim = false;
        if ($this->db->options['portability'] & MDB2_PORTABILITY_RTRIM) {
            if (empty($this->types)) {
                $mode += MDB2_PORTABILITY_RTRIM;
            } else {
                $rtrim = true;
            }
        }
        if ($mode) {
            $this->db->_fixResultArrayValues($row, $mode);
        }
        if (!empty($this->types)) {
            $row = $this->db->datatype->convertResultRow($this->types, $row, $rtrim);
        }
        if (!empty($this->values)) {
            $this->_assignBindColumns($row);
        }
        if ($fetchmode === MDB2_FETCHMODE_OBJECT) {
            $object_class = $this->db->options['fetch_class'];
            if ($object_class == 'stdClass') {
                $row = (object) $row;
            } else {
                $row = new $object_class($row);
            }
        }
        ++$this->rownum;
        return $row;
    }

    // }}}
    // {{{ _getColumnNames()

    /**
     * Retrieve the names of columns returned by the DBMS in a query result.
     *
     * @return  mixed   Array variable that holds the names of columns as keys
     *                  or an MDB2 error on failure.
     *                  Some DBMS may not return any columns when the result set
     *                  does not contain any rows.
     * @access private
     */
    function _getColumnNames()
    {
        $columns = array();
        $numcols = $this->numCols();
        if (PEAR::isError($numcols)) {
            return $numcols;
        }
        for ($column = 0; $column < $numcols; $column++) {
            $column_name = $this->result->getColumnName($column);
            $columns[$column_name] = $column;
        }
        if ($this->db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $columns = array_change_key_case($columns, $this->db->options['field_case']);
        }
        return $columns;
    }

    // }}}
    // {{{ numCols()

    /**
     * Count the number of columns returned by the DBMS in a query result.
     *
     * @access public
     * @return mixed integer value with the number of columns, a MDB2 error
     *                       on failure
     */
    function numCols()
    {
        $this->result->numColumns();
    }
}

/**
 * MDB2 SQLite buffered result driver
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_BufferedResult_sqlite3 extends MDB2_Result_sqlite3
{
    // {{{ seek()

    /**
     * Seek to a specific row in a result set
     *
     * @param int    $rownum    number of the row where the data can be found
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function seek($rownum = 0)
    {
		$this->result->reset();
		for($i=0;$i<$rownum;$i++){
			$this->result->fetchArray();
		}
        $this->rownum = $rownum - 1;
        return MDB2_OK;
    }

    // }}}
    // {{{ valid()

    /**
     * Check if the end of the result set has been reached
     *
     * @return mixed true or false on sucess, a MDB2 error on failure
     * @access public
     */
    function valid()
    {
        $numrows = $this->numRows();
        if (PEAR::isError($numrows)) {
            return $numrows;
        }
        return $this->rownum < ($numrows - 1);
    }

    // }}}
    // {{{ numRows()

    /**
     * Returns the number of rows in a result object
     *
     * @return mixed MDB2 Error Object or the number of rows
     * @access public
     */
    function numRows()
    {
        $rows = 0;
        $this->result->reset();
        while($this->result->fetchArray()){
			$rows++;
        }
        $this->result->reset();
        return $rows;
    }
}

/**
 * MDB2 SQLite statement driver
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Statement_sqlite3 extends MDB2_Statement_Common
{
	// }}}
    // {{{ function bindValue($parameter, &$value, $type = null)

	private function getParamType($type){
		switch(strtolower($type)){
			case 'text':
				return SQLITE3_TEXT;
			case 'boolean':
			case 'integer':
				return SQLITE3_INTEGER;
			case 'float':
				return SQLITE3_FLOAT;
			case 'blob':
				return SQLITE3_BLOB;
		}
	}
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
    function bindValue($parameter, $value, $type = null){
		if($type){
			$type=$this->getParamType($type);
			$this->statement->bindValue($parameter,$value,$type);
		}else{
			$this->statement->bindValue($parameter,$value);
		}
		return MDB2_OK;
    }

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
    function bindParam($parameter, &$value, $type = null){
        if($type){
			$type=$this->getParamType($type);
			$this->statement->bindParam($parameter,$value,$type);
		}else{
			$this->statement->bindParam($parameter,$value);
		}
        return MDB2_OK;
    }

    /**
     * Release resources allocated for the specified prepared query.
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function free()
	{
		$this->statement->close();
    }

    /**
     * Execute a prepared query statement helper method.
     *
     * @param mixed $result_class string which specifies which result class to use
     * @param mixed $result_wrap_class string which specifies which class to wrap results in
     *
     * @return mixed MDB2_Result or integer (affected rows) on success,
     *               a MDB2 error on failure
     * @access private
     */
    function _execute($result_class = true, $result_wrap_class = false){
		if (is_null($this->statement)) {
            $result =& parent::_execute($result_class, $result_wrap_class);
            return $result;
        }
        $this->db->last_query = $this->query;
        $this->db->debug($this->query, 'execute', array('is_manip' => $this->is_manip, 'when' => 'pre', 'parameters' => $this->values));
        if ($this->db->getOption('disable_query')) {
            $result = $this->is_manip ? 0 : null;
            return $result;
        }

        $connection = $this->db->getConnection();
        if (PEAR::isError($connection)) {
            return $connection;
        }

        $result = $this->statement->execute();
        if ($result==false) {
            $err =$this->db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'cant execute statement', __FUNCTION__);
        }

        if ($this->is_manip) {
            $affected_rows = $this->db->_affectedRows($connection, $result);
            return $affected_rows;
        }

        $result =& $this->db->_wrapResult($result, $this->result_types,
            $result_class, $result_wrap_class, $this->limit, $this->offset);
        $this->db->debug($this->query, 'execute', array('is_manip' => $this->is_manip, 'when' => 'post', 'result' => $result));
        return $result;
    }

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
        foreach ($parameters as $key => $parameter) {
            $this->db->pushErrorHandling(PEAR_ERROR_RETURN);
            $this->db->expectError(MDB2_ERROR_NOT_FOUND);
            $err = $this->bindValue($parameter+1, $values[$parameter], $types[$key]);
            $this->db->popExpect();
            $this->db->popErrorHandling();
            if (PEAR::isError($err)) {
                if ($err->getCode() == MDB2_ERROR_NOT_FOUND) {
                    //ignore (extra value for missing placeholder)
                    continue;
                }
                return $err;
            }
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
            $err = $this->bindParam($parameter+1, $values[$parameter], $types[$key]);
            if (PEAR::isError($err)) {
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
        if (is_null($this->positions)) {
            return $this->db->raiseError(MDB2_ERROR, null, null,
                'Prepared statement has already been freed', __FUNCTION__);
        }
        $values = (array)$values;
        if (!empty($values)) {
			if(count($this->types)){
				$types=$this->types;
			}else{
				$types=null;
			}
            $err = $this->bindValueArray($values,$types);
            if (PEAR::isError($err)) {
                return $this->db->raiseError(MDB2_ERROR, null, null,
                                            'Binding Values failed with message: ' . $err->getMessage(), __FUNCTION__);
            }
        }
        $result =$this->_execute($result_class, $result_wrap_class);
        return $result;
    }

    function __destruct() {
		$this->free();
    }
}

?>
