<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2007 Manuel Lemos, Tomas V.V.Cox,                 |
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

require_once 'MDB2/Driver/Reverse/Common.php';

/**
 * MDB2 MySQL driver for the schema reverse engineering module
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 * @author  Lorenzo Alberton <l.alberton@quipo.it>
 */
class MDB2_Driver_Reverse_mysql extends MDB2_Driver_Reverse_Common
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $result = $db->loadModule('Datatype', null, true);
        if (PEAR::isError($result)) {
            return $result;
        }

        list($schema, $table) = $this->splitTableSchema($table_name);

        $table = $db->quoteIdentifier($table, true);
        $query = "SHOW FULL COLUMNS FROM $table LIKE ".$db->quote($field_name);
        $columns = $db->queryAll($query, null, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($columns)) {
            return $columns;
        }
        foreach ($columns as $column) {
            $column = array_change_key_case($column, CASE_LOWER);
            $column['name'] = $column['field'];
            unset($column['field']);
            if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                if ($db->options['field_case'] == CASE_LOWER) {
                    $column['name'] = strtolower($column['name']);
                } else {
                    $column['name'] = strtoupper($column['name']);
                }
            } else {
                $column = array_change_key_case($column, $db->options['field_case']);
            }
            if ($field_name == $column['name']) {
                $mapped_datatype = $db->datatype->mapNativeDatatype($column);
                if (PEAR::isError($mapped_datatype)) {
                    return $mapped_datatype;
                }
                list($types, $length, $unsigned, $fixed) = $mapped_datatype;
                $notnull = false;
                if (empty($column['null']) || $column['null'] !== 'YES') {
                    $notnull = true;
                }
                $default = false;
                if (array_key_exists('default', $column)) {
                    $default = $column['default'];
                    if ((null === $default) && $notnull) {
                        $default = '';
                    }
                }
                $definition[0] = array(
                    'notnull' => $notnull,
                    'nativetype' => preg_replace('/^([a-z]+)[^a-z].*/i', '\\1', $column['type'])
                );
                $autoincrement = false;
                if (!empty($column['extra'])) {
                    if ($column['extra'] == 'auto_increment') {
                        $autoincrement = true;
                    } else {
                        $definition[0]['extra'] = $column['extra'];
                    }
                }
                $collate = null;
                if (!empty($column['collation'])) {
                    $collate = $column['collation'];
                    $charset = preg_replace('/(.+?)(_.+)?/', '$1', $collate);
                }

                if (null !== $length) {
                    $definition[0]['length'] = $length;
                }
                if (null !== $unsigned) {
                    $definition[0]['unsigned'] = $unsigned;
                }
                if (null !== $fixed) {
                    $definition[0]['fixed'] = $fixed;
                }
                if ($default !== false) {
                    $definition[0]['default'] = $default;
                }
                if ($autoincrement !== false) {
                    $definition[0]['autoincrement'] = $autoincrement;
                }
                if (null !== $collate) {
                    $definition[0]['collate'] = $collate;
                    $definition[0]['charset'] = $charset;
                }
                foreach ($types as $key => $type) {
                    $definition[$key] = $definition[0];
                    if ($type == 'clob' || $type == 'blob') {
                        unset($definition[$key]['default']);
                    } elseif ($type == 'timestamp' && $notnull && empty($definition[$key]['default'])) {
                        $definition[$key]['default'] = '0000-00-00 00:00:00';
                    }
                    $definition[$key]['type'] = $type;
                    $definition[$key]['mdb2type'] = $type;
                }
                return $definition;
            }
        }

        return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
            'it was not specified an existing table column', __FUNCTION__);
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        list($schema, $table) = $this->splitTableSchema($table_name);

        $table = $db->quoteIdentifier($table, true);
        $query = "SHOW INDEX FROM $table /*!50002 WHERE Key_name = %s */";
        $index_name_mdb2 = $db->getIndexName($index_name);
        $result = $db->queryRow(sprintf($query, $db->quote($index_name_mdb2)));
        if (!PEAR::isError($result) && (null !== $result)) {
            // apply 'idxname_format' only if the query succeeded, otherwise
            // fallback to the given $index_name, without transformation
            $index_name = $index_name_mdb2;
        }
        $result = $db->query(sprintf($query, $db->quote($index_name)));
        if (PEAR::isError($result)) {
            return $result;
        }
        $colpos = 1;
        $definition = array();
        while (is_array($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC))) {
            $row = array_change_key_case($row, CASE_LOWER);
            $key_name = $row['key_name'];
            if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                if ($db->options['field_case'] == CASE_LOWER) {
                    $key_name = strtolower($key_name);
                } else {
                    $key_name = strtoupper($key_name);
                }
            }
            if ($index_name == $key_name) {
                if (!$row['non_unique']) {
                    return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                        $index_name . ' is not an existing table index', __FUNCTION__);
                }
                $column_name = $row['column_name'];
                if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                    if ($db->options['field_case'] == CASE_LOWER) {
                        $column_name = strtolower($column_name);
                    } else {
                        $column_name = strtoupper($column_name);
                    }
                }
                $definition['fields'][$column_name] = array(
                    'position' => $colpos++
                );
                if (!empty($row['collation'])) {
                    $definition['fields'][$column_name]['sorting'] = ($row['collation'] == 'A'
                        ? 'ascending' : 'descending');
                }
            }
        }
        $result->free();
        if (empty($definition['fields'])) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                $index_name . ' is not an existing table index', __FUNCTION__);
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        list($schema, $table) = $this->splitTableSchema($table_name);
        $constraint_name_original = $constraint_name;

        $table = $db->quoteIdentifier($table, true);
        $query = "SHOW INDEX FROM $table /*!50002 WHERE Key_name = %s */";
        if (strtolower($constraint_name) != 'primary') {
            $constraint_name_mdb2 = $db->getIndexName($constraint_name);
            $result = $db->queryRow(sprintf($query, $db->quote($constraint_name_mdb2)));
            if (!PEAR::isError($result) && (null !== $result)) {
                // apply 'idxname_format' only if the query succeeded, otherwise
                // fallback to the given $index_name, without transformation
                $constraint_name = $constraint_name_mdb2;
            }
        }
        $result = $db->query(sprintf($query, $db->quote($constraint_name)));
        if (PEAR::isError($result)) {
            return $result;
        }
        $colpos = 1;
        //default values, eventually overridden
        $definition = array(
            'primary' => false,
            'unique'  => false,
            'foreign' => false,
            'check'   => false,
            'fields'  => array(),
            'references' => array(
                'table'  => '',
                'fields' => array(),
            ),
            'onupdate'  => '',
            'ondelete'  => '',
            'match'     => '',
            'deferrable'        => false,
            'initiallydeferred' => false,
        );
        while (is_array($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC))) {
            $row = array_change_key_case($row, CASE_LOWER);
            $key_name = $row['key_name'];
            if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                if ($db->options['field_case'] == CASE_LOWER) {
                    $key_name = strtolower($key_name);
                } else {
                    $key_name = strtoupper($key_name);
                }
            }
            if ($constraint_name == $key_name) {
                if ($row['non_unique']) {
                    //FOREIGN KEY?
                    return $this->_getTableFKConstraintDefinition($table, $constraint_name_original, $definition);
                }
                if ($row['key_name'] == 'PRIMARY') {
                    $definition['primary'] = true;
                } elseif (!$row['non_unique']) {
                    $definition['unique'] = true;
                }
                $column_name = $row['column_name'];
                if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                    if ($db->options['field_case'] == CASE_LOWER) {
                        $column_name = strtolower($column_name);
                    } else {
                        $column_name = strtoupper($column_name);
                    }
                }
                $definition['fields'][$column_name] = array(
                    'position' => $colpos++
                );
                if (!empty($row['collation'])) {
                    $definition['fields'][$column_name]['sorting'] = ($row['collation'] == 'A'
                        ? 'ascending' : 'descending');
                }
            }
        }
        $result->free();
        if (empty($definition['fields'])) {
            return $this->_getTableFKConstraintDefinition($table, $constraint_name_original, $definition);
        }
        return $definition;
    }

    // }}}
    // {{{ _getTableFKConstraintDefinition()
    
    /**
     * Get the FK definition from the CREATE TABLE statement
     *
     * @param string $table           table name
     * @param string $constraint_name constraint name
     * @param array  $definition      default values for constraint definition
     *
     * @return array|PEAR_Error
     * @access private
     */
    function _getTableFKConstraintDefinition($table, $constraint_name, $definition)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        //Use INFORMATION_SCHEMA instead?
        //SELECT *
        //  FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
        // WHERE CONSTRAINT_SCHEMA = '$dbname'
        //   AND TABLE_NAME = '$table'
        //   AND CONSTRAINT_NAME = '$constraint_name';
        $query = 'SHOW CREATE TABLE '. $db->escape($table);
        $constraint = $db->queryOne($query, 'text', 1);
        if (!PEAR::isError($constraint) && !empty($constraint)) {
            if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                if ($db->options['field_case'] == CASE_LOWER) {
                    $constraint = strtolower($constraint);
                } else {
                    $constraint = strtoupper($constraint);
                }
            }
            $constraint_name_original = $constraint_name;
            $constraint_name = $db->getIndexName($constraint_name);
            $pattern = '/\bCONSTRAINT\s+'.$constraint_name.'\s+FOREIGN KEY\s+\(([^\)]+)\) \bREFERENCES\b ([^\s]+) \(([^\)]+)\)(?: ON DELETE ([^\s]+))?(?: ON UPDATE ([^\s]+))?/i';
            if (!preg_match($pattern, str_replace('`', '', $constraint), $matches)) {
                //fallback to original constraint name
                $pattern = '/\bCONSTRAINT\s+'.$constraint_name_original.'\s+FOREIGN KEY\s+\(([^\)]+)\) \bREFERENCES\b ([^\s]+) \(([^\)]+)\)(?: ON DELETE ([^\s]+))?(?: ON UPDATE ([^\s]+))?/i';
            }
            if (preg_match($pattern, str_replace('`', '', $constraint), $matches)) {
                $definition['foreign'] = true;
                $column_names = explode(',', $matches[1]);
                $referenced_cols = explode(',', $matches[3]);
                $definition['references'] = array(
                    'table'  => $matches[2],
                    'fields' => array(),
                );
                $colpos = 1;
                foreach ($column_names as $column_name) {
                    $definition['fields'][trim($column_name)] = array(
                        'position' => $colpos++
                    );
                }
                $colpos = 1;
                foreach ($referenced_cols as $column_name) {
                    $definition['references']['fields'][trim($column_name)] = array(
                        'position' => $colpos++
                    );
                }
                $definition['ondelete'] = empty($matches[4]) ? 'RESTRICT' : strtoupper($matches[4]);
                $definition['onupdate'] = empty($matches[5]) ? 'RESTRICT' : strtoupper($matches[5]);
                $definition['match']    = 'SIMPLE';
                return $definition;
            }
        }
        return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                $constraint_name . ' is not an existing table constraint', __FUNCTION__);
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
     * @param string    $trigger    name of trigger that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function getTriggerDefinition($trigger)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = 'SELECT trigger_name,
                         event_object_table AS table_name,
                         action_statement AS trigger_body,
                         action_timing AS trigger_type,
                         event_manipulation AS trigger_event
                    FROM information_schema.triggers
                   WHERE trigger_name = '. $db->quote($trigger, 'text');
        $types = array(
            'trigger_name'    => 'text',
            'table_name'      => 'text',
            'trigger_body'    => 'text',
            'trigger_type'    => 'text',
            'trigger_event'   => 'text',
        );
        $def = $db->queryRow($query, $types, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($def)) {
            return $def;
        }
        $def['trigger_comment'] = '';
        $def['trigger_enabled'] = true;
        return $def;
    }

    // }}}
    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set
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
     * @see MDB2_Driver_Common::setOption()
     */
    function tableInfo($result, $mode = null)
    {
        if (is_string($result)) {
           return parent::tableInfo($result, $mode);
        }

        $db = $this->getDBInstance();
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

        $count = @mysql_num_fields($resource);
        $res   = array();
        if ($mode) {
            $res['num_fields'] = $count;
        }

        $db->loadModule('Datatype', null, true);
        for ($i = 0; $i < $count; $i++) {
            $res[$i] = array(
                'table'  => $case_func(@mysql_field_table($resource, $i)),
                'name'   => $case_func(@mysql_field_name($resource, $i)),
                'type'   => @mysql_field_type($resource, $i),
                'length' => @mysql_field_len($resource, $i),
                'flags'  => @mysql_field_flags($resource, $i),
            );
            if ($res[$i]['type'] == 'string') {
                $res[$i]['type'] = 'char';
            } elseif ($res[$i]['type'] == 'unknown') {
                $res[$i]['type'] = 'decimal';
            }
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