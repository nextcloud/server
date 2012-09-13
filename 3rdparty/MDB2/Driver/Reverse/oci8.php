<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2007 Manuel Lemos, Tomas V.V.Cox,                 |
// | Stig. S. Bakken, Lukas Smith, Frank M. Kromann, Lorenzo Alberton     |
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
// $Id: oci8.php 295587 2010-02-28 17:16:38Z quipo $
//

require_once 'MDB2/Driver/Reverse/Common.php';

/**
 * MDB2 Oracle driver for the schema reverse engineering module
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@dybnet.de>
 */
class MDB2_Driver_Reverse_oci8 extends MDB2_Driver_Reverse_Common
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

        list($owner, $table) = $this->splitTableSchema($table_name);
        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }

        $query = 'SELECT column_name AS "name",
                         data_type AS "type",
                         nullable AS "nullable",
                         data_default AS "default",
                         COALESCE(data_precision, data_length) AS "length",
                         data_scale AS "scale"
                    FROM all_tab_columns
                   WHERE (table_name=? OR table_name=?)
                     AND (owner=? OR owner=?)
                     AND (column_name=? OR column_name=?)
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
            $field_name,
            strtoupper($field_name)
        );
        $result = $stmt->execute($args);
        if (PEAR::isError($result)) {
            return $result;
        }
        $column = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($column)) {
            return $column;
        }
        $stmt->free();
        $result->free();

        if (empty($column)) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                $field_name . ' is not a column in table ' . $table_name, __FUNCTION__);
        }

        $column = array_change_key_case($column, CASE_LOWER);
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            if ($db->options['field_case'] == CASE_LOWER) {
                $column['name'] = strtolower($column['name']);
            } else {
                $column['name'] = strtoupper($column['name']);
            }
        }
        $mapped_datatype = $db->datatype->mapNativeDatatype($column);
        if (PEAR::isError($mapped_datatype)) {
            return $mapped_datatype;
        }
        list($types, $length, $unsigned, $fixed) = $mapped_datatype;
        $notnull = false;
        if (!empty($column['nullable']) && $column['nullable'] == 'N') {
            $notnull = true;
        }
        $default = false;
        if (array_key_exists('default', $column)) {
            $default = $column['default'];
            if ($default === 'NULL') {
                $default = null;
            }
			//ugly hack, but works for the reverse direction
			if ($default == "''") {
				$default = '';
			}
            if ((null === $default) && $notnull) {
                $default = '';
            }
        }

        $definition[0] = array('notnull' => $notnull, 'nativetype' => $column['type']);
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
        foreach ($types as $key => $type) {
            $definition[$key] = $definition[0];
            if ($type == 'clob' || $type == 'blob') {
                unset($definition[$key]['default']);
            }
            $definition[$key]['type'] = $type;
            $definition[$key]['mdb2type'] = $type;
        }
        if ($type == 'integer') {
            $query= "SELECT trigger_body
                       FROM all_triggers
                      WHERE table_name=?
                        AND triggering_event='INSERT'
                        AND trigger_type='BEFORE EACH ROW'";
			// ^^ pretty reasonable mimic for "auto_increment" in oracle?
			$stmt = $db->prepare($query);
            if (PEAR::isError($stmt)) {
                return $stmt;
            }
			$result = $stmt->execute(strtoupper($table));
	        if (PEAR::isError($result)) {
	            return $result;
	        }
	        while ($triggerstr = $result->fetchOne()) {
	           	if (preg_match('/.*SELECT\W+(.+)\.nextval +into +\:NEW\.'.$field_name.' +FROM +dual/im', $triggerstr, $matches)) {
					$definition[0]['autoincrement'] = $matches[1];
                }
            }
	        $stmt->free();
	        $result->free();
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        
        list($owner, $table) = $this->splitTableSchema($table_name);
        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }

        $query = 'SELECT aic.column_name AS "column_name",
                         aic.column_position AS "column_position",
                         aic.descend AS "descend",
                         aic.table_owner AS "table_owner",
                         alc.constraint_type AS "constraint_type"
                    FROM all_ind_columns aic
               LEFT JOIN all_constraints alc
                      ON aic.index_name = alc.constraint_name
                     AND aic.table_name = alc.table_name
                     AND aic.table_owner = alc.owner
                   WHERE (aic.table_name=? OR aic.table_name=?)
                     AND (aic.index_name=? OR aic.index_name=?)
                     AND (aic.table_owner=? OR aic.table_owner=?)
                ORDER BY column_position';
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        $indexnames = array_unique(array($db->getIndexName($index_name), $index_name));
        $i = 0;
        $row = null;
        while ((null === $row) && array_key_exists($i, $indexnames)) {
            $args = array(
                $table,
                strtoupper($table),
                $indexnames[$i],
                strtoupper($indexnames[$i]),
                $owner,
                strtoupper($owner)
            );
        	$result = $stmt->execute($args);
        	if (PEAR::isError($result)) {
                return $result;
            }
        	$row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        	if (PEAR::isError($row)) {
                return $row;
            }
        	$i++;
        }
        if (null === $row) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                $index_name. ' is not an index on table '. $table_name, __FUNCTION__);
        }
        if ($row['constraint_type'] == 'U' || $row['constraint_type'] == 'P') {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                $index_name. ' is a constraint, not an index on table '. $table_name, __FUNCTION__);
        }

        $definition = array();
        while (null !== $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $column_name = $row['column_name'];
            if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                if ($db->options['field_case'] == CASE_LOWER) {
                    $column_name = strtolower($column_name);
                } else {
                    $column_name = strtoupper($column_name);
                }
            }
            $definition['fields'][$column_name] = array(
                'position' => (int)$row['column_position'],
            );
            if (!empty($row['descend'])) {
                $definition['fields'][$column_name]['sorting'] =
                    ($row['descend'] == 'ASC' ? 'ascending' : 'descending');
            }
            $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        }
        $result->free();
        if (empty($definition['fields'])) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                $index_name. ' is not an index on table '. $table_name, __FUNCTION__);
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
        
        list($owner, $table) = $this->splitTableSchema($table_name);
        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }
        
        $query = 'SELECT alc.constraint_name,
                         CASE alc.constraint_type WHEN \'P\' THEN 1 ELSE 0 END "primary",
                         CASE alc.constraint_type WHEN \'R\' THEN 1 ELSE 0 END "foreign",
                         CASE alc.constraint_type WHEN \'U\' THEN 1 ELSE 0 END "unique",
                         CASE alc.constraint_type WHEN \'C\' THEN 1 ELSE 0 END "check",
                         alc.DELETE_RULE "ondelete",
                         \'NO ACTION\' "onupdate",
                         \'SIMPLE\' "match",
                         CASE alc.deferrable WHEN \'NOT DEFERRABLE\' THEN 0 ELSE 1 END "deferrable",
                         CASE alc.deferred WHEN \'IMMEDIATE\' THEN 0 ELSE 1 END "initiallydeferred",
                         alc.search_condition AS "search_condition",
                         alc.table_name,
                         cols.column_name AS "column_name",
                         cols.position,
                         r_alc.table_name "references_table",
                         r_cols.column_name "references_field",
                         r_cols.position "references_field_position"
                    FROM all_cons_columns cols
               LEFT JOIN all_constraints alc
                      ON alc.constraint_name = cols.constraint_name
                     AND alc.owner = cols.owner
               LEFT JOIN all_constraints r_alc
                      ON alc.r_constraint_name = r_alc.constraint_name
                     AND alc.r_owner = r_alc.owner
               LEFT JOIN all_cons_columns r_cols
                      ON r_alc.constraint_name = r_cols.constraint_name
                     AND r_alc.owner = r_cols.owner
                     AND cols.position = r_cols.position
                   WHERE (alc.constraint_name=? OR alc.constraint_name=?)
                     AND alc.constraint_name = cols.constraint_name
                     AND (alc.owner=? OR alc.owner=?)';
        $tablenames = array();
        if (!empty($table)) {
            $query.= ' AND (alc.table_name=? OR alc.table_name=?)';
            $tablenames = array($table, strtoupper($table));
        }
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        
        $constraintnames = array_unique(array($db->getIndexName($constraint_name), $constraint_name));
        $c = 0;
        $row = null;
        while ((null === $row) && array_key_exists($c, $constraintnames)) {
            $args = array(
                $constraintnames[$c],
                strtoupper($constraintnames[$c]),
                $owner,
                strtoupper($owner)
            );
            if (!empty($table)) {
                $args = array_merge($args, $tablenames);
            }
            $result = $stmt->execute($args);
            if (PEAR::isError($result)) {
                return $result;
            }
            $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
            if (PEAR::isError($row)) {
                return $row;
            }
            $c++;
        }

        $definition = array(
            'primary' => (boolean)$row['primary'],
            'unique'  => (boolean)$row['unique'],
            'foreign' => (boolean)$row['foreign'],
            'check'   => (boolean)$row['check'],
            'deferrable' => (boolean)$row['deferrable'],
            'initiallydeferred' => (boolean)$row['initiallydeferred'],
            'ondelete' => $row['ondelete'],
            'onupdate' => $row['onupdate'],
            'match'    => $row['match'],
        );

        if ($definition['check']) {
            // pattern match constraint for check constraint values into enum-style output:
			$enumregex = '/'.$row['column_name'].' in \((.+?)\)/i';
			if (preg_match($enumregex, $row['search_condition'], $rangestr)) {
				$definition['fields'][$column_name] = array();
				$allowed = explode(',', $rangestr[1]);
				foreach ($allowed as $val) {
					$val = trim($val);
					$val = preg_replace('/^\'/', '', $val);
					$val = preg_replace('/\'$/', '', $val);
					array_push($definition['fields'][$column_name], $val);
				}
			}
		}
        
        while (null !== $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $column_name = $row['column_name'];
            if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                if ($db->options['field_case'] == CASE_LOWER) {
                    $column_name = strtolower($column_name);
                } else {
                    $column_name = strtoupper($column_name);
                }
            }
            $definition['fields'][$column_name] = array(
                'position' => (int)$row['position']
            );
            if ($row['foreign']) {
                $ref_column_name = $row['references_field'];
                $ref_table_name  = $row['references_table'];
                if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                    if ($db->options['field_case'] == CASE_LOWER) {
                        $ref_column_name = strtolower($ref_column_name);
                        $ref_table_name  = strtolower($ref_table_name);
                    } else {
                        $ref_column_name = strtoupper($ref_column_name);
                        $ref_table_name  = strtoupper($ref_table_name);
                    }
                }
                $definition['references']['table'] = $ref_table_name;
                $definition['references']['fields'][$ref_column_name] = array(
                    'position' => (int)$row['references_field_position']
                );
            }
            $lastrow = $row;
            $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        }
        $result->free();
        if (empty($definition['fields'])) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                $constraint_name . ' is not a constraint on table '. $table_name, __FUNCTION__);
        }

        return $definition;
    }

    // }}}
    // {{{ getSequenceDefinition()

    /**
     * Get the structure of a sequence into an array
     *
     * @param string    $sequence   name of sequence that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function getSequenceDefinition($sequence)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $sequence_name = $db->getSequenceName($sequence);
        $query = 'SELECT last_number FROM user_sequences';
        $query.= ' WHERE sequence_name='.$db->quote($sequence_name, 'text');
        $query.= '    OR sequence_name='.$db->quote(strtoupper($sequence_name), 'text');
        $start = $db->queryOne($query, 'integer');
        if (PEAR::isError($start)) {
            return $start;
        }
        $definition = array();
        if ($start != 1) {
            $definition = array('start' => $start);
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

        $query = 'SELECT trigger_name AS "trigger_name",
                         table_name AS "table_name",
                         trigger_body AS "trigger_body",
                         trigger_type AS "trigger_type",
                         triggering_event AS "trigger_event",
                         description AS "trigger_comment",
                         1 AS "trigger_enabled",
                         when_clause AS "when_clause"
                    FROM user_triggers
                   WHERE trigger_name = \''. strtoupper($trigger).'\'';
        $types = array(
            'trigger_name'    => 'text',
            'table_name'      => 'text',
            'trigger_body'    => 'text',
            'trigger_type'    => 'text',
            'trigger_event'   => 'text',
            'trigger_comment' => 'text',
            'trigger_enabled' => 'boolean',
            'when_clause'     => 'text',
        );
        $result = $db->queryRow($query, $types, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($result)) {
            return $result;
        }
        if (!empty($result['trigger_type'])) {
            //$result['trigger_type'] = array_shift(explode(' ', $result['trigger_type']));
            $result['trigger_type'] = preg_replace('/(\S+).*/', '\\1', $result['trigger_type']);
        }
        return $result;
    }

    // }}}
    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set
     *
     * NOTE: only supports 'table' and 'flags' if <var>$result</var>
     * is a table name.
     *
     * NOTE: flags won't contain index information.
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

        $count = @OCINumCols($resource);
        $res = array();

        if ($mode) {
            $res['num_fields'] = $count;
        }

        $db->loadModule('Datatype', null, true);
        for ($i = 0; $i < $count; $i++) {
            $column = array(
                'table'  => '',
                'name'   => $case_func(@OCIColumnName($resource, $i+1)),
                'type'   => @OCIColumnType($resource, $i+1),
                'length' => @OCIColumnSize($resource, $i+1),
                'flags'  => '',
            );
            $res[$i] = $column;
            $res[$i]['mdb2type'] = $db->datatype->mapNativeDatatype($res[$i]);
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