<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2008 Manuel Lemos, Tomas V.V.Cox,                 |
// | Stig. S. Bakken, Lukas Smith, Lorenzo Alberton                       |
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
// $Id: sqlite.php,v 1.76 2008/05/31 11:48:48 quipo Exp $
//

require_once 'MDB2/Driver/Manager/Common.php';

/**
 * MDB2 SQLite driver for the management modules
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 * @author  Lorenzo Alberton <l.alberton@quipo.it>
 */
class MDB2_Driver_Manager_sqlite extends MDB2_Driver_Manager_Common
{
    // {{{ createDatabase()

    /**
     * create a new database
     *
     * @param string $name    name of the database that should be created
     * @param array  $options array with charset info
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createDatabase($name, $options = array())
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $database_file = $db->_getDatabaseFile($name);
        if (file_exists($database_file)) {
            return $db->raiseError(MDB2_ERROR_ALREADY_EXISTS, null, null,
                'database already exists', __FUNCTION__);
        }
        $php_errormsg = '';
        $handle = @sqlite_open($database_file, $db->dsn['mode'], $php_errormsg);
        if (!$handle) {
            return $db->raiseError(MDB2_ERROR_CANNOT_CREATE, null, null,
                (isset($php_errormsg) ? $php_errormsg : 'could not create the database file'), __FUNCTION__);
        }
        if (!empty($options['charset'])) {
            $query = 'PRAGMA encoding = ' . $db->quote($options['charset'], 'text');
            @sqlite_query($query, $handle);
        }
        @sqlite_close($handle);
        return MDB2_OK;
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $database_file = $db->_getDatabaseFile($name);
        if (!@file_exists($database_file)) {
            return $db->raiseError(MDB2_ERROR_CANNOT_DROP, null, null,
                'database does not exist', __FUNCTION__);
        }
        $result = @unlink($database_file);
        if (!$result) {
            return $db->raiseError(MDB2_ERROR_CANNOT_DROP, null, null,
                (isset($php_errormsg) ? $php_errormsg : 'could not remove the database file'), __FUNCTION__);
        }
        return MDB2_OK;
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
        if (!empty($definition['onupdate']) && (strtoupper($definition['onupdate']) != 'NO ACTION')) {
            $query .= ' ON UPDATE '.$definition['onupdate'];
        }
        if (!empty($definition['ondelete']) && (strtoupper($definition['ondelete']) != 'NO ACTION')) {
            $query .= ' ON DELETE '.$definition['ondelete'];
        }
        if (!empty($definition['deferrable'])) {
            $query .= ' DEFERRABLE';
        } else {
            $query .= ' NOT DEFERRABLE';
        }
        if (!empty($definition['initiallydeferred'])) {
            $query .= ' INITIALLY DEFERRED';
        } else {
            $query .= ' INITIALLY IMMEDIATE';
        }
        return $query;
    }

    // }}}
    // {{{ _getCreateTableQuery()

    /**
     * Create a basic SQL query for a new table creation
     * @param string $name   Name of the database that should be created
     * @param array $fields  Associative array that contains the definition of each field of the new table
     * @param array $options  An associative array of table options
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
        if (!empty($options['foreign_keys'])) {
            foreach ($options['foreign_keys'] as $fkname => $fkdef) {
                if (empty($fkdef)) {
                    continue;
                }
                $query_fields.= ', CONSTRAINT '.$fkname.' FOREIGN KEY ('.implode(', ', array_keys($fkdef['fields'])).')';
                $query_fields.= ' REFERENCES '.$fkdef['references']['table'].' ('.implode(', ', array_keys($fkdef['references']['fields'])).')';
                $query_fields.= $this->_getAdvancedFKOptions($fkdef);
            }
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
    // {{{ createTable()

    /**
     * create a new table
     *
     * @param string $name    Name of the database that should be created
     * @param array  $fields  Associative array that contains the definition
     *                        of each field of the new table
     * @param array  $options An associative array of table options
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createTable($name, $fields, $options = array())
    {
        $result = parent::createTable($name, $fields, $options);
        if (PEAR::isError($result)) {
            return $result;
        }
        // create triggers to enforce FOREIGN KEY constraints
        if (!empty($options['foreign_keys'])) {
            $db =& $this->getDBInstance();
            if (PEAR::isError($db)) {
                return $db;
            }
            foreach ($options['foreign_keys'] as $fkname => $fkdef) {
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
                
                //create the [insert|update] triggers on the FK table
                $table_fields = array_keys($fkdef['fields']);
                $referenced_fields = array_keys($fkdef['references']['fields']);
                $query = 'CREATE TRIGGER %s BEFORE %s ON '.$name
                        .' FOR EACH ROW BEGIN'
                        .' SELECT RAISE(ROLLBACK, \'%s on table "'.$name.'" violates FOREIGN KEY constraint "'.$fkname.'"\')'
                        .' WHERE  (SELECT ';
                $aliased_fields = array();
                foreach ($referenced_fields as $field) {
                    $aliased_fields[] = $fkdef['references']['table'] .'.'.$field .' AS '.$field;
                }
                $query .= implode(',', $aliased_fields)
                       .' FROM '.$fkdef['references']['table']
                       .' WHERE ';
                $conditions = array();
                for ($i=0; $i<count($table_fields); $i++) {
                    $conditions[] = $referenced_fields[$i] .' = NEW.'.$table_fields[$i];
                }
                $query .= implode(' AND ', $conditions).') IS NULL; END;';
                $result = $db->exec(sprintf($query, $trigger_names['insert'], 'INSERT', 'insert'));
                if (PEAR::isError($result)) {
                    return $result;
                }

                $result = $db->exec(sprintf($query, $trigger_names['update'], 'UPDATE', 'update'));
                if (PEAR::isError($result)) {
                    return $result;
                }
                
                //create the ON [UPDATE|DELETE] triggers on the primary table
                $restrict_action = 'SELECT RAISE(ROLLBACK, \'%s on table "'.$name.'" violates FOREIGN KEY constraint "'.$fkname.'"\')'
                                  .' WHERE  (SELECT ';
                $aliased_fields = array();
                foreach ($table_fields as $field) {
                    $aliased_fields[] = $name .'.'.$field .' AS '.$field;
                }
                $restrict_action .= implode(',', $aliased_fields)
                       .' FROM '.$name
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
                $restrict_action .= implode(' AND ', $conditions).') IS NOT NULL'
                                 .' AND (' .implode(' OR ', $conditions2) .')';

                $cascade_action_update = 'UPDATE '.$name.' SET '.implode(', ', $new_values) .' WHERE '.implode(' AND ', $conditions);
                $cascade_action_delete = 'DELETE FROM '.$name.' WHERE '.implode(' AND ', $conditions);
                $setnull_action        = 'UPDATE '.$name.' SET '.implode(', ', $null_values).' WHERE '.implode(' AND ', $conditions);

                if ('SET DEFAULT' == $fkdef['onupdate'] || 'SET DEFAULT' == $fkdef['ondelete']) {
                    $db->loadModule('Reverse', null, true);
                    $default_values = array();
                    foreach ($table_fields as $table_field) {
                        $field_definition = $db->reverse->getTableFieldDefinition($name, $field);
                        if (PEAR::isError($field_definition)) {
                            return $field_definition;
                        }
                        $default_values[] = $table_field .' = '. $field_definition[0]['default'];
                    }
                    $setdefault_action = 'UPDATE '.$name.' SET '.implode(', ', $default_values).' WHERE '.implode(' AND ', $conditions);
                }

                $query = 'CREATE TRIGGER %s'
                        .' %s ON '.$fkdef['references']['table']
                        .' FOR EACH ROW BEGIN ';

                if ('CASCADE' == $fkdef['onupdate']) {
                    $sql_update = sprintf($query, $trigger_names['pk_update'], 'AFTER UPDATE',  'update') . $cascade_action_update. '; END;';
                } elseif ('SET NULL' == $fkdef['onupdate']) {
                    $sql_update = sprintf($query, $trigger_names['pk_update'], 'BEFORE UPDATE', 'update') . $setnull_action. '; END;';
                } elseif ('SET DEFAULT' == $fkdef['onupdate']) {
                    $sql_update = sprintf($query, $trigger_names['pk_update'], 'BEFORE UPDATE', 'update') . $setdefault_action. '; END;';
                } elseif ('NO ACTION' == $fkdef['onupdate']) {
                    $sql_update = sprintf($query.$restrict_action, $trigger_names['pk_update'], 'AFTER UPDATE', 'update') . '; END;';
                } elseif ('RESTRICT' == $fkdef['onupdate']) {
                    $sql_update = sprintf($query.$restrict_action, $trigger_names['pk_update'], 'BEFORE UPDATE', 'update') . '; END;';
                }
                if ('CASCADE' == $fkdef['ondelete']) {
                    $sql_delete = sprintf($query, $trigger_names['pk_delete'], 'AFTER DELETE',  'delete') . $cascade_action_delete. '; END;';
                } elseif ('SET NULL' == $fkdef['ondelete']) {
                    $sql_delete = sprintf($query, $trigger_names['pk_delete'], 'BEFORE DELETE', 'delete') . $setnull_action. '; END;';
                } elseif ('SET DEFAULT' == $fkdef['ondelete']) {
                    $sql_delete = sprintf($query, $trigger_names['pk_delete'], 'BEFORE DELETE', 'delete') . $setdefault_action. '; END;';
                } elseif ('NO ACTION' == $fkdef['ondelete']) {
                    $sql_delete = sprintf($query.$restrict_action, $trigger_names['pk_delete'], 'AFTER DELETE', 'delete')  . '; END;';
                } elseif ('RESTRICT' == $fkdef['ondelete']) {
                    $sql_delete = sprintf($query.$restrict_action, $trigger_names['pk_delete'], 'BEFORE DELETE', 'delete') . '; END;';
                }

                if (PEAR::isError($result)) {
                    return $result;
                }
                $result = $db->exec($sql_delete);
                if (PEAR::isError($result)) {
                    return $result;
                }
                $result = $db->exec($sql_update);
                if (PEAR::isError($result)) {
                    return $result;
                }
            }
        }
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

        $name = $db->quoteIdentifier($name, true);
        return $db->exec("DROP TABLE $name");
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

        $query = 'VACUUM';
        if (!empty($table)) {
            $query .= ' '.$db->quoteIdentifier($table, true);
        }
        return $db->exec($query);
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
    function alterTable($name, $changes, $check, $options = array())
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        foreach ($changes as $change_name => $change) {
            switch ($change_name) {
            case 'add':
            case 'remove':
            case 'change':
            case 'name':
            case 'rename':
                break;
            default:
                return $db->raiseError(MDB2_ERROR_CANNOT_ALTER, null, null,
                    'change type "'.$change_name.'" not yet supported', __FUNCTION__);
            }
        }

        if ($check) {
            return MDB2_OK;
        }

        $db->loadModule('Reverse', null, true);

        // actually sqlite 2.x supports no ALTER TABLE at all .. so we emulate it
        $fields = $db->manager->listTableFields($name);
        if (PEAR::isError($fields)) {
            return $fields;
        }

        $fields = array_flip($fields);
        foreach ($fields as $field => $value) {
            $definition = $db->reverse->getTableFieldDefinition($name, $field);
            if (PEAR::isError($definition)) {
                return $definition;
            }
            $fields[$field] = $definition[0];
        }

        $indexes = $db->manager->listTableIndexes($name);
        if (PEAR::isError($indexes)) {
            return $indexes;
        }

        $indexes = array_flip($indexes);
        foreach ($indexes as $index => $value) {
            $definition = $db->reverse->getTableIndexDefinition($name, $index);
            if (PEAR::isError($definition)) {
                return $definition;
            }
            $indexes[$index] = $definition;
        }

        $constraints = $db->manager->listTableConstraints($name);
        if (PEAR::isError($constraints)) {
            return $constraints;
        }

        if (!array_key_exists('foreign_keys', $options)) {
            $options['foreign_keys'] = array();
        }
        $constraints = array_flip($constraints);
        foreach ($constraints as $constraint => $value) {
            if (!empty($definition['primary'])) {
                if (!array_key_exists('primary', $options)) {
                    $options['primary'] = $definition['fields'];
                    //remove from the $constraint array, it's already handled by createTable()
                    unset($constraints[$constraint]);
                }
            } else {
                $c_definition = $db->reverse->getTableConstraintDefinition($name, $constraint);
                if (PEAR::isError($c_definition)) {
                    return $c_definition;
                }
                if (!empty($c_definition['foreign'])) {
                    if (!array_key_exists($constraint, $options['foreign_keys'])) {
                        $options['foreign_keys'][$constraint] = $c_definition;
                    }
                    //remove from the $constraint array, it's already handled by createTable()
                    unset($constraints[$constraint]);
                } else {
                    $constraints[$constraint] = $c_definition;
                }
            }
        }

        $name_new = $name;
        $create_order = $select_fields = array_keys($fields);
        foreach ($changes as $change_name => $change) {
            switch ($change_name) {
            case 'add':
                foreach ($change as $field_name => $field) {
                    $fields[$field_name] = $field;
                    $create_order[] = $field_name;
                }
                break;
            case 'remove':
                foreach ($change as $field_name => $field) {
                    unset($fields[$field_name]);
                    $select_fields = array_diff($select_fields, array($field_name));
                    $create_order = array_diff($create_order, array($field_name));
                }
                break;
            case 'change':
                foreach ($change as $field_name => $field) {
                    $fields[$field_name] = $field['definition'];
                }
                break;
            case 'name':
                $name_new = $change;
                break;
            case 'rename':
                foreach ($change as $field_name => $field) {
                    unset($fields[$field_name]);
                    $fields[$field['name']] = $field['definition'];
                    $create_order[array_search($field_name, $create_order)] = $field['name'];
                }
                break;
            default:
                return $db->raiseError(MDB2_ERROR_CANNOT_ALTER, null, null,
                    'change type "'.$change_name.'" not yet supported', __FUNCTION__);
            }
        }

        $data = null;
        if (!empty($select_fields)) {
            $query = 'SELECT '.implode(', ', $select_fields).' FROM '.$db->quoteIdentifier($name, true);
            $data = $db->queryAll($query, null, MDB2_FETCHMODE_ORDERED);
        }

        $result = $this->dropTable($name);
        if (PEAR::isError($result)) {
            return $result;
        }

        $result = $this->createTable($name_new, $fields, $options);
        if (PEAR::isError($result)) {
            return $result;
        }

        foreach ($indexes as $index => $definition) {
            $this->createIndex($name_new, $index, $definition);
        }

        foreach ($constraints as $constraint => $definition) {
            $this->createConstraint($name_new, $constraint, $definition);
        }

        if (!empty($select_fields) && !empty($data)) {
            $query = 'INSERT INTO '.$db->quoteIdentifier($name_new, true);
            $query.= '('.implode(', ', array_slice(array_keys($fields), 0, count($select_fields))).')';
            $query.=' VALUES (?'.str_repeat(', ?', (count($select_fields) - 1)).')';
            $stmt =& $db->prepare($query, null, MDB2_PREPARE_MANIP);
            if (PEAR::isError($stmt)) {
                return $stmt;
            }
            foreach ($data as $row) {
                $result = $stmt->execute($row);
                if (PEAR::isError($result)) {
                    return $result;
                }
            }
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'list databases is not supported', __FUNCTION__);
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
            'list databases is not supported', __FUNCTION__);
    }

    // }}}
    // {{{ listViews()

    /**
     * list all views in the current database
     *
     * @return mixed array of view names on success, a MDB2 error on failure
     * @access public
     */
    function listViews()
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = "SELECT name FROM sqlite_master WHERE type='view' AND sql NOT NULL";
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

