<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2008 Manuel Lemos, Tomas V.V.Cox,                 |
// | Stig. S. Bakken, Lukas Smith                                         |
// | All rights reserved.                                                 |
// +----------------------------------------------------------------------+
// | MDB2 is a merge of PEAR DB and Metabases that provides a unified DB  |
// | API as well as database abstraction for PHP applications.            |
// | This LICENSE is in the BSD license style.                            |
// |                                                                      |
// | Redistribution and use in source and binary forms, with or without   |
// | modification, are permitted provided that the following conditions   |
// | are met:                                                             |
// |                                                                      |
// | Redistributions of source code must retain the above copyright       |
// | notice, this list of conditions and the following disclaimer.        |
// |                                                                      |
// | Redistributions in binary form must reproduce the above copyright    |
// | notice, this list of conditions and the following disclaimer in the  |
// | documentation and/or other materials provided with the distribution. |
// |                                                                      |
// | Neither the name of Manuel Lemos, Tomas V.V.Cox, Stig. S. Bakken,    |
// | Lukas Smith nor the names of his contributors may be used to endorse |
// | or promote products derived from this software without specific prior|
// | written permission.                                                  |
// |                                                                      |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
// | FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE      |
// | REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,          |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
// | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS|
// |  OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED  |
// | AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT          |
// | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY|
// | WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE          |
// | POSSIBILITY OF SUCH DAMAGE.                                          |
// +----------------------------------------------------------------------+
// | Authors: Lukas Smith <smith@pooteeweet.org>                          |
// |          Lorenzo Alberton <l.alberton@quipo.it>                      |
// +----------------------------------------------------------------------+
//
// $Id: Common.php,v 1.72 2009/01/14 15:00:40 quipo Exp $
//

/**
 * @package  MDB2
 * @category Database
 * @author   Lukas Smith <smith@pooteeweet.org>
 * @author   Lorenzo Alberton <l.alberton@quipo.it>
 */

