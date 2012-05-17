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
// | Author: Lukas Smith <smith@pooteeweet.org>                           |
// +----------------------------------------------------------------------+
//
// $Id$
//

require_once 'MDB2/Driver/Manager/Common.php';

/**
 * MDB2 MySQL driver for the management modules
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Driver_Manager_mysql extends MDB2_Driver_Manager_Common
{

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
    function createDatabase($name, $options = array())
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $name  = $db->quoteIdentifier($name, true);
        $query = 'CREATE DATABASE ' . $name;
        if (!empty($options['charset'])) {
            $query .= ' DEFAULT CHARACTER SET ' . $db->quote($options['charset'], 'text');
        }
        if (!empty($options['collation'])) {
            $query .= ' COLLATE ' . $db->quote($options['collation'], 'text');
        }
        return $db->standaloneQuery($query, null, true);
    }

    // }}}
    // {{{ alterDatabase()

    /**
     * alter an existing database
     *
     * @param string $name    name of the database that is intended to be changed
     * @param array  $options array with charset, collation info
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function alterDatabase($name, $options = array())
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = 'ALTER DATABASE '. $db->quoteIdentifier($name, true);
        if (!empty($options['charset'])) {
            $query .= ' DEFAULT CHARACTER SET ' . $db->quote($options['charset'], 'text');
        }
        if (!empty($options['collation'])) {
            $query .= ' COLLATE ' . $db->quote($options['collation'], 'text');
        }
        return $db->standaloneQuery($query, null, true);
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
    function dropDatabase($name)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $name = $db->quoteIdentifier($name, true);
        $query = "DROP DATABASE $name";
        return $db->standaloneQuery($query, null, true);
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
        $query = '';
        if (!empty($definition['match'])) {
            $query .= ' MATCH '.$definition['match'];
        }
        if (!empty($definition['onupdate'])) {
            $query .= ' ON UPDATE '.$definition['onupdate'];
        }
        if (!empty($definition['ondelete'])) {
            $query .= ' ON DELETE '.$definition['ondelete'];
        }
        return $query;
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
     *                              'charset' => 'utf8',
     *                              'collate' => 'utf8_unicode_ci',
     *                              'type'    => 'innodb',
     *                          );
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createTable($name, $fields, $options = array())
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        // if we have an AUTO_INCREMENT column and a PK on more than one field,
        // we have to handle it differently...
        $autoincrement = null;
        if (empty($options['primary'])) {
            $pk_fields = array();
            foreach ($fields as $fieldname => $def) {
                if (!empty($def['primary'])) {
                    $pk_fields[$fieldname] = true;
                }
                if (!empty($def['autoincrement'])) {
                    $autoincrement = $fieldname;
                }
            }
            if ((null !== $autoincrement) && count($pk_fields) > 1) {
                $options['primary'] = $pk_fields;
            } else {
                // the PK constraint is on max one field => OK
                $autoincrement = null;
            }
        }

        $query = $this->_getCreateTableQuery($name, $fields, $options);
        if (PEAR::isError($query)) {
            return $query;
        }

        if (null !== $autoincrement) {
            // we have to remove the PK clause added by _getIntegerDeclaration()
            $query = str_replace('AUTO_INCREMENT PRIMARY KEY', 'AUTO_INCREMENT', $query);
        }

        $options_strings = array();

        if (!empty($options['comment'])) {
            $options_strings['comment'] = 'COMMENT = '.$db->quote($options['comment'], 'text');
        }

        if (!empty($options['charset'])) {
            $options_strings['charset'] = 'DEFAULT CHARACTER SET '.$options['charset'];
            if (!empty($options['collate'])) {
                $options_strings['charset'].= ' COLLATE '.$options['collate'];
            }
        }

        $type = false;
        if (!empty($options['type'])) {
            $type = $options['type'];
        } elseif ($db->options['default_table_type']) {
            $type = $db->options['default_table_type'];
        }
        if ($type) {
            $options_strings[] = "ENGINE = $type";
        }

        if (!empty($options_strings)) {
            $query .= ' '.implode(' ', $options_strings);
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        //delete the triggers associated to existing FK constraints
        $constraints = $this->listTableConstraints($name);
        if (!PEAR::isError($constraints) && !empty($constraints)) {
            $db->loadModule('Reverse', null, true);
            foreach ($constraints as $constraint) {
                $definition = $db->reverse->getTableConstraintDefinition($name, $constraint);
                if (!PEAR::isError($definition) && !empty($definition['foreign'])) {
                    $result = $this->_dropFKTriggers($name, $constraint, $definition['references']['table']);
                    if (PEAR::isError($result)) {
                        return $result;
                    }
                }
            }
        }

        return parent::dropTable($name);
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $name = $db->quoteIdentifier($name, true);
        $result = $db->exec("TRUNCATE TABLE $name");
        if (MDB2::isError($result)) {
            return $result;
        }
        return MDB2_OK;
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (empty($table)) {
            $table = $this->listTables();
            if (PEAR::isError($table)) {
                return $table;
            }
        }
        if (is_array($table)) {
            foreach (array_keys($table) as $k) {
            	$table[$k] = $db->quoteIdentifier($table[$k], true);
            }
            $table = implode(', ', $table);
        } else {
            $table = $db->quoteIdentifier($table, true);
        }
        
        $result = $db->exec('OPTIMIZE TABLE '.$table);
        if (PEAR::isError($result)) {
            return $result;
        }
        if (!empty($options['analyze'])) {
            $result = $db->exec('ANALYZE TABLE '.$table);
            if (MDB2::isError($result)) {
                return $result;
            }
        }
        return MDB2_OK;
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        foreach ($changes as $change_name => $change) {
            switch ($change_name) {
            case 'add':
            case 'remove':
            case 'change':
            case 'rename':
            case 'name':
                break;
            default:
                return $db->raiseError(MDB2_ERROR_CANNOT_ALTER, null, null,
                    'change type "'.$change_name.'" not yet supported', __FUNCTION__);
            }
        }

        if ($check) {
            return MDB2_OK;
        }

        $query = '';
        if (!empty($changes['name'])) {
            $change_name = $db->quoteIdentifier($changes['name'], true);
            $query .= 'RENAME TO ' . $change_name;
        }

        if (!empty($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $field_name => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $query.= 'ADD ' . $db->getDeclaration($field['type'], $field_name, $field);
            }
        }

        if (!empty($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $field_name => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $field_name = $db->quoteIdentifier($field_name, true);
                $query.= 'DROP ' . $field_name;
            }
        }

        $rename = array();
        if (!empty($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $field_name => $field) {
                $rename[$field['name']] = $field_name;
            }
        }

        if (!empty($changes['change']) && is_array($changes['change'])) {
            foreach ($changes['change'] as $field_name => $field) {
                if ($query) {
                    $query.= ', ';
                }
                if (isset($rename[$field_name])) {
                    $old_field_name = $rename[$field_name];
                    unset($rename[$field_name]);
                } else {
                    $old_field_name = $field_name;
                }
                $old_field_name = $db->quoteIdentifier($old_field_name, true);
                $query.= "CHANGE $old_field_name " . $db->getDeclaration($field['definition']['type'], $field_name, $field['definition']);
            }
        }

        if (!empty($rename) && is_array($rename)) {
            foreach ($rename as $rename_name => $renamed_field) {
                if ($query) {
                    $query.= ', ';
                }
                $field = $changes['rename'][$renamed_field];
                $renamed_field = $db->quoteIdentifier($renamed_field, true);
                $query.= 'CHANGE ' . $renamed_field . ' ' . $db->getDeclaration($field['definition']['type'], $field['name'], $field['definition']);
            }
        }

        if (!$query) {
            return MDB2_OK;
        }

        $name = $db->quoteIdentifier($name, true);
        $result = $db->exec("ALTER TABLE $name $query");
        if (MDB2::isError($result)) {
            return $result;
        }
        return MDB2_OK;
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $result = $db->queryCol('SHOW DATABASES');
        if (PEAR::isError($result)) {
            return $result;
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->queryCol('SELECT DISTINCT USER FROM mysql.USER');
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = "SELECT name FROM mysql.proc";
        /*
        SELECT ROUTINE_NAME
          FROM INFORMATION_SCHEMA.ROUTINES
         WHERE ROUTINE_TYPE = 'FUNCTION'
        */
        $result = $db->queryCol($query);
        if (PEAR::isError($result)) {
            return $result;
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = 'SHOW TRIGGERS';
        if (null !== $table) {
            $table = $db->quote($table, 'text');
            $query .= " LIKE $table";
        }
        $result = $db->queryCol($query);
        if (PEAR::isError($result)) {
            return $result;
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }

    // }}}
    // {{{ listTables()

    /**
     * list all tables in the current database
     *
     * @param string database, the current is default
     * @return mixed array of table names on success, a MDB2 error on failure
     * @access public
     */
    function listTables($database = null)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = "SHOW /*!50002 FULL*/ TABLES";
        if (null !== $database) {
            $query .= " FROM $database";
        }
        $query.= "/*!50002  WHERE Table_type = 'BASE TABLE'*/";

        $table_names = $db->queryAll($query, null, MDB2_FETCHMODE_ORDERED);
        if (PEAR::isError($table_names)) {
            return $table_names;
        }

        $result = array();
        foreach ($table_names as $table) {
            if (!$this->_fixSequenceName($table[0], true)) {
                $result[] = $table[0];
            }
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }

    // }}}
    // {{{ listViews()

    /**
     * list all views in the current database
     *
     * @param string database, the current is default
     * @return mixed array of view names on success, a MDB2 error on failure
     * @access public
     */
    function listViews($database = null)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = 'SHOW FULL TABLES';
        if (null !== $database) {
            $query.= " FROM $database";
        }
        $query.= " WHERE Table_type = 'VIEW'";

        $result = $db->queryCol($query);
        if (PEAR::isError($result)) {
            return $result;
        }

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $table = $db->quoteIdentifier($table, true);
        $result = $db->queryCol("SHOW COLUMNS FROM $table");
        if (PEAR::isError($result)) {
            return $result;
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }

    // }}}
    // {{{ createIndex()

    /**
     * Get the stucture of a field into an array
     *
     * @author Leoncx
     * @param string $table      name of the table on which the index is to be created
     * @param string $name       name of the index to be created
     * @param array  $definition associative array that defines properties of the index to be created.
     *                           Currently, only one property named FIELDS is supported. This property
     *                           is also an associative with the names of the index fields as array
     *                           indexes. Each entry of this array is set to another type of associative
     *                           array that specifies properties of the index that are specific to
     *                           each field.
     *
     *                           Currently, only the sorting property is supported. It should be used
     *                           to define the sorting direction of the index. It may be set to either
     *                           ascending or descending.
     *
     *                           Not all DBMS support index sorting direction configuration. The DBMS
     *                           drivers of those that do not support it ignore this property. Use the
     *                           function supports() to determine whether the DBMS driver can manage indexes.
     *
     *                           Example
     *                               array(
     *                                   'fields' => array(
     *                                       'user_name' => array(
     *                                           'sorting' => 'ascending'
     *                                           'length' => 10
     *                                       ),
     *                                       'last_login' => array()
     *                                    )
     *                                )
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createIndex($table, $name, $definition)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $table = $db->quoteIdentifier($table, true);
        $name = $db->quoteIdentifier($db->getIndexName($name), true);
        $query = "CREATE INDEX $name ON $table";
        $fields = array();
        foreach ($definition['fields'] as $field => $fieldinfo) {
            if (!empty($fieldinfo['length'])) {
                $fields[] = $db->quoteIdentifier($field, true) . '(' . $fieldinfo['length'] . ')';
            } else {
                $fields[] = $db->quoteIdentifier($field, true);
            }
        }
        $query .= ' ('. implode(', ', $fields) . ')';
        $result = $db->exec($query);
        if (MDB2::isError($result)) {
            return $result;
        }
        return MDB2_OK;
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $table = $db->quoteIdentifier($table, true);
        $name = $db->quoteIdentifier($db->getIndexName($name), true);
        $result = $db->exec("DROP INDEX $name ON $table");
        if (MDB2::isError($result)) {
            return $result;
        }
        return MDB2_OK;
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $key_name = 'Key_name';
        $non_unique = 'Non_unique';
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            if ($db->options['field_case'] == CASE_LOWER) {
                $key_name = strtolower($key_name);
                $non_unique = strtolower($non_unique);
            } else {
                $key_name = strtoupper($key_name);
                $non_unique = strtoupper($non_unique);
            }
        }

        $table = $db->quoteIdentifier($table, true);
        $query = "SHOW INDEX FROM $table";
        $indexes = $db->queryAll($query, null, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($indexes)) {
            return $indexes;
        }

        $result = array();
        foreach ($indexes as $index_data) {
            if ($index_data[$non_unique] && ($index = $this->_fixIndexName($index_data[$key_name]))) {
                $result[$index] = true;
            }
        }

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_change_key_case($result, $db->options['field_case']);
        }
        return array_keys($result);
    }

    // }}}
    // {{{ createConstraint()

    /**
     * create a constraint on a table
     *
     * @param string    $table        name of the table on which the constraint is to be created
     * @param string    $name         name of the constraint to be created
     * @param array     $definition   associative array that defines properties of the constraint to be created.
     *                                Currently, only one property named FIELDS is supported. This property
     *                                is also an associative with the names of the constraint fields as array
     *                                constraints. Each entry of this array is set to another type of associative
     *                                array that specifies properties of the constraint that are specific to
     *                                each field.
     *
     *                                Example
     *                                   array(
     *                                       'fields' => array(
     *                                           'user_name' => array(),
     *                                           'last_login' => array()
     *                                       )
     *                                   )
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createConstraint($table, $name, $definition)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $type = '';
        $idx_name = $db->quoteIdentifier($db->getIndexName($name), true);
        if (!empty($definition['primary'])) {
            $type = 'PRIMARY';
            $idx_name = 'KEY';
        } elseif (!empty($definition['unique'])) {
            $type = 'UNIQUE';
        } elseif (!empty($definition['foreign'])) {
            $type = 'CONSTRAINT';
        }
        if (empty($type)) {
            return $db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                'invalid definition, could not create constraint', __FUNCTION__);
        }

        $table_quoted = $db->quoteIdentifier($table, true);
        $query = "ALTER TABLE $table_quoted ADD $type $idx_name";
        if (!empty($definition['foreign'])) {
            $query .= ' FOREIGN KEY';
        }
        $fields = array();
        foreach ($definition['fields'] as $field => $fieldinfo) {
            $quoted = $db->quoteIdentifier($field, true);
            if (!empty($fieldinfo['length'])) {
                $quoted .= '(' . $fieldinfo['length'] . ')';
            }
            $fields[] = $quoted;
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

            // add index on FK column(s) or we can't add a FK constraint
            // @see http://forums.mysql.com/read.php?22,19755,226009
            $result = $this->createIndex($table, $name.'_fkidx', $definition);
            if (PEAR::isError($result)) {
                return $result;
            }
        }
        $res = $db->exec($query);
        if (PEAR::isError($res)) {
            return $res;
        }
        if (!empty($definition['foreign'])) {
            return $this->_createFKTriggers($table, array($name => $definition));
        }
        return MDB2_OK;
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if ($primary || strtolower($name) == 'primary') {
            $query = 'ALTER TABLE '. $db->quoteIdentifier($table, true) .' DROP PRIMARY KEY';
            $result = $db->exec($query);
            if (MDB2::isError($result)) {
                return $result;
            }
            return MDB2_OK;
        }

        //is it a FK constraint? If so, also delete the associated triggers
        $db->loadModule('Reverse', null, true);
        $definition = $db->reverse->getTableConstraintDefinition($table, $name);
        if (!PEAR::isError($definition) && !empty($definition['foreign'])) {
            //first drop the FK enforcing triggers
            $result = $this->_dropFKTriggers($table, $name, $definition['references']['table']);
            if (PEAR::isError($result)) {
                return $result;
            }
            //then drop the constraint itself
            $table = $db->quoteIdentifier($table, true);
            $name = $db->quoteIdentifier($db->getIndexName($name), true);
            $query = "ALTER TABLE $table DROP FOREIGN KEY $name";
            $result = $db->exec($query);
            if (MDB2::isError($result)) {
                return $result;
            }
            return MDB2_OK;
        }

        $table = $db->quoteIdentifier($table, true);
        $name = $db->quoteIdentifier($db->getIndexName($name), true);
        $query = "ALTER TABLE $table DROP INDEX $name";
        $result = $db->exec($query);
        if (MDB2::isError($result)) {
            return $result;
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ _createFKTriggers()

    /**
     * Create triggers to enforce the FOREIGN KEY constraint on the table
     *
     * NB: since there's no RAISE_APPLICATION_ERROR facility in mysql,
     * we call a non-existent procedure to raise the FK violation message.
     * @see http://forums.mysql.com/read.php?99,55108,71877#msg-71877
     *
     * @param string $table        table name
     * @param array  $foreign_keys FOREIGN KEY definitions
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access private
     */
    function _createFKTriggers($table, $foreign_keys)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        // create triggers to enforce FOREIGN KEY constraints
        if ($db->supports('triggers') && !empty($foreign_keys)) {
            $table_quoted = $db->quoteIdentifier($table, true);
            foreach ($foreign_keys as $fkname => $fkdef) {
                if (empty($fkdef)) {
                    continue;
                }
                //set actions to default if not set
                $fkdef['onupdate'] = empty($fkdef['onupdate']) ? $db->options['default_fk_action_onupdate'] : strtoupper($fkdef['onupdate']);
                $fkdef['ondelete'] = empty($fkdef['ondelete']) ? $db->options['default_fk_action_ondelete'] : strtoupper($fkdef['ondelete']);

                $trigger_names = array(
                    'insert'    => $fkname.'_insert_trg',
                    'update'    => $fkname.'_update_trg',
                    'pk_update' => $fkname.'_pk_update_trg',
                    'pk_delete' => $fkname.'_pk_delete_trg',
                );
                $table_fields = array_keys($fkdef['fields']);
                $referenced_fields = array_keys($fkdef['references']['fields']);

                //create the ON [UPDATE|DELETE] triggers on the primary table
                $restrict_action = ' IF (SELECT ';
                $aliased_fields = array();
                foreach ($table_fields as $field) {
                    $aliased_fields[] = $table_quoted .'.'.$field .' AS '.$field;
                }
                $restrict_action .= implode(',', $aliased_fields)
                       .' FROM '.$table_quoted
                       .' WHERE ';
                $conditions  = array();
                $new_values  = array();
                $null_values = array();
                for ($i=0; $i<count($table_fields); $i++) {
                    $conditions[]  = $table_fields[$i] .' = OLD.'.$referenced_fields[$i];
                    $new_values[]  = $table_fields[$i] .' = NEW.'.$referenced_fields[$i];
                    $null_values[] = $table_fields[$i] .' = NULL';
                }
                $conditions2 = array();
                for ($i=0; $i<count($referenced_fields); $i++) {
                    $conditions2[]  = 'NEW.'.$referenced_fields[$i] .' <> OLD.'.$referenced_fields[$i];
                }

                $restrict_action .= implode(' AND ', $conditions).') IS NOT NULL';
                $restrict_action2 = empty($conditions2) ? '' : ' AND (' .implode(' OR ', $conditions2) .')';
                $restrict_action3 = ' THEN CALL %s_ON_TABLE_'.$table.'_VIOLATES_FOREIGN_KEY_CONSTRAINT();'
                                   .' END IF;';

                $restrict_action_update = $restrict_action . $restrict_action2 . $restrict_action3;
                $restrict_action_delete = $restrict_action . $restrict_action3; // There is no NEW row in on DELETE trigger

                $cascade_action_update = 'UPDATE '.$table_quoted.' SET '.implode(', ', $new_values) .' WHERE '.implode(' AND ', $conditions). ';';
                $cascade_action_delete = 'DELETE FROM '.$table_quoted.' WHERE '.implode(' AND ', $conditions). ';';
                $setnull_action        = 'UPDATE '.$table_quoted.' SET '.implode(', ', $null_values).' WHERE '.implode(' AND ', $conditions). ';';

                if ('SET DEFAULT' == $fkdef['onupdate'] || 'SET DEFAULT' == $fkdef['ondelete']) {
                    $db->loadModule('Reverse', null, true);
                    $default_values = array();
                    foreach ($table_fields as $table_field) {
                        $field_definition = $db->reverse->getTableFieldDefinition($table, $field);
                        if (PEAR::isError($field_definition)) {
                            return $field_definition;
                        }
                        $default_values[] = $table_field .' = '. $field_definition[0]['default'];
                    }
                    $setdefault_action = 'UPDATE '.$table_quoted.' SET '.implode(', ', $default_values).' WHERE '.implode(' AND ', $conditions). ';';
                }

                $query = 'CREATE TRIGGER %s'
                        .' %s ON '.$fkdef['references']['table']
                        .' FOR EACH ROW BEGIN '
                        .' SET FOREIGN_KEY_CHECKS = 0; ';  //only really needed for ON UPDATE CASCADE

                if ('CASCADE' == $fkdef['onupdate']) {
                    $sql_update = sprintf($query, $trigger_names['pk_update'], 'BEFORE UPDATE',  'update') . $cascade_action_update;
                } elseif ('SET NULL' == $fkdef['onupdate']) {
                    $sql_update = sprintf($query, $trigger_names['pk_update'], 'BEFORE UPDATE', 'update') . $setnull_action;
                } elseif ('SET DEFAULT' == $fkdef['onupdate']) {
                    $sql_update = sprintf($query, $trigger_names['pk_update'], 'BEFORE UPDATE', 'update') . $setdefault_action;
                } elseif ('NO ACTION' == $fkdef['onupdate']) {
                    $sql_update = sprintf($query.$restrict_action_update, $trigger_names['pk_update'], 'AFTER UPDATE', 'update');
                } elseif ('RESTRICT' == $fkdef['onupdate']) {
                    $sql_update = sprintf($query.$restrict_action_update, $trigger_names['pk_update'], 'BEFORE UPDATE', 'update');
                }
                if ('CASCADE' == $fkdef['ondelete']) {
                    $sql_delete = sprintf($query, $trigger_names['pk_delete'], 'BEFORE DELETE',  'delete') . $cascade_action_delete;
                } elseif ('SET NULL' == $fkdef['ondelete']) {
                    $sql_delete = sprintf($query, $trigger_names['pk_delete'], 'BEFORE DELETE', 'delete') . $setnull_action;
                } elseif ('SET DEFAULT' == $fkdef['ondelete']) {
                    $sql_delete = sprintf($query, $trigger_names['pk_delete'], 'BEFORE DELETE', 'delete') . $setdefault_action;
                } elseif ('NO ACTION' == $fkdef['ondelete']) {
                    $sql_delete = sprintf($query.$restrict_action_delete, $trigger_names['pk_delete'], 'AFTER DELETE', 'delete');
                } elseif ('RESTRICT' == $fkdef['ondelete']) {
                    $sql_delete = sprintf($query.$restrict_action_delete, $trigger_names['pk_delete'], 'BEFORE DELETE', 'delete');
                }
                $sql_update .= ' SET FOREIGN_KEY_CHECKS = 1; END;';
                $sql_delete .= ' SET FOREIGN_KEY_CHECKS = 1; END;';

                $db->pushErrorHandling(PEAR_ERROR_RETURN);
                $db->expectError(MDB2_ERROR_CANNOT_CREATE);
                $result = $db->exec($sql_delete);
                $expected_errmsg = 'This MySQL version doesn\'t support multiple triggers with the same action time and event for one table';
                $db->popExpect();
                $db->popErrorHandling();
                if (PEAR::isError($result)) {
                    if ($result->getCode() != MDB2_ERROR_CANNOT_CREATE) {
                        return $result;
                    }
                    $db->warnings[] = $expected_errmsg;
                }
                $db->pushErrorHandling(PEAR_ERROR_RETURN);
                $db->expectError(MDB2_ERROR_CANNOT_CREATE);
                $result = $db->exec($sql_update);
                $db->popExpect();
                $db->popErrorHandling();
                if (PEAR::isError($result) && $result->getCode() != MDB2_ERROR_CANNOT_CREATE) {
                    if ($result->getCode() != MDB2_ERROR_CANNOT_CREATE) {
                        return $result;
                    }
                    $db->warnings[] = $expected_errmsg;
                }
            }
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ _dropFKTriggers()

    /**
     * Drop the triggers created to enforce the FOREIGN KEY constraint on the table
     *
     * @param string $table            table name
     * @param string $fkname           FOREIGN KEY constraint name
     * @param string $referenced_table referenced table name
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access private
     */
    function _dropFKTriggers($table, $fkname, $referenced_table)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $triggers  = $this->listTableTriggers($table);
        $triggers2 = $this->listTableTriggers($referenced_table);
        if (!PEAR::isError($triggers2) && !PEAR::isError($triggers)) {
            $triggers = array_merge($triggers, $triggers2);
            $pattern = '/^'.$fkname.'(_pk)?_(insert|update|delete)_trg$/i';
            foreach ($triggers as $trigger) {
                if (preg_match($pattern, $trigger)) {
                    $result = $db->exec('DROP TRIGGER '.$trigger);
                    if (PEAR::isError($result)) {
                        return $result;
                    }
                }
            }
        }
        return MDB2_OK;
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $key_name = 'Key_name';
        $non_unique = 'Non_unique';
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            if ($db->options['field_case'] == CASE_LOWER) {
                $key_name = strtolower($key_name);
                $non_unique = strtolower($non_unique);
            } else {
                $key_name = strtoupper($key_name);
                $non_unique = strtoupper($non_unique);
            }
        }

        $query = 'SHOW INDEX FROM ' . $db->quoteIdentifier($table, true);
        $indexes = $db->queryAll($query, null, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($indexes)) {
            return $indexes;
        }

        $result = array();
        foreach ($indexes as $index_data) {
            if (!$index_data[$non_unique]) {
                if ($index_data[$key_name] !== 'PRIMARY') {
                    $index = $this->_fixIndexName($index_data[$key_name]);
                } else {
                    $index = 'PRIMARY';
                }
                if (!empty($index)) {
                    $result[$index] = true;
                }
            }
        }
        
        //list FOREIGN KEY constraints...
        $query = 'SHOW CREATE TABLE '. $db->escape($table);
        $definition = $db->queryOne($query, 'text', 1);
        if (!PEAR::isError($definition) && !empty($definition)) {
            $pattern = '/\bCONSTRAINT\b\s+([^\s]+)\s+\bFOREIGN KEY\b/Uims';
            if (preg_match_all($pattern, str_replace('`', '', $definition), $matches) > 0) {
                foreach ($matches[1] as $constraint) {
                    $result[$constraint] = true;
                }
            }
        }

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_change_key_case($result, $db->options['field_case']);
        }
        return array_keys($result);
    }

    // }}}
    // {{{ createSequence()

    /**
     * create sequence
     *
     * @param string    $seq_name name of the sequence to be created
     * @param string    $start    start value of the sequence; default is 1
     * @param array     $options  An associative array of table options:
     *                          array(
     *                              'comment' => 'Foo',
     *                              'charset' => 'utf8',
     *                              'collate' => 'utf8_unicode_ci',
     *                              'type'    => 'innodb',
     *                          );
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createSequence($seq_name, $start = 1, $options = array())
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $sequence_name = $db->quoteIdentifier($db->getSequenceName($seq_name), true);
        $seqcol_name = $db->quoteIdentifier($db->options['seqcol_name'], true);
        
        $options_strings = array();

        if (!empty($options['comment'])) {
            $options_strings['comment'] = 'COMMENT = '.$db->quote($options['comment'], 'text');
        }

        if (!empty($options['charset'])) {
            $options_strings['charset'] = 'DEFAULT CHARACTER SET '.$options['charset'];
            if (!empty($options['collate'])) {
                $options_strings['charset'].= ' COLLATE '.$options['collate'];
            }
        }

        $type = false;
        if (!empty($options['type'])) {
            $type = $options['type'];
        } elseif ($db->options['default_table_type']) {
            $type = $db->options['default_table_type'];
        }
        if ($type) {
            $options_strings[] = "ENGINE = $type";
        }

        $query = "CREATE TABLE $sequence_name ($seqcol_name INT NOT NULL AUTO_INCREMENT, PRIMARY KEY ($seqcol_name))";
        if (!empty($options_strings)) {
            $query .= ' '.implode(' ', $options_strings);
        }
        $res = $db->exec($query);
        if (PEAR::isError($res)) {
            return $res;
        }

        if ($start == 1) {
            return MDB2_OK;
        }

        $query = "INSERT INTO $sequence_name ($seqcol_name) VALUES (".($start-1).')';
        $res = $db->exec($query);
        if (!PEAR::isError($res)) {
            return MDB2_OK;
        }

        // Handle error
        $result = $db->exec("DROP TABLE $sequence_name");
        if (PEAR::isError($result)) {
            return $db->raiseError($result, null, null,
                'could not drop inconsistent sequence table', __FUNCTION__);
        }

        return $db->raiseError($res, null, null,
            'could not create sequence table', __FUNCTION__);
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
    function dropSequence($seq_name)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $sequence_name = $db->quoteIdentifier($db->getSequenceName($seq_name), true);
        $result = $db->exec("DROP TABLE $sequence_name");
        if (MDB2::isError($result)) {
            return $result;
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ listSequences()

    /**
     * list all sequences in the current database
     *
     * @param string database, the current is default
     * @return mixed array of sequence names on success, a MDB2 error on failure
     * @access public
     */
    function listSequences($database = null)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = "SHOW TABLES";
        if (null !== $database) {
            $query .= " FROM $database";
        }
        $table_names = $db->queryCol($query);
        if (PEAR::isError($table_names)) {
            return $table_names;
        }

        $result = array();
        foreach ($table_names as $table_name) {
            if ($sqn = $this->_fixSequenceName($table_name, true)) {
                $result[] = $sqn;
            }
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }

    // }}}
}
?>
