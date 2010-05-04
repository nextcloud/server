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
// | Author: Paul Cooper <pgc@ucecom.com>                                 |
// +----------------------------------------------------------------------+
//
// $Id: pgsql.php,v 1.87 2008/11/29 14:09:59 afz Exp $

require_once 'MDB2/Driver/Manager/Common.php';

/**
 * MDB2 MySQL driver for the management modules
 *
 * @package MDB2
 * @category Database
 * @author  Paul Cooper <pgc@ucecom.com>
 */
class MDB2_Driver_Manager_pgsql extends MDB2_Driver_Manager_Common
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

        $name  = $db->quoteIdentifier($name, true);
        $query = 'CREATE DATABASE ' . $name;
        if (!empty($options['charset'])) {
            $query .= ' WITH ENCODING ' . $db->quote($options['charset'], 'text');
        }
        return $db->standaloneQuery($query, null, true);
    }

    // }}}
    // {{{ alterDatabase()

    /**
     * alter an existing database
     *
     * @param string $name    name of the database that is intended to be changed
     * @param array  $options array with name, owner info
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function alterDatabase($name, $options = array())
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = 'ALTER DATABASE '. $db->quoteIdentifier($name, true);
        if (!empty($options['name'])) {
            $query .= ' RENAME TO ' . $options['name'];
        }
        if (!empty($options['owner'])) {
            $query .= ' OWNER TO ' . $options['owner'];
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
        $db =& $this->getDBInstance();
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        $query = 'VACUUM';

        if (!empty($options['full'])) {
            $query .= ' FULL';
        }
        if (!empty($options['freeze'])) {
            $query .= ' FREEZE';
        }
        if (!empty($options['analyze'])) {
            $query .= ' ANALYZE';
        }

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
    function alterTable($name, $changes, $check)
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
                    'change type "'.$change_name.'\" not yet supported', __FUNCTION__);
            }
        }

        if ($check) {
            return MDB2_OK;
        }

        $name = $db->quoteIdentifier($name, true);

        if (!empty($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $field_name => $field) {
                $field_name = $db->quoteIdentifier($field_name, true);
                $query = 'DROP ' . $field_name;
                $result = $db->exec("ALTER TABLE $name $query");
                if (PEAR::isError($result)) {
                    return $result;
                }
            }
        }

        if (!empty($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $field_name => $field) {
                $field_name = $db->quoteIdentifier($field_name, true);
                $result = $db->exec("ALTER TABLE $name RENAME COLUMN $field_name TO ".$db->quoteIdentifier($field['name'], true));
                if (PEAR::isError($result)) {
                    return $result;
                }
            }
        }

        if (!empty($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $field_name => $field) {
                $query = 'ADD ' . $db->getDeclaration($field['type'], $field_name, $field);
                $result = $db->exec("ALTER TABLE $name $query");
                if (PEAR::isError($result)) {
                    return $result;
                }
            }
        }

        if (!empty($changes['change']) && is_array($changes['change'])) {
            foreach ($changes['change'] as $field_name => $field) {
                $field_name = $db->quoteIdentifier($field_name, true);
                if (!empty($field['definition']['type'])) {
                    $server_info = $db->getServerVersion();
                    if (PEAR::isError($server_info)) {
                        return $server_info;
                    }
                    if (is_array($server_info) && $server_info['major'] < 8) {
                        return $db->raiseError(MDB2_ERROR_CANNOT_ALTER, null, null,
                            'changing column type for "'.$change_name.'\" requires PostgreSQL 8.0 or above', __FUNCTION__);
                    }
                    $db->loadModule('Datatype', null, true);
                    $type = $db->datatype->getTypeDeclaration($field['definition']);
                    $query = "ALTER $field_name TYPE $type USING CAST($field_name AS $type)";
                    $result = $db->exec("ALTER TABLE $name $query");
                    if (PEAR::isError($result)) {
                        return $result;
                    }
                }
                if (array_key_exists('default', $field['definition'])) {
                    $query = "ALTER $field_name SET DEFAULT ".$db->quote($field['definition']['default'], $field['definition']['type']);
                    $result = $db->exec("ALTER TABLE $name $query");
                    if (PEAR::isError($result)) {
                        return $result;
                    }
                }
                if (!empty($field['definition']['notnull'])) {
                    $query = "ALTER $field_name ".($field['definition']['notnull'] ? 'SET' : 'DROP').' NOT NULL';
                    $result = $db->exec("ALTER TABLE $name $query");
                    if (PEAR::isError($result)) {
                        return $result;
                    }
                }
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

        $query = 'SELECT datname FROM pg_database';
        $result2 = $db->standaloneQuery($query, array('text'), false);
        if (!MDB2::isResultCommon($result2)) {
            return $result2;
        }

        $result = $result2->fetchCol();
        $result2->free();
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = 'SELECT usename FROM pg_user';
        $result2 = $db->standaloneQuery($query, array('text'), false);
        if (!MDB2::isResultCommon($result2)) {
            return $result2;
        }

        $result = $result2->fetchCol();
        $result2->free();
        return $result;
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

        $query = "SELECT viewname
                    FROM pg_views
                   WHERE schemaname NOT IN ('pg_catalog', 'information_schema')
                     AND viewname !~ '^pg_'";
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

        $query = 'SELECT viewname FROM pg_views NATURAL JOIN pg_tables';
        $query.= ' WHERE tablename ='.$db->quote($table, 'text');
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

        $query = "
            SELECT
                proname
            FROM
                pg_proc pr,
                pg_type tp
            WHERE
                tp.oid = pr.prorettype
                AND pr.proisagg = FALSE
                AND tp.typname <> 'trigger'
                AND pr.pronamespace IN
                    (SELECT oid FROM pg_namespace WHERE nspname NOT LIKE 'pg_%' AND nspname != 'information_schema')";
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = 'SELECT trg.tgname AS trigger_name
                    FROM pg_trigger trg,
                         pg_class tbl
                   WHERE trg.tgrelid = tbl.oid';
        if (!is_null($table)) {
            $table = $db->quote(strtoupper($table), 'text');
            $query .= " AND tbl.relname = $table";
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
     * @return mixed array of table names on success, a MDB2 error on failure
     * @access public
     */
    function listTables()
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        // gratuitously stolen from PEAR DB _getSpecialQuery in pgsql.php
        $query = 'SELECT c.relname AS "Name"'
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        list($schema, $table) = $this->splitTableSchema($table);

        $table = $db->quoteIdentifier($table, true);
        if (!empty($schema)) {
            $table = $db->quoteIdentifier($schema, true) . '.' .$table;
        }
        $db->setLimit(1);
        $result2 = $db->query("SELECT * FROM $table");
        if (PEAR::isError($result2)) {
            return $result2;
        }
        $result = $result2->getColumnNames();
        $result2->free();
        if (PEAR::isError($result)) {
            return $result;
        }
        return array_flip($result);
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

        list($schema, $table) = $this->splitTableSchema($table);

        $table = $db->quote($table, 'text');
        $subquery = "SELECT indexrelid
                       FROM pg_index
                  LEFT JOIN pg_class ON pg_class.oid = pg_index.indrelid
                  LEFT JOIN pg_namespace ON pg_class.relnamespace = pg_namespace.oid
                      WHERE pg_class.relname = $table
                        AND indisunique != 't'
                        AND indisprimary != 't'";
        if (!empty($schema)) {
            $subquery .= ' AND pg_namespace.nspname = '.$db->quote($schema, 'text');
        }
        $query = "SELECT relname FROM pg_class WHERE oid IN ($subquery)";
        $indexes = $db->queryCol($query, 'text');
        if (PEAR::isError($indexes)) {
            return $indexes;
        }

        $result = array();
        foreach ($indexes as $index) {
            $index = $this->_fixIndexName($index);
            if (!empty($index)) {
                $result[$index] = true;
            }
        }

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_change_key_case($result, $db->options['field_case']);
        }
        return array_keys($result);
    }

    // }}}
    // {{{ dropConstraint()

    /**
     * drop existing constraint
     *
     * @param string $table   name of table that should be used in method
     * @param string $name    name of the constraint to be dropped
     * @param string $primary hint if the constraint is primary
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function dropConstraint($table, $name, $primary = false)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        // is it an UNIQUE index?
        $query = 'SELECT relname
                    FROM pg_class
                   WHERE oid IN (
                         SELECT indexrelid
                           FROM pg_index, pg_class
                          WHERE pg_class.relname = '.$db->quote($table, 'text').'
                            AND pg_class.oid = pg_index.indrelid
                            AND indisunique = \'t\')
                  EXCEPT
                  SELECT conname
                   FROM pg_constraint, pg_class
                  WHERE pg_constraint.conrelid = pg_class.oid
                    AND relname = '. $db->quote($table, 'text');
        $unique = $db->queryCol($query, 'text');
        if (PEAR::isError($unique) || empty($unique)) {
            // not an UNIQUE index, maybe a CONSTRAINT
            return parent::dropConstraint($table, $name, $primary);
        }

        if (in_array($name, $unique)) {
            return $db->exec('DROP INDEX '.$db->quoteIdentifier($name, true));
        }
        $idxname = $db->getIndexName($name);
        if (in_array($idxname, $unique)) {
            return $db->exec('DROP INDEX '.$db->quoteIdentifier($idxname, true));
        }
        return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
            $name . ' is not an existing constraint for table ' . $table, __FUNCTION__);
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

        list($schema, $table) = $this->splitTableSchema($table);

        $table = $db->quote($table, 'text');
        $query = 'SELECT conname
                    FROM pg_constraint
               LEFT JOIN pg_class ON pg_constraint.conrelid = pg_class.oid
               LEFT JOIN pg_namespace ON pg_class.relnamespace = pg_namespace.oid
                   WHERE relname = ' .$table;
        if (!empty($schema)) {
            $query .= ' AND pg_namespace.nspname = ' . $db->quote($schema, 'text');
        }
        $query .= '
                   UNION DISTINCT
                  SELECT relname
                    FROM pg_class
                   WHERE oid IN (
                         SELECT indexrelid
                           FROM pg_index
                      LEFT JOIN pg_class ON pg_class.oid = pg_index.indrelid
                      LEFT JOIN pg_namespace ON pg_class.relnamespace = pg_namespace.oid
                          WHERE pg_class.relname = '.$table.'
                            AND indisunique = \'t\'';
        if (!empty($schema)) {
            $query .= ' AND pg_namespace.nspname = ' . $db->quote($schema, 'text');
        }
        $query .= ')';
        $constraints = $db->queryCol($query);
        if (PEAR::isError($constraints)) {
            return $constraints;
        }

        $result = array();
        foreach ($constraints as $constraint) {
            $constraint = $this->_fixIndexName($constraint);
            if (!empty($constraint)) {
                $result[$constraint] = true;
            }
        }

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE
            && $db->options['field_case'] == CASE_LOWER
        ) {
            $result = array_change_key_case($result, $db->options['field_case']);
        }
        return array_keys($result);
    }

    // }}}
    // {{{ createSequence()

    /**
     * create sequence
     *
     * @param string $seq_name name of the sequence to be created
     * @param string $start start value of the sequence; default is 1
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
        return $db->exec("CREATE SEQUENCE $sequence_name INCREMENT 1".
            ($start < 1 ? " MINVALUE $start" : '')." START $start");
    }

    // }}}
    // {{{ dropSequence()

    /**
     * drop existing sequence
     *
     * @param string $seq_name name of the sequence to be dropped
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
        return $db->exec("DROP SEQUENCE $sequence_name");
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

        $query = "SELECT relname FROM pg_class WHERE relkind = 'S' AND relnamespace IN";
        $query.= "(SELECT oid FROM pg_namespace WHERE nspname NOT LIKE 'pg_%' AND nspname != 'information_schema')";
        $table_names = $db->queryCol($query);
        if (PEAR::isError($table_names)) {
            return $table_names;
        }
        $result = array();
        foreach ($table_names as $table_name) {
            $result[] = $this->_fixSequenceName($table_name);
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }
}
?>