/**
 * Base class for the management modules that is extended by each MDB2 driver
 *
 * To load this module in the MDB2 object:
 * $mdb->loadModule('Manager');
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Driver_Manager_Common extends MDB2_Module_Common
{
    // {{{ splitTableSchema()

    /**
     * Split the "[owner|schema].table" notation into an array
     *
     * @param string $table [schema and] table name
     *
     * @return array array(schema, table)
     * @access private
     */
    function splitTableSchema($table)
    {
        $ret = array();
        if (strpos($table, '.') !== false) {
            return explode('.', $table);
        }
        return array(null, $table);
    }

    // }}}
    // {{{ getFieldDeclarationList()

    /**
     * Get declaration of a number of field in bulk
     *
     * @param array $fields  a multidimensional associative array.
     *      The first dimension determines the field name, while the second
     *      dimension is keyed with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      default
     *          Boolean value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     *
     * @return mixed string on success, a MDB2 error on failure
     * @access public
     */
    function getFieldDeclarationList($fields)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (!is_array($fields) || empty($fields)) {
            return $db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                'missing any fields', __FUNCTION__);
        }
        foreach ($fields as $field_name => $field) {
            $query = $db->getDeclaration($field['type'], $field_name, $field);
            if (PEAR::isError($query)) {
                return $query;
            }
            $query_fields[] = $query;
        }
        return implode(', ', $query_fields);
    }

    // }}}
    // {{{ _fixSequenceName()

    /**
     * Removes any formatting in an sequence name using the 'seqname_format' option
     *
     * @param string $sqn string that containts name of a potential sequence
     * @param bool $check if only formatted sequences should be returned
     * @return string name of the sequence with possible formatting removed
     * @access protected
     */
    function _fixSequenceName($sqn, $check = false)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $seq_pattern = '/^'.preg_replace('/%s/', '([a-z0-9_]+)', $db->options['seqname_format']).'$/i';
        $seq_name = preg_replace($seq_pattern, '\\1', $sqn);
        if ($seq_name && !strcasecmp($sqn, $db->getSequenceName($seq_name))) {
            return $seq_name;
        }
        if ($check) {
            return false;
        }
        return $sqn;
    }

    // }}}
    // {{{ _fixIndexName()

    /**
     * Removes any formatting in an index name using the 'idxname_format' option
     *
     * @param string $idx string that containts name of anl index
     * @return string name of the index with eventual formatting removed
     * @access protected
     */
    function _fixIndexName($idx)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $idx_pattern = '/^'.preg_replace('/%s/', '([a-z0-9_]+)', $db->options['idxname_format']).'$/i';
        $idx_name = preg_replace($idx_pattern, '\\1', $idx);
        if ($idx_name && !strcasecmp($idx, $db->getIndexName($idx_name))) {
            return $idx_name;
        }
        return $idx;
    }

    // }}}
    // {{{ createDatabase()

    /**
     * create a new database
     *
     * @param string $name    name of the database that should be created
     * @param array  $options array with charset, collation info
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createDatabase($database, $options = array())
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ alterDatabase()

    /**
     * alter an existing database
     *
     * @param string $name    name of the database that should be created
     * @param array  $options array with charset, collation info
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function alterDatabase($database, $options = array())
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ dropDatabase()

    /**
     * drop an existing database
     *
     * @param string $name name of the database that should be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function dropDatabase($database)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ _getCreateTableQuery()

    /**
     * Create a basic SQL query for a new table creation
     *
     * @param string $name    Name of the database that should be created
     * @param array  $fields  Associative array that contains the definition of each field of the new table
     * @param array  $options An associative array of table options
     *
     * @return mixed string (the SQL query) on success, a MDB2 error on failure
     * @see createTable()
     */
    function _getCreateTableQuery($name, $fields, $options = array())
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (!$name) {
            return $db->raiseError(MDB2_ERROR_CANNOT_CREATE, null, null,
                'no valid table name specified', __FUNCTION__);
        }
        if (empty($fields)) {
            return $db->raiseError(MDB2_ERROR_CANNOT_CREATE, null, null,
                'no fields specified for table "'.$name.'"', __FUNCTION__);
        }
        $query_fields = $this->getFieldDeclarationList($fields);
        if (PEAR::isError($query_fields)) {
            return $query_fields;
        }
        if (!empty($options['primary'])) {
            $query_fields.= ', PRIMARY KEY ('.implode(', ', array_keys($options['primary'])).')';
        }

        $name = $db->quoteIdentifier($name, true);
        $result = 'CREATE ';
        if (!empty($options['temporary'])) {
            $result .= $this->_getTemporaryTableQuery();
        }
        $result .= " TABLE $name ($query_fields)";
        return $result;
    }

    // }}}
    // {{{ _getTemporaryTableQuery()

    /**
     * A method to return the required SQL string that fits between CREATE ... TABLE
     * to create the table as a temporary table.
     *
     * Should be overridden in driver classes to return the correct string for the
     * specific database type.
     *
     * The default is to return the string "TEMPORARY" - this will result in a
     * SQL error for any database that does not support temporary tables, or that
     * requires a different SQL command from "CREATE TEMPORARY TABLE".
     *
     * @return string The string required to be placed between "CREATE" and "TABLE"
     *                to generate a temporary table, if possible.
     */
    function _getTemporaryTableQuery()
    {
        return 'TEMPORARY';
    }

    // }}}
    // {{{ createTable()

    /**
     * create a new table
     *
     * @param string $name   Name of the database that should be created
     * @param array $fields  Associative array that contains the definition of each field of the new table
     *                       The indexes of the array entries are the names of the fields of the table an
     *                       the array entry values are associative arrays like those that are meant to be
     *                       passed with the field definitions to get[Type]Declaration() functions.
     *                          array(
     *                              'id' => array(
     *                                  'type' => 'integer',
     *                                  'unsigned' => 1
     *                                  'notnull' => 1
     *                                  'default' => 0
     *                              ),
     *                              'name' => array(
     *                                  'type' => 'text',
     *                                  'length' => 12
     *                              ),
     *                              'password' => array(
     *                                  'type' => 'text',
     *                                  'length' => 12
     *                              )
     *                          );
     * @param array $options  An associative array of table options:
     *                          array(
     *                              'comment' => 'Foo',
     *                              'temporary' => true|false,
     *                          );
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createTable($name, $fields, $options = array())
    {
        $query = $this->_getCreateTableQuery($name, $fields, $options);
        if (PEAR::isError($query)) {
            return $query;
        }
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        $result = $db->exec($query);
        if (PEAR::isError($result)) {
            return $result;
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ dropTable()

    /**
     * drop an existing table
     *
     * @param string $name name of the table that should be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function dropTable($name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $name = $db->quoteIdentifier($name, true);
        return $db->exec("DROP TABLE $name");
    }

    // }}}
    // {{{ truncateTable()

    /**
     * Truncate an existing table (if the TRUNCATE TABLE syntax is not supported,
     * it falls back to a DELETE FROM TABLE query)
     *
     * @param string $name name of the table that should be truncated
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function truncateTable($name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $name = $db->quoteIdentifier($name, true);
        return $db->exec("DELETE FROM $name");
    }

    // }}}
    // {{{ vacuum()

    /**
     * Optimize (vacuum) all the tables in the db (or only the specified table)
     * and optionally run ANALYZE.
     *
     * @param string $table table name (all the tables if empty)
     * @param array  $options an array with driver-specific options:
     *               - timeout [int] (in seconds) [mssql-only]
     *               - analyze [boolean] [pgsql and mysql]
     *               - full [boolean] [pgsql-only]
     *               - freeze [boolean] [pgsql-only]
     *
     * @return mixed MDB2_OK success, a MDB2 error on failure
     * @access public
     */
    function vacuum($table = null, $options = array())
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ alterTable()

    /**
     * alter an existing table
     *
     * @param string $name         name of the table that is intended to be changed.
     * @param array $changes     associative array that contains the details of each type
     *                             of change that is intended to be performed. The types of
     *                             changes that are currently supported are defined as follows:
     *
     *                             name
     *
     *                                New name for the table.
     *
     *                            add
     *
     *                                Associative array with the names of fields to be added as
     *                                 indexes of the array. The value of each entry of the array
     *                                 should be set to another associative array with the properties
     *                                 of the fields to be added. The properties of the fields should
     *                                 be the same as defined by the MDB2 parser.
     *
     *
     *                            remove
     *
     *                                Associative array with the names of fields to be removed as indexes
     *                                 of the array. Currently the values assigned to each entry are ignored.
     *                                 An empty array should be used for future compatibility.
     *
     *                            rename
     *
     *                                Associative array with the names of fields to be renamed as indexes
     *                                 of the array. The value of each entry of the array should be set to
     *                                 another associative array with the entry named name with the new
     *                                 field name and the entry named Declaration that is expected to contain
     *                                 the portion of the field declaration already in DBMS specific SQL code
     *                                 as it is used in the CREATE TABLE statement.
     *
     *                            change
     *
     *                                Associative array with the names of the fields to be changed as indexes
     *                                 of the array. Keep in mind that if it is intended to change either the
     *                                 name of a field and any other properties, the change array entries
     *                                 should have the new names of the fields as array indexes.
     *
     *                                The value of each entry of the array should be set to another associative
     *                                 array with the properties of the fields to that are meant to be changed as
     *                                 array entries. These entries should be assigned to the new values of the
     *                                 respective properties. The properties of the fields should be the same
     *                                 as defined by the MDB2 parser.
     *
     *                            Example
     *                                array(
     *                                    'name' => 'userlist',
     *                                    'add' => array(
     *                                        'quota' => array(
     *                                            'type' => 'integer',
     *                                            'unsigned' => 1
     *                                        )
     *                                    ),
     *                                    'remove' => array(
     *                                        'file_limit' => array(),
     *                                        'time_limit' => array()
     *                                    ),
     *                                    'change' => array(
     *                                        'name' => array(
     *                                            'length' => '20',
     *                                            'definition' => array(
     *                                                'type' => 'text',
     *                                                'length' => 20,
     *                                            ),
     *                                        )
     *                                    ),
     *                                    'rename' => array(
     *                                        'sex' => array(
     *                                            'name' => 'gender',
     *                                            'definition' => array(
     *                                                'type' => 'text',
     *                                                'length' => 1,
     *                                                'default' => 'M',
     *                                            ),
     *                                        )
     *                                    )
     *                                )
     *
     * @param boolean $check     indicates whether the function should just check if the DBMS driver
     *                             can perform the requested table alterations if the value is true or
     *                             actually perform them otherwise.
     * @access public
     *
      * @return mixed MDB2_OK on success, a MDB2 error on failure
     */
    function alterTable($name, $changes, $check)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ listDatabases()

    /**
     * list all databases
     *
     * @return mixed array of database names on success, a MDB2 error on failure
     * @access public
     */
    function listDatabases()
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implementedd', __FUNCTION__);
    }

    // }}}
    // {{{ listUsers()

    /**
     * list all users
     *
     * @return mixed array of user names on success, a MDB2 error on failure
     * @access public
     */
    function listUsers()
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ listViews()

    /**
     * list all views in the current database
     *
     * @param string database, the current is default
     *               NB: not all the drivers can get the view names from
     *               a database other than the current one
     * @return mixed array of view names on success, a MDB2 error on failure
     * @access public
     */
    function listViews($database = null)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ listTableViews()

    /**
     * list the views in the database that reference a given table
     *
     * @param string table for which all referenced views should be found
     * @return mixed array of view names on success, a MDB2 error on failure
     * @access public
     */
    function listTableViews($table)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ listTableTriggers()

    /**
     * list all triggers in the database that reference a given table
     *
     * @param string table for which all referenced triggers should be found
     * @return mixed array of trigger names on success, a MDB2 error on failure
     * @access public
     */
    function listTableTriggers($table = null)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ listFunctions()

    /**
     * list all functions in the current database
     *
     * @return mixed array of function names on success, a MDB2 error on failure
     * @access public
     */
    function listFunctions()
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ listTables()

    /**
     * list all tables in the current database
     *
     * @param string database, the current is default.
     *               NB: not all the drivers can get the table names from
     *               a database other than the current one
     * @return mixed array of table names on success, a MDB2 error on failure
     * @access public
     */
    function listTables($database = null)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ listTableFields()

    /**
     * list all fields in a table in the current database
     *
     * @param string $table name of table that should be used in method
     * @return mixed array of field names on success, a MDB2 error on failure
     * @access public
     */
    function listTableFields($table)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ createIndex()

    /**
     * Get the stucture of a field into an array
     *
     * @param string    $table         name of the table on which the index is to be created
     * @param string    $name         name of the index to be created
     * @param array     $definition        associative array that defines properties of the index to be created.
     *                                 Currently, only one property named FIELDS is supported. This property
     *                                 is also an associative with the names of the index fields as array
     *                                 indexes. Each entry of this array is set to another type of associative
     *                                 array that specifies properties of the index that are specific to
     *                                 each field.
     *
     *                                Currently, only the sorting property is supported. It should be used
     *                                 to define the sorting direction of the index. It may be set to either
     *                                 ascending or descending.
     *
     *                                Not all DBMS support index sorting direction configuration. The DBMS
     *                                 drivers of those that do not support it ignore this property. Use the
     *                                 function supports() to determine whether the DBMS driver can manage indexes.
     *
     *                                 Example
     *                                    array(
     *                                        'fields' => array(
     *                                            'user_name' => array(
     *                                                'sorting' => 'ascending'
     *                                            ),
     *                                            'last_login' => array()
     *                                        )
     *                                    )
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createIndex($table, $name, $definition)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $table = $db->quoteIdentifier($table, true);
        $name = $db->quoteIdentifier($db->getIndexName($name), true);
        $query = "CREATE INDEX $name ON $table";
        $fields = array();
        foreach (array_keys($definition['fields']) as $field) {
            $fields[] = $db->quoteIdentifier($field, true);
        }
        $query .= ' ('. implode(', ', $fields) . ')';
        return $db->exec($query);
    }

    // }}}
    // {{{ dropIndex()

    /**
     * drop existing index
     *
     * @param string    $table         name of table that should be used in method
     * @param string    $name         name of the index to be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function dropIndex($table, $name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $name = $db->quoteIdentifier($db->getIndexName($name), true);
        return $db->exec("DROP INDEX $name");
    }

    // }}}
    // {{{ listTableIndexes()

    /**
     * list all indexes in a table
     *
     * @param string $table name of table that should be used in method
     * @return mixed array of index names on success, a MDB2 error on failure
     * @access public
     */
    function listTableIndexes($table)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ _getAdvancedFKOptions()

    /**
     * Return the FOREIGN KEY query section dealing with non-standard options
     * as MATCH, INITIALLY DEFERRED, ON UPDATE, ...
     *
     * @param array $definition
     * @return string
     * @access protected
     */
    function _getAdvancedFKOptions($definition)
    {
        return '';
    }

    // }}}
    // {{{ createConstraint()

    /**
     * create a constraint on a table
     *
     * @param string    $table       name of the table on which the constraint is to be created
     * @param string    $name        name of the constraint to be created
     * @param array     $definition  associative array that defines properties of the constraint to be created.
     *                               The full structure of the array looks like this:
     *          <pre>
     *          array (
     *              [primary] => 0
     *              [unique]  => 0
     *              [foreign] => 1
     *              [check]   => 0
     *              [fields] => array (
     *                  [field1name] => array() // one entry per each field covered
     *                  [field2name] => array() // by the index
     *                  [field3name] => array(
     *                      [sorting]  => ascending
     *                      [position] => 3
     *                  )
     *              )
     *              [references] => array(
     *                  [table] => name
     *                  [fields] => array(
     *                      [field1name] => array(  //one entry per each referenced field
     *                           [position] => 1
     *                      )
     *                  )
     *              )
     *              [deferrable] => 0
     *              [initiallydeferred] => 0
     *              [onupdate] => CASCADE|RESTRICT|SET NULL|SET DEFAULT|NO ACTION
     *              [ondelete] => CASCADE|RESTRICT|SET NULL|SET DEFAULT|NO ACTION
     *              [match] => SIMPLE|PARTIAL|FULL
     *          );
     *          </pre>
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createConstraint($table, $name, $definition)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        $table = $db->quoteIdentifier($table, true);
        $name = $db->quoteIdentifier($db->getIndexName($name), true);
        $query = "ALTER TABLE $table ADD CONSTRAINT $name";
        if (!empty($definition['primary'])) {
            $query.= ' PRIMARY KEY';
        } elseif (!empty($definition['unique'])) {
            $query.= ' UNIQUE';
        } elseif (!empty($definition['foreign'])) {
            $query.= ' FOREIGN KEY';
        }
        $fields = array();
        foreach (array_keys($definition['fields']) as $field) {
            $fields[] = $db->quoteIdentifier($field, true);
        }
        $query .= ' ('. implode(', ', $fields) . ')';
        if (!empty($definition['foreign'])) {
            $query.= ' REFERENCES ' . $db->quoteIdentifier($definition['references']['table'], true);
            $referenced_fields = array();
            foreach (array_keys($definition['references']['fields']) as $field) {
                $referenced_fields[] = $db->quoteIdentifier($field, true);
            }
            $query .= ' ('. implode(', ', $referenced_fields) . ')';
            $query .= $this->_getAdvancedFKOptions($definition);
        }
        return $db->exec($query);
    }

    // }}}
    // {{{ dropConstraint()

    /**
     * drop existing constraint
     *
     * @param string    $table        name of table that should be used in method
     * @param string    $name         name of the constraint to be dropped
     * @param string    $primary      hint if the constraint is primary
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function dropConstraint($table, $name, $primary = false)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $table = $db->quoteIdentifier($table, true);
        $name = $db->quoteIdentifier($db->getIndexName($name), true);
        return $db->exec("ALTER TABLE $table DROP CONSTRAINT $name");
    }

    // }}}
    // {{{ listTableConstraints()

    /**
     * list all constraints in a table
     *
     * @param string $table name of table that should be used in method
     * @return mixed array of constraint names on success, a MDB2 error on failure
     * @access public
     */
    function listTableConstraints($table)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ createSequence()

    /**
     * create sequence
     *
     * @param string    $seq_name     name of the sequence to be created
     * @param string    $start         start value of the sequence; default is 1
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createSequence($seq_name, $start = 1)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ dropSequence()

    /**
     * drop existing sequence
     *
     * @param string    $seq_name     name of the sequence to be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function dropSequence($name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ listSequences()

    /**
     * list all sequences in the current database
     *
     * @param string database, the current is default
     *               NB: not all the drivers can get the sequence names from
     *               a database other than the current one
     * @return mixed array of sequence names on success, a MDB2 error on failure
     * @access public
     */
    function listSequences($database = null)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
}
?>