        $query = "SELECT name, sql FROM sqlite_master WHERE type='view' AND sql NOT NULL";
        $views = $db->queryAll($query, array('text', 'text'), MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($views)) {
            return $views;
        }
        $result = array();
        foreach ($views as $row) {
            if (preg_match("/^create view .* \bfrom\b\s+\b{$table}\b /i", $row['sql'])) {
                if (!empty($row['name'])) {
                    $result[$row['name']] = true;
                }
            }
        }

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_change_key_case($result, $db->options['field_case']);
        }
        return array_keys($result);
    }

    // }}}
    // {{{ listTables()

    /**
     * list all tables in the current database
     *
     * @return mixed array of table names on success, a MDB2 error on failure
     * @access public
     */
    function listTables()
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = "SELECT name FROM sqlite_master WHERE type='table' AND sql NOT NULL ORDER BY name";
        $table_names = $db->queryCol($query);
        if (PEAR::isError($table_names)) {
            return $table_names;
        }
        $result = array();
        foreach ($table_names as $table_name) {
            if (!$this->_fixSequenceName($table_name, true)) {
                $result[] = $table_name;
            }
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $result = $db->loadModule('Reverse', null, true);
        if (PEAR::isError($result)) {
            return $result;
        }
        $query = "SELECT sql FROM sqlite_master WHERE type='table' AND ";
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $query.= 'LOWER(name)='.$db->quote(strtolower($table), 'text');
        } else {
            $query.= 'name='.$db->quote($table, 'text');
        }
        $sql = $db->queryOne($query);
        if (PEAR::isError($sql)) {
            return $sql;
        }
        $columns = $db->reverse->_getTableColumns($sql);
        $fields = array();
        foreach ($columns as $column) {
            if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                if ($db->options['field_case'] == CASE_LOWER) {
                    $column['name'] = strtolower($column['name']);
                } else {
                    $column['name'] = strtoupper($column['name']);
                }
            } else {
                $column = array_change_key_case($column, $db->options['field_case']);
            }
            $fields[] = $column['name'];
        }
        return $fields;
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

        $query = "SELECT name FROM sqlite_master WHERE type='trigger' AND sql NOT NULL";
        if (!is_null($table)) {
            if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                $query.= ' AND LOWER(tbl_name)='.$db->quote(strtolower($table), 'text');
            } else {
                $query.= ' AND tbl_name='.$db->quote($table, 'text');
            }
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
     *                                 function support() to determine whether the DBMS driver can manage indexes.

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
        $name  = $db->getIndexName($name);
        $query = "CREATE INDEX $name ON $table";
        $fields = array();
        foreach ($definition['fields'] as $field_name => $field) {
            $field_string = $field_name;
            if (!empty($field['sorting'])) {
                switch ($field['sorting']) {
                case 'ascending':
                    $field_string.= ' ASC';
                    break;
                case 'descending':
                    $field_string.= ' DESC';
                    break;
                }
            }
            $fields[] = $field_string;
        }
        $query .= ' ('.implode(', ', $fields) . ')';
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

        $name = $db->getIndexName($name);
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

        $table = $db->quote($table, 'text');
        $query = "SELECT sql FROM sqlite_master WHERE type='index' AND ";
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $query.= 'LOWER(tbl_name)='.strtolower($table);
        } else {
            $query.= "tbl_name=$table";
        }
        $query.= " AND sql NOT NULL ORDER BY name";
        $indexes = $db->queryCol($query, 'text');
        if (PEAR::isError($indexes)) {
            return $indexes;
        }

        $result = array();
        foreach ($indexes as $sql) {
            if (preg_match("/^create index ([^ ]+) on /i", $sql, $tmp)) {
                $index = $this->_fixIndexName($tmp[1]);
                if (!empty($index)) {
                    $result[$index] = true;
                }
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
     * @param string $table      name of the table on which the constraint is to be created
     * @param string $name       name of the constraint to be created
     * @param array  $definition associative array that defines properties of the constraint to be created.
     *                           Currently, only one property named FIELDS is supported. This property
     *                           is also an associative with the names of the constraint fields as array
     *                           constraints. Each entry of this array is set to another type of associative
     *                           array that specifies properties of the constraint that are specific to
     *                           each field.
     *
     *                           Example
     *                              array(
     *                                  'fields' => array(
     *                                      'user_name' => array(),
     *                                      'last_login' => array()
     *                                  )
     *                              )
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createConstraint($table, $name, $definition)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (!empty($definition['primary'])) {
            return $db->manager->alterTable($table, array(), false, array('primary' => $definition['fields']));
        }
        
        if (!empty($definition['foreign'])) {
            return $db->manager->alterTable($table, array(), false, array('foreign_keys' => array($name => $definition)));
        }

        $table = $db->quoteIdentifier($table, true);
        $name  = $db->getIndexName($name);
        $query = "CREATE UNIQUE INDEX $name ON $table";
        $fields = array();
        foreach ($definition['fields'] as $field_name => $field) {
            $field_string = $field_name;
            if (!empty($field['sorting'])) {
                switch ($field['sorting']) {
                case 'ascending':
                    $field_string.= ' ASC';
                    break;
                case 'descending':
                    $field_string.= ' DESC';
                    break;
                }
            }
            $fields[] = $field_string;
        }
        $query .= ' ('.implode(', ', $fields) . ')';
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
        if ($primary || $name == 'PRIMARY') {
            return $this->alterTable($table, array(), false, array('primary' => null));
        }

        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
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
            return $this->alterTable($table, array(), false, array('foreign_keys' => array($name => null)));
        }

        $name = $db->getIndexName($name);
        return $db->exec("DROP INDEX $name");
    }

    // }}}
    // {{{ _dropFKTriggers()
    
    /**
     * Drop the triggers created to enforce the FOREIGN KEY constraint on the table
     *
     * @param string $table  table name
     * @param string $fkname FOREIGN KEY constraint name
     * @param string $referenced_table  referenced table name
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access private
     */
    function _dropFKTriggers($table, $fkname, $referenced_table)
    {
        $db =& $this->getDBInstance();
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $table = $db->quote($table, 'text');
        $query = "SELECT sql FROM sqlite_master WHERE type='index' AND ";
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $query.= 'LOWER(tbl_name)='.strtolower($table);
        } else {
            $query.= "tbl_name=$table";
        }
        $query.= " AND sql NOT NULL ORDER BY name";
        $indexes = $db->queryCol($query, 'text');
        if (PEAR::isError($indexes)) {
            return $indexes;
        }

        $result = array();
        foreach ($indexes as $sql) {
            if (preg_match("/^create unique index ([^ ]+) on /i", $sql, $tmp)) {
                $index = $this->_fixIndexName($tmp[1]);
                if (!empty($index)) {
                    $result[$index] = true;
                }
            }
        }
        
        // also search in table definition for PRIMARY KEYs...
        $query = "SELECT sql FROM sqlite_master WHERE type='table' AND ";
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $query.= 'LOWER(name)='.strtolower($table);
        } else {
            $query.= "name=$table";
        }
        $query.= " AND sql NOT NULL ORDER BY name";
        $table_def = $db->queryOne($query, 'text');
        if (PEAR::isError($table_def)) {
            return $table_def;
        }
        if (preg_match("/\bPRIMARY\s+KEY\b/i", $table_def, $tmp)) {
            $result['primary'] = true;
        }

        // ...and for FOREIGN KEYs
        if (preg_match_all("/\bCONSTRAINT\b\s+([^\s]+)\s+\bFOREIGN\s+KEY/imsx", $table_def, $tmp)) {
            foreach ($tmp[1] as $fk) {
                $result[$fk] = true;
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

        $sequence_name = $db->quoteIdentifier($db->getSequenceName($seq_name), true);
        $seqcol_name = $db->quoteIdentifier($db->options['seqcol_name'], true);
        $query = "CREATE TABLE $sequence_name ($seqcol_name INTEGER PRIMARY KEY DEFAULT 0 NOT NULL)";
        $res = $db->exec($query);
        if (PEAR::isError($res)) {
            return $res;
        }
        if ($start == 1) {
            return MDB2_OK;
        }
        $res = $db->exec("INSERT INTO $sequence_name ($seqcol_name) VALUES (".($start-1).')');
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $sequence_name = $db->quoteIdentifier($db->getSequenceName($seq_name), true);
        return $db->exec("DROP TABLE $sequence_name");
    }

    // }}}
    // {{{ listSequences()

    /**
     * list all sequences in the current database
     *
     * @return mixed array of sequence names on success, a MDB2 error on failure
     * @access public
     */
    function listSequences()
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = "SELECT name FROM sqlite_master WHERE type='table' AND sql NOT NULL ORDER BY name";
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