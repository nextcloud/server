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

require_once('MDB2/Driver/Datatype/Common.php');

/**
 * MDB2 SQLite driver
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Driver_Datatype_sqlite3 extends MDB2_Driver_Datatype_Common
{
    // {{{ _getCollationFieldDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to set the COLLATION
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param string $collation name of the collation
     *
     * @return string DBMS specific SQL code portion needed to set the COLLATION
     *                of a field declaration.
     */
    function _getCollationFieldDeclaration($collation)
    {
        return 'COLLATE '.$collation;
    }

    // }}}
    // {{{ getTypeDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param array $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the text
     *          field. If this argument is missing the field should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      default
     *          Text value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access public
     */
    function getTypeDeclaration($field)
    {
        $db =$this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        switch ($field['type']) {
        case 'text':
            $length = !empty($field['length'])
                ? $field['length'] : false;
            $fixed = !empty($field['fixed']) ? $field['fixed'] : false;
            return $fixed ? ($length ? 'CHAR('.$length.')' : 'CHAR('.$db->options['default_text_field_length'].')')
                : ($length ? 'VARCHAR('.$length.')' : 'TEXT');
        case 'clob':
            if (!empty($field['length'])) {
                $length = $field['length'];
                if ($length <= 255) {
                    return 'TINYTEXT';
                } elseif ($length <= 65532) {
                    return 'TEXT';
                } elseif ($length <= 16777215) {
                    return 'MEDIUMTEXT';
                }
            }
            return 'LONGTEXT';
        case 'blob':
            if (!empty($field['length'])) {
                $length = $field['length'];
                if ($length <= 255) {
                    return 'TINYBLOB';
                } elseif ($length <= 65532) {
                    return 'BLOB';
                } elseif ($length <= 16777215) {
                    return 'MEDIUMBLOB';
                }
            }
            return 'LONGBLOB';
        case 'integer':
            if (!empty($field['length'])) {
                $length = $field['length'];
                if ($length <= 2) {
                    return 'SMALLINT';
                } elseif ($length == 3 || $length == 4) {
                    return 'INTEGER';
                } elseif ($length > 4) {
                    return 'BIGINT';
                }
            }
            return 'INTEGER';
        case 'boolean':
            return 'BOOLEAN';
        case 'date':
            return 'DATE';
        case 'time':
            return 'TIME';
        case 'timestamp':
            return 'DATETIME';
        case 'float':
            return 'DOUBLE'.($db->options['fixed_float'] ? '('.
                ($db->options['fixed_float']+2).','.$db->options['fixed_float'].')' : '');
        case 'decimal':
            $length = !empty($field['length']) ? $field['length'] : 18;
            $scale = !empty($field['scale']) ? $field['scale'] : $db->options['decimal_places'];
            return 'DECIMAL('.$length.','.$scale.')';
        }
        return '';
    }

    // }}}
    // {{{ _getIntegerDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       unsigned
     *                        Boolean flag that indicates whether the field
     *                        should be declared as unsigned integer if
     *                        possible.
     *
     *                       default
     *                        Integer value to be used as default for this
     *                        field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access protected
     */
    function _getIntegerDeclaration($name, $field)
    {
        $db =$this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $default = $autoinc = '';
        if (!empty($field['autoincrement'])) {
            $autoinc = ' PRIMARY KEY AUTOINCREMENT';
        } elseif (array_key_exists('default', $field)) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull']) ? null : 0;
            }
            $default = ' DEFAULT '.$this->quote($field['default'], 'integer');
        }

        $notnull = empty($field['notnull']) ? '' : ' NOT NULL';
        $unsigned = empty($field['unsigned']) ? '' : ' UNSIGNED';
        $name = $db->quoteIdentifier($name, true);
        if($autoinc){
			return $name.' '.$this->getTypeDeclaration($field).$autoinc;
        }else{
			return $name.' '.$this->getTypeDeclaration($field).$unsigned.$default.$notnull.$autoinc;
        }
    }

    // }}}
    // {{{ matchPattern()

    /**
     * build a pattern matching string
     *
     * @access public
     *
     * @param array $pattern even keys are strings, odd are patterns (% and _)
     * @param string $operator optional pattern operator (LIKE, ILIKE and maybe others in the future)
     * @param string $field optional field name that is being matched against
     *                  (might be required when emulating ILIKE)
     *
     * @return string SQL pattern
     */
    function matchPattern($pattern, $operator = null, $field = null)
    {
        $db =$this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $match = '';
        if (!is_null($operator)) {
            $field = is_null($field) ? '' : $field.' ';
            $operator = strtoupper($operator);
            switch ($operator) {
            // case insensitive
            case 'ILIKE':
                $match = $field.'LIKE ';
                break;
            // case sensitive
            case 'LIKE':
                $match = $field.'LIKE ';
                break;
            default:
                return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                    'not a supported operator type:'. $operator, __FUNCTION__);
            }
        }
        $match.= "'";
        foreach ($pattern as $key => $value) {
            if ($key % 2) {
                $match.= $value;
            } else {
                $match.= $db->escapePattern($db->escape($value));
            }
        }
        $match.= "'";
        $match.= $this->patternEscapeString();
        return $match;
    }

    // }}}
    // {{{ _mapNativeDatatype()

    /**
     * Maps a native array description of a field to a MDB2 datatype and length
     *
     * @param array  $field native field description
     * @return array containing the various possible types, length, sign, fixed
     * @access public
     */
    function _mapNativeDatatype($field)
    {
        $db_type = strtolower($field['type']);
        $length = !empty($field['length']) ? $field['length'] : null;
        $unsigned = !empty($field['unsigned']) ? $field['unsigned'] : null;
        $fixed = null;
        $type = array();
        switch ($db_type) {
        case 'boolean':
            $type[] = 'boolean';
            break;
        case 'tinyint':
            $type[] = 'integer';
            $type[] = 'boolean';
            if (preg_match('/^(is|has)/', $field['name'])) {
                $type = array_reverse($type);
            }
            $unsigned = preg_match('/ unsigned/i', $field['type']);
            $length = 1;
            break;
        case 'smallint':
            $type[] = 'integer';
            $unsigned = preg_match('/ unsigned/i', $field['type']);
            $length = 2;
            break;
        case 'mediumint':
            $type[] = 'integer';
            $unsigned = preg_match('/ unsigned/i', $field['type']);
            $length = 3;
            break;
        case 'int':
        case 'integer':
        case 'serial':
            $type[] = 'integer';
            $unsigned = preg_match('/ unsigned/i', $field['type']);
            $length = 4;
            break;
        case 'bigint':
        case 'bigserial':
            $type[] = 'integer';
            $unsigned = preg_match('/ unsigned/i', $field['type']);
            $length = 8;
            break;
        case 'clob':
            $type[] = 'clob';
            $fixed  = false;
            break;
        case 'tinytext':
        case 'mediumtext':
        case 'longtext':
        case 'text':
        case 'varchar':
        case 'varchar2':
            $fixed = false;
        case 'char':
            $type[] = 'text';
            if ($length == '1') {
                $type[] = 'boolean';
                if (preg_match('/^(is|has)/', $field['name'])) {
                    $type = array_reverse($type);
                }
            } elseif (strstr($db_type, 'text')) {
                $type[] = 'clob';
                $type = array_reverse($type);
            }
            if ($fixed !== false) {
                $fixed = true;
            }
            break;
        case 'date':
            $type[] = 'date';
            $length = null;
            break;
        case 'datetime':
        case 'timestamp':
            $type[] = 'timestamp';
            $length = null;
            break;
        case 'time':
            $type[] = 'time';
            $length = null;
            break;
        case 'float':
        case 'double':
        case 'real':
            $type[] = 'float';
            break;
        case 'decimal':
        case 'numeric':
            $type[] = 'decimal';
            $length = $length.','.$field['decimal'];
            break;
        case 'tinyblob':
        case 'mediumblob':
        case 'longblob':
        case 'blob':
            $type[] = 'blob';
            $length = null;
            break;
        case 'year':
            $type[] = 'integer';
            $type[] = 'date';
            $length = null;
            break;
        default:
            $db =$this->getDBInstance();
            if (PEAR::isError($db)) {
                return $db;
            }
            return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'unknown database attribute type: '.$db_type, __FUNCTION__);
        }

        if ((int)$length <= 0) {
            $length = null;
        }

        return array($type, $length, $unsigned, $fixed);
    }

    // }}}
}

?>