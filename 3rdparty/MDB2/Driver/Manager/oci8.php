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

// $Id: oci8.php 295587 2010-02-28 17:16:38Z quipo $

require_once 'MDB2/Driver/Manager/Common.php';

/**
 * MDB2 oci8 driver for the management modules
 *
 * @package MDB2
 * @category Database
 * @author Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Driver_Manager_oci8 extends MDB2_Driver_Manager_Common
{
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

        $username = $db->options['database_name_prefix'].$name;
        $password = $db->dsn['password'] ? $db->dsn['password'] : $name;
        $tablespace = $db->options['default_tablespace']
            ? ' DEFAULT TABLESPACE '.$db->options['default_tablespace'] : '';

        $query = 'CREATE USER '.$username.' IDENTIFIED BY '.$password.$tablespace;
        $result = $db->standaloneQuery($query, null, true);
        if (PEAR::isError($result)) {
            return $result;
        }
        $query = 'GRANT CREATE SESSION, CREATE TABLE, UNLIMITED TABLESPACE, CREATE SEQUENCE, CREATE TRIGGER TO '.$username;
        $result = $db->standaloneQuery($query, null, true);
        if (PEAR::isError($result)) {
            $query = 'DROP USER '.$username.' CASCADE';
            $result2 = $db->standaloneQuery($query, null, true);
            if (PEAR::isError($result2)) {
                return $db->raiseError($result2, null, null,
                    'could not setup the database user', __FUNCTION__);
            }
            return $result;
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ alterDatabase()

    /**
     * alter an existing database
     *
     * IMPORTANT: the safe way to change the db charset is to do a full import/export!
     * If - and only if - the new character set is a strict superset of the current
     * character set, it is possible to use the ALTER DATABASE CHARACTER SET to
     * expedite the change in the database character set.
     *
     * @param string $name    name of the database that is intended to be changed
     * @param array  $options array with name, charset info
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function alterDatabase($name, $options = array())
    {
        //disabled
        //return parent::alterDatabase($name, $options);

        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (!empty($options['name'])) {
            $query = 'ALTER DATABASE ' . $db->quoteIdentifier($name, true)
                    .' RENAME GLOBAL_NAME TO ' . $db->quoteIdentifier($options['name'], true);
            $result = $db->standaloneQuery($query);
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        if (!empty($options['charset'])) {
            $queries = array();
            $queries[] = 'SHUTDOWN IMMEDIATE'; //or NORMAL
            $queries[] = 'STARTUP MOUNT';
            $queries[] = 'ALTER SYSTEM ENABLE RESTRICTED SESSION';
            $queries[] = 'ALTER SYSTEM SET JOB_QUEUE_PROCESSES=0';
            $queries[] = 'ALTER DATABASE OPEN';
            $queries[] = 'ALTER DATABASE CHARACTER SET ' . $options['charset'];
            $queries[] = 'ALTER DATABASE NATIONAL CHARACTER SET ' . $options['charset'];
            $queries[] = 'SHUTDOWN IMMEDIATE'; //or NORMAL
            $queries[] = 'STARTUP';

            foreach ($queries as $query) {
                $result = $db->standaloneQuery($query);
                if (PEAR::isError($result)) {
                    return $result;
                }
            }
        }

        return MDB2_OK;
    }

    // }}}
    // {{{ dropDatabase()

    /**
     * drop an existing database
     *
     * @param object $db database object that is extended by this class
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

        $username = $db->options['database_name_prefix'].$name;
        return $db->standaloneQuery('DROP USER '.$username.' CASCADE', null, true);
    }


    // }}}
    // {{{ _makeAutoincrement()

    /**
     * add an autoincrement sequence + trigger
     *
     * @param string $name  name of the PK field
     * @param string $table name of the table
     * @param string $start start value for the sequence
     * @return mixed        MDB2_OK on success, a MDB2 error on failure
     * @access private
     */
    function _makeAutoincrement($name, $table, $start = 1)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $table_uppercase = strtoupper($table);
        $index_name  = $table_uppercase . '_AI_PK';
        $definition = array(
            'primary' => true,
            'fields' => array($name => true),
        );
        $idxname_format = $db->getOption('idxname_format');
        $db->setOption('idxname_format', '%s');
        $result = $this->createConstraint($table, $index_name, $definition);
        $db->setOption('idxname_format', $idxname_format);
        if (PEAR::isError($result)) {
            return $db->raiseError($result, null, null,
                'primary key for autoincrement PK could not be created', __FUNCTION__);
        }

        if (null === $start) {
            $db->beginTransaction();
            $query = 'SELECT MAX(' . $db->quoteIdentifier($name, true) . ') FROM ' . $db->quoteIdentifier($table, true);
            $start = $this->db->queryOne($query, 'integer');
            if (PEAR::isError($start)) {
                return $start;
            }
            ++$start;
            $result = $this->createSequence($table, $start);
            $db->commit();
        } else {
            $result = $this->createSequence($table, $start);
        }
        if (PEAR::isError($result)) {
            return $db->raiseError($result, null, null,
                'sequence for autoincrement PK could not be created', __FUNCTION__);
        }
        $seq_name        = $db->getSequenceName($table);
        $trigger_name    = $db->quoteIdentifier($table_uppercase . '_AI_PK', true);
        $seq_name_quoted = $db->quoteIdentifier($seq_name, true);
        $table = $db->quoteIdentifier($table, true);
        $name  = $db->quoteIdentifier($name, true);
        $trigger_sql = '
