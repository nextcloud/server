<?php
// vim: set et ts=4 sw=4 fdm=marker:
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

require_once 'MDB2/Driver/Datatype/Common.php';

/**
 * MDB2 MySQL driver
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Driver_Datatype_mysql extends MDB2_Driver_Datatype_Common
{
    // {{{ _getCharsetFieldDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to set the CHARACTER SET
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param string $charset   name of the charset
     * @return string  DBMS specific SQL code portion needed to set the CHARACTER SET
     *                 of a field declaration.
     */
    function _getCharsetFieldDeclaration($charset)
    {
        return 'CHARACTER SET '.$charset;
    }

    // }}}
    // {{{ _getCollationFieldDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to set the COLLATION
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param string $collation   name of the collation
     * @return string  DBMS specific SQL code portion needed to set the COLLATION
     *                 of a field declaration.
     */
    function _getCollationFieldDeclaration($collation)
    {
        return 'COLLATE '.$collation;
    }

    // }}}
    // {{{ getDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare
     * of the given type
     *
     * @param string $type  type to which the value should be converted to
     * @param string $name  name the field to be declared.
     * @param string $field definition of the field
     *
     * @return string DBMS-specific SQL code portion that should be used to
     *                declare the specified field.
     * @access public
     */
    function getDeclaration($type, $name, $field)
    {
        // MySQL DDL syntax forbids combining NOT NULL with DEFAULT NULL.
        // To get a default of NULL for NOT NULL columns, omit it.
        if (   isset($field['notnull'])
            && !empty($field['notnull'])
            && array_key_exists('default', $field) // do not use isset() here!
            && null === $field['default']
        ) {
            unset($field['default']);
        }
        return parent::getDeclaration($type, $name, $field);
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        switch ($field['type']) {
        case 'text':
            if (empty($field['length']) && array_key_exists('default', $field)) {
                $field['length'] = $db->varchar_max_length;
            }
            $length = !empty($field['length']) ? $field['length'] : false;
            $fixed = !empty($field['fixed']) ? $field['fixed'] : false;
            return $fixed ? ($length ? 'CHAR('.$length.')' : 'CHAR(255)')
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
                if ($length <= 1) {
                    return 'TINYINT';
                } elseif ($length == 2) {
                    return 'SMALLINT';
                } elseif ($length == 3) {
                    return 'MEDIUMINT';
                } elseif ($length == 4) {
                    return 'INT';
                } elseif ($length > 4) {
                    return 'BIGINT';
                }
            }
            return 'INT';
        case 'boolean':
            return 'TINYINT(1)';
        case 'date':
            return 'DATE';
        case 'time':
            return 'TIME';
        case 'timestamp':
            return 'DATETIME';
        case 'float':
            $l = '';
            if (!empty($field['length'])) {
                $l = '(' . $field['length'];
                if (!empty($field['scale'])) {
                    $l .= ',' . $field['scale'];
                }
                $l .= ')';
            }
            return 'DOUBLE' . $l;
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $default = $autoinc = '';
        if (!empty($field['autoincrement'])) {
            $autoinc = ' AUTO_INCREMENT PRIMARY KEY';
        } elseif (array_key_exists('default', $field)) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull']) ? null : 0;
            }
            $default = ' DEFAULT '.$this->quote($field['default'], 'integer');
        }

        $notnull = empty($field['notnull']) ? '' : ' NOT NULL';
        $unsigned = empty($field['unsigned']) ? '' : ' UNSIGNED';
        if (empty($default) && empty($notnull)) {
            $default = ' DEFAULT NULL';
        }
        $name = $db->quoteIdentifier($name, true);
        return $name.' '.$this->getTypeDeclaration($field).$unsigned.$default.$notnull.$autoinc;
    }

    // }}}
    // {{{ _getFloatDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an float type
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
     *                        should be declared as unsigned float if
     *                        possible.
     *
     *                       default
     *                        float value to be used as default for this
     *                        field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access protected
     */
    function _getFloatDeclaration($name, $field)
    {
        // Since AUTO_INCREMENT can be used for integer or floating-point types,
        // reuse the INTEGER declaration
        // @see http://bugs.mysql.com/bug.php?id=31032
        return $this->_getIntegerDeclaration($name, $field);
    }

    // }}}
    // {{{ _getDecimalDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an decimal type
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
     *                        Decimal value to be used as default for this
     *                        field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access protected
     */
    function _getDecimalDeclaration($name, $field)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $default = '';
        if (array_key_exists('default', $field)) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull']) ? null : 0;
            }
            $default = ' DEFAULT '.$this->quote($field['default'], 'integer');
        } elseif (empty($field['notnull'])) {
            $default = ' DEFAULT NULL';
        }

        $notnull = empty($field['notnull']) ? '' : ' NOT NULL';
        $unsigned = empty($field['unsigned']) ? '' : ' UNSIGNED';
        $name = $db->quoteIdentifier($name, true);
        return $name.' '.$this->getTypeDeclaration($field).$unsigned.$default.$notnull;
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
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $match = '';
        if (null !== $operator) {
            $field = (null === $field) ? '' : $field.' ';
            $operator = strtoupper($operator);
            switch ($operator) {
            // case insensitive
            case 'ILIKE':
                $match = $field.'LIKE ';
                break;
            case 'NOT ILIKE':
                $match = $field.'NOT LIKE ';
                break;
            // case sensitive
            case 'LIKE':
                $match = $field.'LIKE BINARY ';
                break;
            case 'NOT LIKE':
                $match = $field.'NOT LIKE BINARY ';
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
        $db_type = strtok($db_type, '(), ');
        if ($db_type == 'national') {
            $db_type = strtok('(), ');
        }
        if (!empty($field['length'])) {
            $length = strtok($field['length'], ', ');
            $decimal = strtok(', ');
        } else {
            $length = strtok('(), ');
            $decimal = strtok('(), ');
        }
        $type = array();
        $unsigned = $fixed = null;
        switch ($db_type) {
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
            $type[] = 'integer';
            $unsigned = preg_match('/ unsigned/i', $field['type']);
            $length = 4;
            break;
        case 'bigint':
            $type[] = 'integer';
            $unsigned = preg_match('/ unsigned/i', $field['type']);
            $length = 8;
            break;
        case 'tinytext':
        case 'mediumtext':
        case 'longtext':
        case 'text':
        case 'varchar':
            $fixed = false;
        case 'string':
        case 'char':
            $type[] = 'text';
            if ($length == '1') {
                $type[] = 'boolean';
                if (preg_match('/^(is|has)/', $field['name'])) {
                    $type = array_reverse($type);
                }
            } elseif (strstr($db_type, 'text')) {
                $type[] = 'clob';
                if ($decimal == 'binary') {
                    $type[] = 'blob';
                }
                $type = array_reverse($type);
            }
            if ($fixed !== false) {
                $fixed = true;
            }
            break;
        case 'enum':
            $type[] = 'text';
            preg_match_all('/\'.+\'/U', $field['type'], $matches);
            $length = 0;
            $fixed = false;
            if (is_array($matches)) {
                foreach ($matches[0] as $value) {
                    $length = max($length, strlen($value)-2);
                }
                if ($length == '1' && count($matches[0]) == 2) {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                }
            }
            $type[] = 'integer';
        case 'set':
            $fixed = false;
            $type[] = 'text';
            $type[] = 'integer';
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
            $unsigned = preg_match('/ unsigned/i', $field['type']);
            if ($decimal !== false) {
                $length = $length.','.$decimal;
            }
            break;
        case 'unknown':
        case 'decimal':
        case 'numeric':
            $type[] = 'decimal';
            $unsigned = preg_match('/ unsigned/i', $field['type']);
            if ($decimal !== false) {
                $length = $length.','.$decimal;
            }
            break;
        case 'tinyblob':
        case 'mediumblob':
        case 'longblob':
        case 'blob':
            $type[] = 'blob';
            $length = null;
            break;
        case 'binary':
        case 'varbinary':
            $type[] = 'blob';
            break;
        case 'year':
            $type[] = 'integer';
            $type[] = 'date';
            $length = null;
            break;
        default:
            $db = $this->getDBInstance();
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