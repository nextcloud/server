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
// | Author: Paul Cooper <pgc@ucecom.com>                                 |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'MDB2/Driver/Datatype/Common.php';

/**
 * MDB2 PostGreSQL driver
 *
 * @package MDB2
 * @category Database
 * @author  Paul Cooper <pgc@ucecom.com>
 */
class MDB2_Driver_Datatype_pgsql extends MDB2_Driver_Datatype_Common
{
    // {{{ _baseConvertResult()

    /**
     * General type conversion method
     *
     * @param mixed   $value refernce to a value to be converted
     * @param string  $type  specifies which type to convert to
     * @param boolean $rtrim [optional] when TRUE [default], apply rtrim() to text
     * @return object a MDB2 error on failure
     * @access protected
     */
    function _baseConvertResult($value, $type, $rtrim = true)
    {
        if (null === $value) {
            return null;
        }
        switch ($type) {
        case 'boolean':
            return $value == 't';
        case 'float':
            return doubleval($value);
        case 'date':
            return $value;
        case 'time':
            return substr($value, 0, strlen('HH:MM:SS'));
        case 'timestamp':
            return substr($value, 0, strlen('YYYY-MM-DD HH:MM:SS'));
        case 'blob':
            $value = pg_unescape_bytea($value);
            return parent::_baseConvertResult($value, $type, $rtrim);
        }
        return parent::_baseConvertResult($value, $type, $rtrim);
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
            $length = !empty($field['length']) ? $field['length'] : false;
            $fixed = !empty($field['fixed']) ? $field['fixed'] : false;
            return $fixed ? ($length ? 'CHAR('.$length.')' : 'CHAR('.$db->options['default_text_field_length'].')')
                : ($length ? 'VARCHAR('.$length.')' : 'TEXT');
        case 'clob':
            return 'TEXT';
        case 'blob':
            return 'BYTEA';
        case 'integer':
            if (!empty($field['autoincrement'])) {
                if (!empty($field['length'])) {
                    $length = $field['length'];
                    if ($length > 4) {
                        return 'BIGSERIAL PRIMARY KEY';
                    }
                }
                return 'SERIAL PRIMARY KEY';
            }
            if (!empty($field['length'])) {
                $length = $field['length'];
                if ($length <= 2) {
                    return 'SMALLINT';
                } elseif ($length == 3 || $length == 4) {
                    return 'INT';
                } elseif ($length > 4) {
                    return 'BIGINT';
                }
            }
            return 'INT';
        case 'boolean':
            return 'BOOLEAN';
        case 'date':
            return 'DATE';
        case 'time':
            return 'TIME without time zone';
        case 'timestamp':
            return 'TIMESTAMP without time zone';
        case 'float':
            return 'FLOAT8';
        case 'decimal':
            $length = !empty($field['length']) ? $field['length'] : 18;
            $scale = !empty($field['scale']) ? $field['scale'] : $db->options['decimal_places'];
            return 'NUMERIC('.$length.','.$scale.')';
        }
    }

    // }}}
    // {{{ _getIntegerDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       unsigned
     *           Boolean flag that indicates whether the field should be
     *           declared as unsigned integer if possible.
     *
     *       default
     *           Integer value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getIntegerDeclaration($name, $field)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (!empty($field['unsigned'])) {
            $db->warnings[] = "unsigned integer field \"$name\" is being declared as signed integer";
        }
        if (!empty($field['autoincrement'])) {
            $name = $db->quoteIdentifier($name, true);
            return $name.' '.$this->getTypeDeclaration($field);
        }
        $default = '';
        if (array_key_exists('default', $field)) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull']) ? null : 0;
            }
            $default = ' DEFAULT '.$this->quote($field['default'], 'integer');
        }

        $notnull = empty($field['notnull']) ? '' : ' NOT NULL';
        if (empty($default) && empty($notnull)) {
            $default = ' DEFAULT NULL';
        }
        $name = $db->quoteIdentifier($name, true);
        return $name.' '.$this->getTypeDeclaration($field).$default.$notnull;
    }

    // }}}
    // {{{ _quoteCLOB()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that represents the given argument value in
     *      a DBMS specific format.
     * @access protected
     */
    function _quoteCLOB($value, $quote, $escape_wildcards)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        if ($db->options['lob_allow_url_include']) {
            $value = $this->_readFile($value);
            if (PEAR::isError($value)) {
                return $value;
            }
        }
        return $this->_quoteText($value, $quote, $escape_wildcards);
    }

    // }}}
    // {{{ _quoteBLOB()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that represents the given argument value in
     *      a DBMS specific format.
     * @access protected
     */
    function _quoteBLOB($value, $quote, $escape_wildcards)
    {
        if (!$quote) {
            return $value;
        }
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        if ($db->options['lob_allow_url_include']) {
            $value = $this->_readFile($value);
            if (PEAR::isError($value)) {
                return $value;
            }
        }
        if (version_compare(PHP_VERSION, '5.2.0RC6', '>=')) {
            $connection = $db->getConnection();
            if (PEAR::isError($connection)) {
                return $connection;
            }
            $value = @pg_escape_bytea($connection, $value);
        } else {
            $value = @pg_escape_bytea($value);
        }
        return "'".$value."'";
    }

    // }}}
    // {{{ _quoteBoolean()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteBoolean($value, $quote, $escape_wildcards)
    {
        $value = $value ? 't' : 'f';
        if (!$quote) {
            return $value;
        }
        return "'".$value."'";
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
                $match = $field.'ILIKE ';
                break;
            case 'NOT ILIKE':
                $match = $field.'NOT ILIKE ';
                break;
            // case sensitive
            case 'LIKE':
                $match = $field.'LIKE ';
                break;
            case 'NOT LIKE':
                $match = $field.'NOT LIKE ';
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
    // {{{ patternEscapeString()

    /**
     * build string to define escape pattern string
     *
     * @access public
     *
     *
     * @return string define escape pattern
     */
    function patternEscapeString()
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        return ' ESCAPE '.$this->quote($db->string_quoting['escape_pattern']);
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
        $length = $field['length'];
        $type = array();
        $unsigned = $fixed = null;
        switch ($db_type) {
        case 'smallint':
        case 'int2':
            $type[] = 'integer';
            $unsigned = false;
            $length = 2;
            if ($length == '2') {
                $type[] = 'boolean';
                if (preg_match('/^(is|has)/', $field['name'])) {
                    $type = array_reverse($type);
                }
            }
            break;
        case 'int':
        case 'int4':
        case 'integer':
        case 'serial':
        case 'serial4':
            $type[] = 'integer';
            $unsigned = false;
            $length = 4;
            break;
        case 'bigint':
        case 'int8':
        case 'bigserial':
        case 'serial8':
            $type[] = 'integer';
            $unsigned = false;
            $length = 8;
            break;
        case 'bool':
        case 'boolean':
            $type[] = 'boolean';
            $length = null;
            break;
        case 'text':
        case 'varchar':
            $fixed = false;
        case 'unknown':
        case 'char':
        case 'bpchar':
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
        case 'timestamptz':
            $type[] = 'timestamp';
            $length = null;
            break;
        case 'time':
            $type[] = 'time';
            $length = null;
            break;
        case 'float':
        case 'float4':
        case 'float8':
        case 'double':
        case 'real':
            $type[] = 'float';
            break;
        case 'decimal':
        case 'money':
        case 'numeric':
            $type[] = 'decimal';
            if (isset($field['scale'])) {
                $length = $length.','.$field['scale'];
            }
            break;
        case 'tinyblob':
        case 'mediumblob':
        case 'longblob':
        case 'blob':
        case 'bytea':
            $type[] = 'blob';
            $length = null;
            break;
        case 'oid':
            $type[] = 'blob';
            $type[] = 'clob';
            $length = null;
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
    // {{{ mapPrepareDatatype()

    /**
     * Maps an mdb2 datatype to native prepare type
     *
     * @param string $type
     * @return string
     * @access public
     */
    function mapPrepareDatatype($type)
    {
        $db = $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (!empty($db->options['datatype_map'][$type])) {
            $type = $db->options['datatype_map'][$type];
            if (!empty($db->options['datatype_map_callback'][$type])) {
                $parameter = array('type' => $type);
                return call_user_func_array($db->options['datatype_map_callback'][$type], array(&$db, __FUNCTION__, $parameter));
            }
        }

        switch ($type) {
            case 'integer':
                return 'int';
            case 'boolean':
                return 'bool';
            case 'decimal':
            case 'float':
                return 'numeric';
            case 'clob':
                return 'text';
            case 'blob':
                return 'bytea';
            default:
                break;
        }
        return $type;
    }
    // }}}
}
?>