CREATE TRIGGER '.$trigger_name.'
   BEFORE INSERT
   ON '.$table.'
   FOR EACH ROW
DECLARE
   last_Sequence NUMBER;
   last_InsertID NUMBER;
BEGIN
   SELECT '.$seq_name_quoted.'.NEXTVAL INTO :NEW.'.$name.' FROM DUAL;
   IF (:NEW.'.$name.' IS NULL OR :NEW.'.$name.' = 0) THEN
      SELECT '.$seq_name_quoted.'.NEXTVAL INTO :NEW.'.$name.' FROM DUAL;
   ELSE
      SELECT NVL(Last_Number, 0) INTO last_Sequence
        FROM User_Sequences
       WHERE UPPER(Sequence_Name) = UPPER(\''.$seq_name.'\');
      SELECT :NEW.'.$name.' INTO last_InsertID FROM DUAL;
      WHILE (last_InsertID > last_Sequence) LOOP
         SELECT '.$seq_name_quoted.'.NEXTVAL INTO last_Sequence FROM DUAL;
      END LOOP;
   END IF;
END;
';
        $result = $db->exec($trigger_sql);
        if (PEAR::isError($result)) {
            return $result;
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ _dropAutoincrement()

    /**
     * drop an existing autoincrement sequence + trigger
     *
     * @param string $table name of the table
     * @return mixed        MDB2_OK on success, a MDB2 error on failure
     * @access private
     */
    function _dropAutoincrement($table)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $table = strtoupper($table);
        $trigger_name = $table . '_AI_PK';
        $trigger_name_quoted = $db->quote($trigger_name, 'text');
        $query = 'SELECT trigger_name FROM user_triggers';
        $query.= ' WHERE trigger_name='.$trigger_name_quoted.' OR trigger_name='.strtoupper($trigger_name_quoted);
        $trigger = $db->queryOne($query);
        if (PEAR::isError($trigger)) {
            return $trigger;
        }

        if ($trigger) {
            $trigger_name  = $db->quoteIdentifier($table . '_AI_PK', true);
            $trigger_sql = 'DROP TRIGGER ' . $trigger_name;
            $result = $db->exec($trigger_sql);
            if (PEAR::isError($result)) {
                return $db->raiseError($result, null, null,
                    'trigger for autoincrement PK could not be dropped', __FUNCTION__);
            }

            $result = $this->dropSequence($table);
            if (PEAR::isError($result)) {
                return $db->raiseError($result, null, null,
                    'sequence for autoincrement PK could not be dropped', __FUNCTION__);
            }

            $index_name = $table . '_AI_PK';
            $idxname_format = $db->getOption('idxname_format');
            $db->setOption('idxname_format', '%s');
            $result1 = $this->dropConstraint($table, $index_name);
            $db->setOption('idxname_format', $idxname_format);
            $result2 = $this->dropConstraint($table, $index_name);
            if (PEAR::isError($result1) && PEAR::isError($result2)) {
                return $db->raiseError($result1, null, null,
                    'primary key for autoincrement PK could not be dropped', __FUNCTION__);
            }
        }

        return MDB2_OK;
    }

    // }}}
    // {{{ _getTemporaryTableQuery()

    /**
     * A method to return the required SQL string that fits between CREATE ... TABLE
     * to create the table as a temporary table.
     *
     * @return string The string required to be placed between "CREATE" and "TABLE"
     *                to generate a temporary table, if possible.
     */
    function _getTemporaryTableQuery()
    {
        return 'GLOBAL TEMPORARY';
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
    // {{{ createTable()

    /**
     * create a new table
     *
     * @param string $name     Name of the database that should be created
     * @param array $fields Associative array that contains the definition of each field of the new table
     *                        The indexes of the array entries are the names of the fields of the table an
     *                        the array entry values are associative arrays like those that are meant to be
     *                         passed with the field definitions to get[Type]Declaration() functions.
     *
     *                        Example
     *                        array(
     *
     *                            'id' => array(
     *                                'type' => 'integer',
     *                                'unsigned' => 1
     *                                'notnull' => 1
     *                                'default' => 0
     *                            ),
     *                            'name' => array(
     *                                'type' => 'text',
     *                                'length' => 12
     *                            ),
     *                            'password' => array(
     *                                'type' => 'text',
     *                                'length' => 12
     *                            )
     *                        );
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        $db->beginNestedTransaction();
        $result = parent::createTable($name, $fields, $options);
        if (!PEAR::isError($result)) {
            foreach ($fields as $field_name => $field) {
                if (!empty($field['autoincrement'])) {
                    $result = $this->_makeAutoincrement($field_name, $name);
                }
            }
        }
        $db->completeNestedTransaction();
        return $result;
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
        $db->beginNestedTransaction();
        $result = $this->_dropAutoincrement($name);
        if (!PEAR::isError($result)) {
            $result = parent::dropTable($name);
        }
        $db->completeNestedTransaction();
        return $result;
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
        return $db->exec("TRUNCATE TABLE $name");
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
        // not needed in Oracle
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

        $name = $db->quoteIdentifier($name, true);

        if (!empty($changes['add']) && is_array($changes['add'])) {
            $fields = array();
            foreach ($changes['add'] as $field_name => $field) {
                $fields[] = $db->getDeclaration($field['type'], $field_name, $field);
            }
            $result = $db->exec("ALTER TABLE $name ADD (". implode(', ', $fields).')');
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        if (!empty($changes['change']) && is_array($changes['change'])) {
            $fields = array();
            foreach ($changes['change'] as $field_name => $field) {
                //fix error "column to be modified to NOT NULL is already NOT NULL" 
                if (!array_key_exists('notnull', $field)) {
                    unset($field['definition']['notnull']);
                }
                $fields[] = $db->getDeclaration($field['definition']['type'], $field_name, $field['definition']);
            }
            $result = $db->exec("ALTER TABLE $name MODIFY (". implode(', ', $fields).')');
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        if (!empty($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $field_name => $field) {
                $field_name = $db->quoteIdentifier($field_name, true);
                $query = "ALTER TABLE $name RENAME COLUMN $field_name TO ".$db->quoteIdentifier($field['name']);
                $result = $db->exec($query);
                if (PEAR::isError($result)) {
                    return $result;
                }
            }
        }

        if (!empty($changes['remove']) && is_array($changes['remove'])) {
            $fields = array();
            foreach ($changes['remove'] as $field_name => $field) {
                $fields[] = $db->quoteIdentifier($field_name, true);
            }
            $result = $db->exec("ALTER TABLE $name DROP COLUMN ". implode(', ', $fields));
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        if (!empty($changes['name'])) {
            $change_name = $db->quoteIdentifier($changes['name'], true);
            $result = $db->exec("ALTER TABLE $name RENAME TO ".$change_name);
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        return MDB2_OK;
    }

    // }}}
    // {{{ _fetchCol()

    /**
     * Utility method to fetch and format a column from a resultset
     *
     * @param resource $result
     * @param boolean $fixname (used when listing indices or constraints)
     * @return mixed array of names on success, a MDB2 error on failure
     * @access private
     */
    function _fetchCol($result, $fixname = false)
    {
        if (PEAR::isError($result)) {
            return $result;
        }
        $col = $result->fetchCol();
        if (PEAR::isError($col)) {
            return $col;
        }
        $result->free();
        
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        
        if ($fixname) {
            foreach ($col as $k => $v) {
                $col[$k] = $this->_fixIndexName($v);
            }
        }
        
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE
            && $db->options['field_case'] == CASE_LOWER
        ) {
            $col = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $col);
        }
        return $col;
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

        if (!$db->options['emulate_database']) {
            return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'database listing is only supported if the "emulate_database" option is enabled', __FUNCTION__);
        }

        if ($db->options['database_name_prefix']) {
            $query = 'SELECT SUBSTR(username, ';
            $query.= (strlen($db->options['database_name_prefix'])+1);
            $query.= ") FROM sys.dba_users WHERE username LIKE '";
            $query.= $db->options['database_name_prefix']."%'";
        } else {
            $query = 'SELECT username FROM sys.dba_users';
        }
        $result = $db->standaloneQuery($query, array('text'), false);
        return $this->_fetchCol($result);
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

        if ($db->options['emulate_database'] && $db->options['database_name_prefix']) {
            $query = 'SELECT SUBSTR(username, ';
            $query.= (strlen($db->options['database_name_prefix'])+1);
            $query.= ") FROM sys.dba_users WHERE username NOT LIKE '";
            $query.= $db->options['database_name_prefix']."%'";
        } else {
            $query = 'SELECT username FROM sys.dba_users';
        }
        return $db->queryCol($query);
    }

    // }}}
    // {{{ listViews()

    /**
     * list all views in the current database
     *
     * @param string owner, the current is default
     * @return mixed array of view names on success, a MDB2 error on failure
     * @access public
     */
    function listViews($owner = null)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        
        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }

        $query = 'SELECT view_name
                    FROM sys.all_views
                   WHERE owner=? OR owner=?';
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        $result = $stmt->execute(array($owner, strtoupper($owner)));
        return $this->_fetchCol($result);
    }

    // }}}
    // {{{ listFunctions()

    /**
     * list all functions in the current database
     *
     * @param string owner, the current is default
     * @return mixed array of function names on success, a MDB2 error on failure
     * @access public
     */
    function listFunctions($owner = null)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }

        $query = "SELECT name
                    FROM sys.all_source
                   WHERE line = 1
                     AND type = 'FUNCTION'
                     AND (owner=? OR owner=?)";
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        $result = $stmt->execute(array($owner, strtoupper($owner)));
        return $this->_fetchCol($result);
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

        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }

        $query = "SELECT trigger_name
                    FROM sys.all_triggers
                   WHERE (table_name=? OR table_name=?)
                     AND (owner=? OR owner=?)";
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        $args = array(
            $table,
            strtoupper($table),
            $owner,
            strtoupper($owner),
        );
        $result = $stmt->execute($args);
        return $this->_fetchCol($result);
    }

    // }}}
    // {{{ listTables()

    /**
     * list all tables in the database
     *
     * @param string owner, the current is default
     * @return mixed array of table names on success, a MDB2 error on failure
     * @access public
     */
    function listTables($owner = null)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        
        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }

        $query = 'SELECT table_name
                    FROM sys.all_tables
                   WHERE owner=? OR owner=?';
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        $result = $stmt->execute(array($owner, strtoupper($owner)));
        return $this->_fetchCol($result);
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
        
        list($owner, $table) = $this->splitTableSchema($table);
        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }

        $query = 'SELECT column_name
                    FROM all_tab_columns
                   WHERE (table_name=? OR table_name=?)
                     AND (owner=? OR owner=?)
                ORDER BY column_id';
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        $args = array(
            $table,
            strtoupper($table),
            $owner,
            strtoupper($owner),
        );
        $result = $stmt->execute($args);
        return $this->_fetchCol($result);
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
        
        list($owner, $table) = $this->splitTableSchema($table);
        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }
        
        $query = 'SELECT i.index_name name
                    FROM all_indexes i
               LEFT JOIN all_constraints c
                      ON c.index_name = i.index_name
                     AND c.owner = i.owner
                     AND c.table_name = i.table_name
                   WHERE (i.table_name=? OR i.table_name=?)
                     AND (i.owner=? OR i.owner=?)
                     AND c.index_name IS NULL
                     AND i.generated=' .$db->quote('N', 'text');
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        $args = array(
            $table,
            strtoupper($table),
            $owner,
            strtoupper($owner),
        );
        $result = $stmt->execute($args);
        return $this->_fetchCol($result, true);
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
        $result = parent::createConstraint($table, $name, $definition);
        if (PEAR::isError($result)) {
            return $result;
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

        //is it a FK constraint? If so, also delete the associated triggers
        $db->loadModule('Reverse', null, true);
        $definition = $db->reverse->getTableConstraintDefinition($table, $name);
        if (!PEAR::isError($definition) && !empty($definition['foreign'])) {
            //first drop the FK enforcing triggers
            $result = $this->_dropFKTriggers($table, $name, $definition['references']['table']);
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        return parent::dropConstraint($table, $name, $primary);
    }

    // }}}
    // {{{ _createFKTriggers()

    /**
     * Create triggers to enforce the FOREIGN KEY constraint on the table
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
            $table = $db->quoteIdentifier($table, true);
            foreach ($foreign_keys as $fkname => $fkdef) {
                if (empty($fkdef)) {
                    continue;
                }
                $fkdef['onupdate'] = empty($fkdef['onupdate']) ? $db->options['default_fk_action_onupdate'] : strtoupper($fkdef['onupdate']);
                if ('RESTRICT' == $fkdef['onupdate'] || 'NO ACTION' == $fkdef['onupdate']) {
                    // already handled by default
                    continue;
                }

                $trigger_name = substr(strtolower($fkname.'_pk_upd_trg'), 0, $db->options['max_identifiers_length']);
                $table_fields = array_keys($fkdef['fields']);
                $referenced_fields = array_keys($fkdef['references']['fields']);

                //create the ON UPDATE trigger on the primary table
                $restrict_action = ' IF (SELECT ';
                $aliased_fields = array();
                foreach ($table_fields as $field) {
                    $aliased_fields[] = $table .'.'.$field .' AS '.$field;
                }
                $restrict_action .= implode(',', $aliased_fields)
                       .' FROM '.$table
                       .' WHERE ';
                $conditions  = array();
                $new_values  = array();
                $null_values = array();
                for ($i=0; $i<count($table_fields); $i++) {
                    $conditions[]  = $table_fields[$i] .' = :OLD.'.$referenced_fields[$i];
                    $new_values[]  = $table_fields[$i] .' = :NEW.'.$referenced_fields[$i];
                    $null_values[] = $table_fields[$i] .' = NULL';
                }

                $cascade_action = 'UPDATE '.$table.' SET '.implode(', ', $new_values) .' WHERE '.implode(' AND ', $conditions). ';';
                $setnull_action = 'UPDATE '.$table.' SET '.implode(', ', $null_values).' WHERE '.implode(' AND ', $conditions). ';';

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
                    $setdefault_action = 'UPDATE '.$table.' SET '.implode(', ', $default_values).' WHERE '.implode(' AND ', $conditions). ';';
                }

                $query = 'CREATE TRIGGER %s'
                        .' %s ON '.$fkdef['references']['table']
                        .' FOR EACH ROW '
                        .' BEGIN ';

                if ('CASCADE' == $fkdef['onupdate']) {
                    $sql_update = sprintf($query, $trigger_name, 'BEFORE UPDATE',  'update') . $cascade_action;
                } elseif ('SET NULL' == $fkdef['onupdate']) {
                    $sql_update = sprintf($query, $trigger_name, 'BEFORE UPDATE', 'update') . $setnull_action;
                } elseif ('SET DEFAULT' == $fkdef['onupdate']) {
                    $sql_update = sprintf($query, $trigger_name, 'BEFORE UPDATE', 'update') . $setdefault_action;
                }
                $sql_update .= ' END;';
                $result = $db->exec($sql_update);
                if (PEAR::isError($result)) {
                    if ($result->getCode() === MDB2_ERROR_ALREADY_EXISTS) {
                        return MDB2_OK;
                    }
                    return $result;
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
            $trigger_name = substr(strtolower($fkname.'_pk_upd_trg'), 0, $db->options['max_identifiers_length']);
            $pattern = '/^'.$trigger_name.'$/i';
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

        list($owner, $table) = $this->splitTableSchema($table);
        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }

        $query = 'SELECT constraint_name
                    FROM all_constraints
                   WHERE (table_name=? OR table_name=?)
                     AND (owner=? OR owner=?)';
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        $args = array(
            $table,
            strtoupper($table),
            $owner,
            strtoupper($owner),
        );
        $result = $stmt->execute($args);
        return $this->_fetchCol($result, true);
    }

    // }}}
    // {{{ createSequence()

    /**
     * create sequence
     *
     * @param object $db database object that is extended by this class
     * @param string $seq_name name of the sequence to be created
     * @param string $start start value of the sequence; default is 1
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createSequence($seq_name, $start = 1)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $sequence_name = $db->quoteIdentifier($db->getSequenceName($seq_name), true);
        $query = "CREATE SEQUENCE $sequence_name START WITH $start INCREMENT BY 1 NOCACHE";
        $query.= ($start < 1 ? " MINVALUE $start" : '');
        return $db->exec($query);
    }

    // }}}
    // {{{ dropSequence()

    /**
     * drop existing sequence
     *
     * @param object $db database object that is extended by this class
     * @param string $seq_name name of the sequence to be dropped
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
        return $db->exec("DROP SEQUENCE $sequence_name");
    }

    // }}}
    // {{{ listSequences()

    /**
     * list all sequences in the current database
     *
     * @param string owner, the current is default
     * @return mixed array of sequence names on success, a MDB2 error on failure
     * @access public
     */
    function listSequences($owner = null)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }

        $query = 'SELECT sequence_name
                    FROM sys.all_sequences
                   WHERE (sequence_owner=? OR sequence_owner=?)';
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        $result = $stmt->execute(array($owner, strtoupper($owner)));
        if (PEAR::isError($result)) {
            return $result;
        }
        $col = $result->fetchCol();
        if (PEAR::isError($col)) {
            return $col;
        }
        $result->free();
        
        foreach ($col as $k => $v) {
            $col[$k] = $this->_fixSequenceName($v);
        }

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE
            && $db->options['field_case'] == CASE_LOWER
        ) {
            $col = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $col);
        }
        return $col;
    }
}
?>