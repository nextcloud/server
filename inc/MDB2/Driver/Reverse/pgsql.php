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
// | Authors: Paul Cooper <pgc@ucecom.com>                                |
// |          Lorenzo Alberton <l.alberton@quipo.it>                      |
// +----------------------------------------------------------------------+
//
// $Id: pgsql.php,v 1.75 2008/08/22 16:36:20 quipo Exp $

require_once 'MDB2/Driver/Reverse/Common.php';

/**
 * MDB2 PostGreSQL driver for the schema reverse engineering module
 *
 * @package  MDB2
 * @category Database
 * @author   Paul Cooper <pgc@ucecom.com>
 * @author   Lorenzo Alberton <l.alberton@quipo.it>
 */
class MDB2_Driver_Reverse_pgsql extends MDB2_Driver_Reverse_Common
{
    // {{{ getTableFieldDefinition()

    /**
     * Get the structure of a field into an array
     *
     * @param string $table_name name of table that should be used in method
     * @param string $field_name name of field that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function getTableFieldDefinition($table_name, $field_name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $result = $db->loadModule('Datatype', null, true);
        if (PEAR::isError($result)) {
            return $result;
        }

        list($schema, $table) = $this->splitTableSchema($table_name);

        $query = "SELECT a.attname AS name,
                         t.typname AS type,
                         CASE a.attlen
                           WHEN -1 THEN
	                         CASE t.typname
	                           WHEN 'numeric' THEN (a.atttypmod / 65536)
	                           WHEN 'decimal' THEN (a.atttypmod / 65536)
	                           WHEN 'money'   THEN (a.atttypmod / 65536)
	                           ELSE CASE a.atttypmod
                                 WHEN -1 THEN NULL
	                             ELSE a.atttypmod - 4
	                           END
                             END
	                       ELSE a.attlen
                         END AS length,
	                     CASE t.typname
	                       WHEN 'numeric' THEN (a.atttypmod % 65536) - 4
	                       WHEN 'decimal' THEN (a.atttypmod % 65536) - 4
	                       WHEN 'money'   THEN (a.atttypmod % 65536) - 4
	                       ELSE 0
                         END AS scale,
                         a.attnotnull,
                         a.atttypmod,
                         a.atthasdef,
                         (SELECT substring(pg_get_expr(d.adbin, d.adrelid) for 128)
                            FROM pg_attrdef d
                           WHERE d.adrelid = a.attrelid
                             AND d.adnum = a.attnum
                             AND a.atthasdef
                         ) as default
                    FROM pg_attribute a,
                         pg_class c,
                         pg_type t
                   WHERE c.relname = ".$db->quote($table, 'text')."
                     AND a.atttypid = t.oid
                     AND c.oid = a.attrelid
                     AND NOT a.attisdropped
                     AND a.attnum > 0
                     AND a.attname = ".$db->quote($field_name, 'text')."
                ORDER BY a.attnum";
        $column = $db->queryRow($query, null, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($column)) {
            return $column;
        }

        if (empty($column)) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'it was not specified an existing table column', __FUNCTION__);
        }

        $column = array_change_key_case($column, CASE_LOWER);
        $mapped_datatype = $db->datatype->mapNativeDatatype($column);
        if (PEAR::isError($mapped_datatype)) {
            return $mapped_datatype;
        }
        list($types, $length, $unsigned, $fixed) = $mapped_datatype;
        $notnull = false;
        if (!empty($column['attnotnull']) && $column['attnotnull'] == 't') {
            $notnull = true;
        }
        $default = null;
        if ($column['atthasdef'] === 't'
            && !preg_match("/nextval\('([^']+)'/", $column['default'])
        ) {
            $pattern = '/^\'(.*)\'::[\w ]+$/i';
            $default = $column['default'];#substr($column['adsrc'], 1, -1);
            if (is_null($default) && $notnull) {
                $default = '';
            } elseif (!empty($default) && preg_match($pattern, $default)) {
                //remove data type cast
                $default = preg_replace ($pattern, '\\1', $default);
            }
        }
        $autoincrement = false;
        if (preg_match("/nextval\('([^']+)'/", $column['default'], $nextvals)) {
            $autoincrement = true;
        }
        $definition[0] = array('notnull' => $notnull, 'nativetype' => $column['type']);
        if (!is_null($length)) {
            $definition[0]['length'] = $length;
        }
        if (!is_null($unsigned)) {
            $definition[0]['unsigned'] = $unsigned;
        }
        if (!is_null($fixed)) {
            $definition[0]['fixed'] = $fixed;
        }
        if ($default !== false) {
            $definition[0]['default'] = $default;
        }
        if ($autoincrement !== false) {
            $definition[0]['autoincrement'] = $autoincrement;
        }
        foreach ($types as $key => $type) {
            $definition[$key] = $definition[0];
            if ($type == 'clob' || $type == 'blob') {
                unset($definition[$key]['default']);
            }
            $definition[$key]['type'] = $type;
            $definition[$key]['mdb2type'] = $type;
        }
        return $definition;
    }

    // }}}
    // {{{ getTableIndexDefinition()

    /**
     * Get the structure of an index into an array
     *
     * @param string $table_name name of table that should be used in method
     * @param string $index_name name of index that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function getTableIndexDefinition($table_name, $index_name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        
        list($schema, $table) = $this->splitTableSchema($table_name);

        $query = 'SELECT relname, indkey FROM pg_index, pg_class';
        $query.= ' WHERE pg_class.oid = pg_index.indexrelid';
        $query.= " AND indisunique != 't' AND indisprimary != 't'";
        $query.= ' AND pg_class.relname = %s';
        $index_name_mdb2 = $db->getIndexName($index_name);
        $row = $db->queryRow(sprintf($query, $db->quote($index_name_mdb2, 'text')), null, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($row) || empty($row)) {
            // fallback to the given $index_name, without transformation
            $row = $db->queryRow(sprintf($query, $db->quote($index_name, 'text')), null, MDB2_FETCHMODE_ASSOC);
        }
        if (PEAR::isError($row)) {
            return $row;
        }

        if (empty($row)) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'it was not specified an existing table index', __FUNCTION__);
        }

        $row = array_change_key_case($row, CASE_LOWER);

        $db->loadModule('Manager', null, true);
        $columns = $db->manager->listTableFields($table_name);

        $definition = array();

        $index_column_numbers = explode(' ', $row['indkey']);

        $colpos = 1;
        foreach ($index_column_numbers as $number) {
            $definition['fields'][$columns[($number - 1)]] = array(
                'position' => $colpos++,
                'sorting' => 'ascending',
            );
        }
        return $definition;
    }

    // }}}
    // {{{ getTableConstraintDefinition()

    /**
     * Get the structure of a constraint into an array
     *
     * @param string $table_name      name of table that should be used in method
     * @param string $constraint_name name of constraint that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function getTableConstraintDefinition($table_name, $constraint_name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        
        list($schema, $table) = $this->splitTableSchema($table_name);

        $query = "SELECT c.oid,
                         c.conname AS constraint_name,
                         CASE WHEN c.contype = 'c' THEN 1 ELSE 0 END AS \"check\",
                         CASE WHEN c.contype = 'f' THEN 1 ELSE 0 END AS \"foreign\",
                         CASE WHEN c.contype = 'p' THEN 1 ELSE 0 END AS \"primary\",
                         CASE WHEN c.contype = 'u' THEN 1 ELSE 0 END AS \"unique\",
                         CASE WHEN c.condeferrable = 'f' THEN 0 ELSE 1 END AS deferrable,
                         CASE WHEN c.condeferred = 'f' THEN 0 ELSE 1 END AS initiallydeferred,
                         --array_to_string(c.conkey, ' ') AS constraint_key,
                         t.relname AS table_name,
                         t2.relname AS references_table,
                         CASE confupdtype
                           WHEN 'a' THEN 'NO ACTION'
                           WHEN 'r' THEN 'RESTRICT'
                           WHEN 'c' THEN 'CASCADE'
                           WHEN 'n' THEN 'SET NULL'
                           WHEN 'd' THEN 'SET DEFAULT'
                         END AS onupdate,
                         CASE confdeltype
                           WHEN 'a' THEN 'NO ACTION'
                           WHEN 'r' THEN 'RESTRICT'
                           WHEN 'c' THEN 'CASCADE'
                           WHEN 'n' THEN 'SET NULL'
                           WHEN 'd' THEN 'SET DEFAULT'
                         END AS ondelete,
                         CASE confmatchtype
                           WHEN 'u' THEN 'UNSPECIFIED'
                           WHEN 'f' THEN 'FULL'
                           WHEN 'p' THEN 'PARTIAL'
                         END AS match,
                         --array_to_string(c.confkey, ' ') AS fk_constraint_key,
                         consrc
                    FROM pg_constraint c
               LEFT JOIN pg_class t  ON c.conrelid  = t.oid
               LEFT JOIN pg_class t2 ON c.confrelid = t2.oid
                   WHERE c.conname = %s
                     AND t.relname = " . $db->quote($table, 'text');
        $constraint_name_mdb2 = $db->getIndexName($constraint_name);
        $row = $db->queryRow(sprintf($query, $db->quote($constraint_name_mdb2, 'text')), null, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($row) || empty($row)) {
            // fallback to the given $index_name, without transformation
            $constraint_name_mdb2 = $constraint_name;
            $row = $db->queryRow(sprintf($query, $db->quote($constraint_name_mdb2, 'text')), null, MDB2_FETCHMODE_ASSOC);
        }
        if (PEAR::isError($row)) {
            return $row;
        }
        $uniqueIndex = false;
        if (empty($row)) {
            // We might be looking for a UNIQUE index that was not created
            // as a constraint but should be treated as such.
            $query = 'SELECT relname AS constraint_name,
                             indkey,
                             0 AS "check",
                             0 AS "foreign",
                             0 AS "primary",
                             1 AS "unique",
                             0 AS deferrable,
                             0 AS initiallydeferred,
                             NULL AS references_table,
                             NULL AS onupdate,
                             NULL AS ondelete,
                             NULL AS match
                        FROM pg_index, pg_class
                       WHERE pg_class.oid = pg_index.indexrelid
                         AND indisunique = \'t\'
                         AND pg_class.relname = %s';
            $constraint_name_mdb2 = $db->getIndexName($constraint_name);
            $row = $db->queryRow(sprintf($query, $db->quote($constraint_name_mdb2, 'text')), null, MDB2_FETCHMODE_ASSOC);
            if (PEAR::isError($row) || empty($row)) {
                // fallback to the given $index_name, without transformation
                $constraint_name_mdb2 = $constraint_name;
                $row = $db->queryRow(sprintf($query, $db->quote($constraint_name_mdb2, 'text')), null, MDB2_FETCHMODE_ASSOC);
            }
            if (PEAR::isError($row)) {
                return $row;
            }
            if (empty($row)) {
                return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                    $constraint_name . ' is not an existing table constraint', __FUNCTION__);
            }
            $uniqueIndex = true;
        }

        $row = array_change_key_case($row, CASE_LOWER);

        $definition = array(
            'primary' => (boolean)$row['primary'],
            'unique'  => (boolean)$row['unique'],
            'foreign' => (boolean)$row['foreign'],
            'check'   => (boolean)$row['check'],
            'fields'  => array(),
            'references' => array(
                'table'  => $row['references_table'],
                'fields' => array(),
            ),
            'deferrable' => (boolean)$row['deferrable'],
            'initiallydeferred' => (boolean)$row['initiallydeferred'],
            'onupdate' => $row['onupdate'],
            'ondelete' => $row['ondelete'],
            'match'    => $row['match'],
        );

        if ($uniqueIndex) {
            $db->loadModule('Manager', null, true);
            $columns = $db->manager->listTableFields($table_name);
            $index_column_numbers = explode(' ', $row['indkey']);
            $colpos = 1;
            foreach ($index_column_numbers as $number) {
                $definition['fields'][$columns[($number - 1)]] = array(
                    'position' => $colpos++,
                    'sorting'  => 'ascending',
                );
            }
            return $definition;
        }

        $query = 'SELECT a.attname
                    FROM pg_constraint c
               LEFT JOIN pg_class t  ON c.conrelid  = t.oid
               LEFT JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(c.conkey)
                   WHERE c.conname = %s
                     AND t.relname = ' . $db->quote($table, 'text');
        $fields = $db->queryCol(sprintf($query, $db->quote($constraint_name_mdb2, 'text')), null);
        if (PEAR::isError($fields)) {
            return $fields;
        }
        $colpos = 1;
        foreach ($fields as $field) {
            $definition['fields'][$field] = array(
                'position' => $colpos++,
                'sorting' => 'ascending',
            );
        }
        
        if ($definition['foreign']) {
            $query = 'SELECT a.attname
                        FROM pg_constraint c
                   LEFT JOIN pg_class t  ON c.confrelid  = t.oid
                   LEFT JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(c.confkey)
                       WHERE c.conname = %s
                         AND t.relname = ' . $db->quote($definition['references']['table'], 'text');
            $foreign_fields = $db->queryCol(sprintf($query, $db->quote($constraint_name_mdb2, 'text')), null);
            if (PEAR::isError($foreign_fields)) {
                return $foreign_fields;
            }
            $colpos = 1;
            foreach ($foreign_fields as $foreign_field) {
                $definition['references']['fields'][$foreign_field] = array(
                    'position' => $colpos++,
                );
            }
        }
        
        if ($definition['check']) {
            $check_def = $db->queryOne("SELECT pg_get_constraintdef(" . $row['oid'] . ", 't')");
            // ...
        }
        return $definition;
    }

    // }}}
    // {{{ getTriggerDefinition()

    /**
     * Get the structure of a trigger into an array
     *
     * EXPERIMENTAL
     *
     * WARNING: this function is experimental and may change the returned value
     * at any time until labelled as non-experimental
     *
     * @param string $trigger name of trigger that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     *
     * @TODO: add support for plsql functions and functions with args
     */
    function getTriggerDefinition($trigger)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = "SELECT trg.tgname AS trigger_name,
                         tbl.relname AS table_name,
                         CASE
                            WHEN p.proname IS NOT NULL THEN 'EXECUTE PROCEDURE ' || p.proname || '();'
                            ELSE ''
                         END AS trigger_body,
                         CASE trg.tgtype & cast(2 as int2)
                            WHEN 0 THEN 'AFTER'
                            ELSE 'BEFORE'
                         END AS trigger_type,
                         CASE trg.tgtype & cast(28 as int2)
                            WHEN 16 THEN 'UPDATE'
                            WHEN 8 THEN 'DELETE'
                            WHEN 4 THEN 'INSERT'
                            WHEN 20 THEN 'INSERT, UPDATE'
                            WHEN 28 THEN 'INSERT, UPDATE, DELETE'
                            WHEN 24 THEN 'UPDATE, DELETE'
                            WHEN 12 THEN 'INSERT, DELETE'
                         END AS trigger_event,
                         CASE trg.tgenabled
                            WHEN 'O' THEN 't'
                            ELSE trg.tgenabled
                         END AS trigger_enabled,
                         obj_description(trg.oid, 'pg_trigger') AS trigger_comment
                    FROM pg_trigger trg,
                         pg_class tbl,
                         pg_proc p
                   WHERE trg.tgrelid = tbl.oid
                     AND trg.tgfoid = p.oid
                     AND trg.tgname = ". $db->quote($trigger, 'text');
        $types = array(
            'trigger_name'    => 'text',
            'table_name'      => 'text',
            'trigger_body'    => 'text',
            'trigger_type'    => 'text',
            'trigger_event'   => 'text',
            'trigger_comment' => 'text',
            'trigger_enabled' => 'boolean',
        );
        return $db->queryRow($query, $types, MDB2_FETCHMODE_ASSOC);
    }
    
    // }}}
    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set
     *
     * NOTE: only supports 'table' and 'flags' if <var>$result</var>
     * is a table name.
     *
     * @param object|string  $result  MDB2_result object from a query or a
     *                                 string containing the name of a table.
     *                                 While this also accepts a query result
     *                                 resource identifier, this behavior is
     *                                 deprecated.
     * @param int            $mode    a valid tableInfo mode
     *
     * @return array  an associative array with the information requested.
     *                 A MDB2_Error object on failure.
     *
     * @see MDB2_Driver_Common::tableInfo()
     */
    function tableInfo($result, $mode = null)
    {
        if (is_string($result)) {
           return parent::tableInfo($result, $mode);
        }

        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $resource = MDB2::isResultCommon($result) ? $result->getResource() : $result;
        if (!is_resource($resource)) {
            return $db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                'Could not generate result resource', __FUNCTION__);
        }

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            if ($db->options['field_case'] == CASE_LOWER) {
                $case_func = 'strtolower';
            } else {
                $case_func = 'strtoupper';
            }
        } else {
            $case_func = 'strval';
        }

        $count = @pg_num_fields($resource);
        $res   = array();

        if ($mode) {
            $res['num_fields'] = $count;
        }

        $db->loadModule('Datatype', null, true);
        for ($i = 0; $i < $count; $i++) {
            $res[$i] = array(
                'table' => function_exists('pg_field_table') ? @pg_field_table($resource, $i) : '',
                'name'  => $case_func(@pg_field_name($resource, $i)),
                'type'  => @pg_field_type($resource, $i),
                'length' => @pg_field_size($resource, $i),
                'flags' => '',
            );
            $mdb2type_info = $db->datatype->mapNativeDatatype($res[$i]);
            if (PEAR::isError($mdb2type_info)) {
               return $mdb2type_info;
            }
            $res[$i]['mdb2type'] = $mdb2type_info[0][0];
            if ($mode & MDB2_TABLEINFO_ORDER) {
                $res['order'][$res[$i]['name']] = $i;
            }
            if ($mode & MDB2_TABLEINFO_ORDERTABLE) {
                $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
            }
        }

        return $res;
    }
}
?>