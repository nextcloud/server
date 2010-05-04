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
// $Id: Common.php,v 1.139 2008/12/04 11:50:42 afz Exp $

require_once 'MDB2/LOB.php';

/**
 * @package  MDB2
 * @category Database
 * @author   Lukas Smith <smith@pooteeweet.org>
 */

/**
 * MDB2_Driver_Common: Base class that is extended by each MDB2 driver
 *
 * To load this module in the MDB2 object:
 * $mdb->loadModule('Datatype');
 *
 * @package MDB2
 * @category Database
 * @author Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Driver_Datatype_Common extends MDB2_Module_Common
{
    var $valid_default_values = array(
        'text'      => '',
        'boolean'   => true,
        'integer'   => 0,
        'decimal'   => 0.0,
        'float'     => 0.0,
        'timestamp' => '1970-01-01 00:00:00',
        'time'      => '00:00:00',
        'date'      => '1970-01-01',
        'clob'      => '',
        'blob'      => '',
    );

    /**
     * contains all LOB objects created with this MDB2 instance
     * @var array
     * @access protected
     */
    var $lobs = array();

    // }}}
    // {{{ getValidTypes()

    /**
     * Get the list of valid types
     *
     * This function returns an array of valid types as keys with the values
     * being possible default values for all native datatypes and mapped types
     * for custom datatypes.
     *
     * @return mixed array on success, a MDB2 error on failure
     * @access public
     */
    function getValidTypes()
    {
        $types = $this->valid_default_values;
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        if (!empty($db->options['datatype_map'])) {
            foreach ($db->options['datatype_map'] as $type => $mapped_type) {
                if (array_key_exists($mapped_type, $types)) {
                    $types[$type] = $types[$mapped_type];
                } elseif (!empty($db->options['datatype_map_callback'][$type])) {
                    $parameter = array('type' => $type, 'mapped_type' => $mapped_type);
                    $default =  call_user_func_array($db->options['datatype_map_callback'][$type], array(&$db, __FUNCTION__, $parameter));
                    $types[$type] = $default;
                }
            }
        }
        return $types;
    }

    // }}}
    // {{{ checkResultTypes()

    /**
     * Define the list of types to be associated with the columns of a given
     * result set.
     *
     * This function may be called before invoking fetchRow(), fetchOne()
     * fetchCole() and fetchAll() so that the necessary data type
     * conversions are performed on the data to be retrieved by them. If this
     * function is not called, the type of all result set columns is assumed
     * to be text, thus leading to not perform any conversions.
     *
     * @param array $types array variable that lists the
     *       data types to be expected in the result set columns. If this array
     *       contains less types than the number of columns that are returned
     *       in the result set, the remaining columns are assumed to be of the
     *       type text. Currently, the types clob and blob are not fully
     *       supported.
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function checkResultTypes($types)
    {
        $types = is_array($types) ? $types : array($types);
        foreach ($types as $key => $type) {
            if (!isset($this->valid_default_values[$type])) {
                $db =& $this->getDBInstance();
                if (PEAR::isError($db)) {
                    return $db;
                }
                if (empty($db->options['datatype_map'][$type])) {
                    return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                        $type.' for '.$key.' is not a supported column type', __FUNCTION__);
                }
            }
        }
        return $types;
    }

    // }}}
    // {{{ _baseConvertResult()

    /**
     * General type conversion method
     *
     * @param mixed   $value reference to a value to be converted
     * @param string  $type  specifies which type to convert to
     * @param boolean $rtrim [optional] when TRUE [default], apply rtrim() to text
     * @return object an MDB2 error on failure
     * @access protected
     */
    function _baseConvertResult($value, $type, $rtrim = true)
    {
        switch ($type) {
        case 'text':
            if ($rtrim) {
                $value = rtrim($value);
            }
            return $value;
        case 'integer':
            return intval($value);
        case 'boolean':
            return !empty($value);
        case 'decimal':
            return $value;
        case 'float':
            return doubleval($value);
        case 'date':
            return $value;
        case 'time':
            return $value;
        case 'timestamp':
            return $value;
        case 'clob':
        case 'blob':
            $this->lobs[] = array(
                'buffer' => null,
                'position' => 0,
                'lob_index' => null,
                'endOfLOB' => false,
                'resource' => $value,
                'value' => null,
                'loaded' => false,
            );
            end($this->lobs);
            $lob_index = key($this->lobs);
            $this->lobs[$lob_index]['lob_index'] = $lob_index;
            return fopen('MDB2LOB://'.$lob_index.'@'.$this->db_index, 'r+');
        }

        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_INVALID, null, null,
            'attempt to convert result value to an unknown type :' . $type, __FUNCTION__);
    }

    // }}}
    // {{{ convertResult()

    /**
     * Convert a value to a RDBMS indipendent MDB2 type
     *
     * @param mixed   $value value to be converted
     * @param string  $type  specifies which type to convert to
     * @param boolean $rtrim [optional] when TRUE [default], apply rtrim() to text
     * @return mixed converted value
     * @access public
     */
    function convertResult($value, $type, $rtrim = true)
    {
        if (is_null($value)) {
            return null;
        }
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        if (!empty($db->options['datatype_map'][$type])) {
            $type = $db->options['datatype_map'][$type];
            if (!empty($db->options['datatype_map_callback'][$type])) {
                $parameter = array('type' => $type, 'value' => $value, 'rtrim' => $rtrim);
                return call_user_func_array($db->options['datatype_map_callback'][$type], array(&$db, __FUNCTION__, $parameter));
            }
        }
        return $this->_baseConvertResult($value, $type, $rtrim);
    }

    // }}}
    // {{{ convertResultRow()

    /**
     * Convert a result row
     *
     * @param array   $types
     * @param array   $row   specifies the types to convert to
     * @param boolean $rtrim [optional] when TRUE [default], apply rtrim() to text
     * @return mixed MDB2_OK on success, an MDB2 error on failure
     * @access public
     */
    function convertResultRow($types, $row, $rtrim = true)
    {
        $types = $this->_sortResultFieldTypes(array_keys($row), $types);
        foreach ($row as $key => $value) {
            if (empty($types[$key])) {
                continue;
            }
            $value = $this->convertResult($row[$key], $types[$key], $rtrim);
            if (PEAR::isError($value)) {
                return $value;
            }
            $row[$key] = $value;
        }
        return $row;
    }

    // }}}
    // {{{ _sortResultFieldTypes()

    /**
     * convert a result row
     *
     * @param array $types
     * @param array $row specifies the types to convert to
     * @param bool   $rtrim   if to rtrim text values or not
     * @return mixed MDB2_OK on success,  a MDB2 error on failure
     * @access public
     */
    function _sortResultFieldTypes($columns, $types)
    {
        $n_cols = count($columns);
        $n_types = count($types);
        if ($n_cols > $n_types) {
            for ($i= $n_cols - $n_types; $i >= 0; $i--) {
                $types[] = null;
            }
        }
        $sorted_types = array();
        foreach ($columns as $col) {
            $sorted_types[$col] = null;
        }
        foreach ($types as $name => $type) {
            if (array_key_exists($name, $sorted_types)) {
                $sorted_types[$name] = $type;
                unset($types[$name]);
            }
        }
        // if there are left types in the array, fill the null values of the
        // sorted array with them, in order.
        if (count($types)) {
            reset($types);
            foreach (array_keys($sorted_types) as $k) {
                if (is_null($sorted_types[$k])) {
                    $sorted_types[$k] = current($types);
                    next($types);
                }
            }
        }
        return $sorted_types;
    }

    // }}}
    // {{{ getDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare
     * of the given type
     *
     * @param string $type type to which the value should be converted to
     * @param string  $name   name the field to be declared.
     * @param string  $field  definition of the field
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getDeclaration($type, $name, $field)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (!empty($db->options['datatype_map'][$type])) {
            $type = $db->options['datatype_map'][$type];
            if (!empty($db->options['datatype_map_callback'][$type])) {
                $parameter = array('type' => $type, 'name' => $name, 'field' => $field);
                return call_user_func_array($db->options['datatype_map_callback'][$type], array(&$db, __FUNCTION__, $parameter));
            }
            $field['type'] = $type;
        }

        if (!method_exists($this, "_get{$type}Declaration")) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'type not defined: '.$type, __FUNCTION__);
        }
        return $this->{"_get{$type}Declaration"}($name, $field);
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        switch ($field['type']) {
        case 'text':
            $length = !empty($field['length']) ? $field['length'] : $db->options['default_text_field_length'];
            $fixed = !empty($field['fixed']) ? $field['fixed'] : false;
            return $fixed ? ($length ? 'CHAR('.$length.')' : 'CHAR('.$db->options['default_text_field_length'].')')
                : ($length ? 'VARCHAR('.$length.')' : 'TEXT');
        case 'clob':
            return 'TEXT';
        case 'blob':
            return 'TEXT';
        case 'integer':
            return 'INT';
        case 'boolean':
            return 'INT';
        case 'date':
            return 'CHAR ('.strlen('YYYY-MM-DD').')';
        case 'time':
            return 'CHAR ('.strlen('HH:MM:SS').')';
        case 'timestamp':
            return 'CHAR ('.strlen('YYYY-MM-DD HH:MM:SS').')';
        case 'float':
            return 'TEXT';
        case 'decimal':
            return 'TEXT';
        }
        return '';
    }

    // }}}
    // {{{ _getDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a generic type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name   name the field to be declared.
     * @param array  $field  associative array with the name of the properties
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
     *      charset
     *          Text value with the default CHARACTER SET for this field.
     *      collation
     *          Text value with the default COLLATION for this field.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field, or a MDB2_Error on failure
     * @access protected
     */
    function _getDeclaration($name, $field)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $name = $db->quoteIdentifier($name, true);
        $declaration_options = $db->datatype->_getDeclarationOptions($field);
        if (PEAR::isError($declaration_options)) {
            return $declaration_options;
        }
        return $name.' '.$this->getTypeDeclaration($field).$declaration_options;
    }

    // }}}
    // {{{ _getDeclarationOptions()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a generic type
     * field to be used in statement like CREATE TABLE, without the field name
     * and type values (ie. just the character set, default value, if the
     * field is permitted to be NULL or not, and the collation options).
     *
     * @param array  $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      default
     *          Text value to be used as default for this field.
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     *      charset
     *          Text value with the default CHARACTER SET for this field.
     *      collation
     *          Text value with the default COLLATION for this field.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field's options.
     * @access protected
     */
    function _getDeclarationOptions($field)
    {
        $charset = empty($field['charset']) ? '' :
            ' '.$this->_getCharsetFieldDeclaration($field['charset']);

        $notnull = empty($field['notnull']) ? '' : ' NOT NULL';
        $default = '';
        if (array_key_exists('default', $field)) {
            if ($field['default'] === '') {
                $db =& $this->getDBInstance();
                if (PEAR::isError($db)) {
                    return $db;
                }
                $valid_default_values = $this->getValidTypes();
                $field['default'] = $valid_default_values[$field['type']];
                if ($field['default'] === ''&& ($db->options['portability'] & MDB2_PORTABILITY_EMPTY_TO_NULL)) {
                    $field['default'] = ' ';
                }
            }
            if (!is_null($field['default'])) {
                $default = ' DEFAULT ' . $this->quote($field['default'], $field['type']);
            }
        }

        $collation = empty($field['collation']) ? '' :
            ' '.$this->_getCollationFieldDeclaration($field['collation']);

        return $charset.$default.$notnull.$collation;
    }

    // }}}
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
        return '';
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
        return '';
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
        if (!empty($field['unsigned'])) {
            $db =& $this->getDBInstance();
            if (PEAR::isError($db)) {
                return $db;
            }

            $db->warnings[] = "unsigned integer field \"$name\" is being declared as signed integer";
        }
        return $this->_getDeclaration($name, $field);
    }

    // }}}
    // {{{ _getTextDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       length
     *           Integer value that determines the maximum length of the text
     *           field. If this argument is missing the field should be
     *           declared to have the longest length allowed by the DBMS.
     *
     *       default
     *           Text value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getTextDeclaration($name, $field)
    {
        return $this->_getDeclaration($name, $field);
    }

    // }}}
    // {{{ _getCLOBDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an character
     * large object type field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     *
     *        length
     *            Integer value that determines the maximum length of the large
     *            object field. If this argument is missing the field should be
     *            declared to have the longest length allowed by the DBMS.
     *
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access public
     */
    function _getCLOBDeclaration($name, $field)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $notnull = empty($field['notnull']) ? '' : ' NOT NULL';
        $name = $db->quoteIdentifier($name, true);
        return $name.' '.$this->getTypeDeclaration($field).$notnull;
    }

    // }}}
    // {{{ _getBLOBDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an binary large
     * object type field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     *
     *        length
     *            Integer value that determines the maximum length of the large
     *            object field. If this argument is missing the field should be
     *            declared to have the longest length allowed by the DBMS.
     *
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access protected
     */
    function _getBLOBDeclaration($name, $field)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $notnull = empty($field['notnull']) ? '' : ' NOT NULL';
        $name = $db->quoteIdentifier($name, true);
        return $name.' '.$this->getTypeDeclaration($field).$notnull;
    }

    // }}}
    // {{{ _getBooleanDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a boolean type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Boolean value to be used as default for this field.
     *
     *       notnullL
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getBooleanDeclaration($name, $field)
    {
        return $this->_getDeclaration($name, $field);
    }

    // }}}
    // {{{ _getDateDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a date type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Date value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getDateDeclaration($name, $field)
    {
        return $this->_getDeclaration($name, $field);
    }

    // }}}
    // {{{ _getTimestampDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a timestamp
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Timestamp value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getTimestampDeclaration($name, $field)
    {
        return $this->_getDeclaration($name, $field);
    }

    // }}}
    // {{{ _getTimeDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a time
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Time value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getTimeDeclaration($name, $field)
    {
        return $this->_getDeclaration($name, $field);
    }

    // }}}
    // {{{ _getFloatDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a float type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Float value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getFloatDeclaration($name, $field)
    {
        return $this->_getDeclaration($name, $field);
    }

    // }}}
    // {{{ _getDecimalDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a decimal type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Decimal value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getDecimalDeclaration($name, $field)
    {
        return $this->_getDeclaration($name, $field);
    }

    // }}}
    // {{{ compareDefinition()

    /**
     * Obtain an array of changes that may need to applied
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containing all changes that will need to be applied
     * @access public
     */
    function compareDefinition($current, $previous)
    {
        $type = !empty($current['type']) ? $current['type'] : null;

        if (!method_exists($this, "_compare{$type}Definition")) {
            $db =& $this->getDBInstance();
            if (PEAR::isError($db)) {
                return $db;
            }
            if (!empty($db->options['datatype_map_callback'][$type])) {
                $parameter = array('current' => $current, 'previous' => $previous);
                $change =  call_user_func_array($db->options['datatype_map_callback'][$type], array(&$db, __FUNCTION__, $parameter));
                return $change;
            }
            return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'type "'.$current['type'].'" is not yet supported', __FUNCTION__);
        }

        if (empty($previous['type']) || $previous['type'] != $type) {
            return $current;
        }

        $change = $this->{"_compare{$type}Definition"}($current, $previous);

        if ($previous['type'] != $type) {
            $change['type'] = true;
        }

        $previous_notnull = !empty($previous['notnull']) ? $previous['notnull'] : false;
        $notnull = !empty($current['notnull']) ? $current['notnull'] : false;
        if ($previous_notnull != $notnull) {
            $change['notnull'] = true;
        }

        $previous_default = array_key_exists('default', $previous) ? $previous['default'] :
            ($previous_notnull ? '' : null);
        $default = array_key_exists('default', $current) ? $current['default'] :
            ($notnull ? '' : null);
        if ($previous_default !== $default) {
            $change['default'] = true;
        }

        return $change;
    }

    // }}}
    // {{{ _compareIntegerDefinition()

    /**
     * Obtain an array of changes that may need to applied to an integer field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containing all changes that will need to be applied
     * @access protected
     */
    function _compareIntegerDefinition($current, $previous)
    {
        $change = array();
        $previous_unsigned = !empty($previous['unsigned']) ? $previous['unsigned'] : false;
        $unsigned = !empty($current['unsigned']) ? $current['unsigned'] : false;
        if ($previous_unsigned != $unsigned) {
            $change['unsigned'] = true;
        }
        $previous_autoincrement = !empty($previous['autoincrement']) ? $previous['autoincrement'] : false;
        $autoincrement = !empty($current['autoincrement']) ? $current['autoincrement'] : false;
        if ($previous_autoincrement != $autoincrement) {
            $change['autoincrement'] = true;
        }
        return $change;
    }

    // }}}
    // {{{ _compareTextDefinition()

    /**
     * Obtain an array of changes that may need to applied to an text field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containing all changes that will need to be applied
     * @access protected
     */
    function _compareTextDefinition($current, $previous)
    {
        $change = array();
        $previous_length = !empty($previous['length']) ? $previous['length'] : 0;
        $length = !empty($current['length']) ? $current['length'] : 0;
        if ($previous_length != $length) {
            $change['length'] = true;
        }
        $previous_fixed = !empty($previous['fixed']) ? $previous['fixed'] : 0;
        $fixed = !empty($current['fixed']) ? $current['fixed'] : 0;
        if ($previous_fixed != $fixed) {
            $change['fixed'] = true;
        }
        return $change;
    }

    // }}}
    // {{{ _compareCLOBDefinition()

    /**
     * Obtain an array of changes that may need to applied to an CLOB field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containing all changes that will need to be applied
     * @access protected
     */
    function _compareCLOBDefinition($current, $previous)
    {
        return $this->_compareTextDefinition($current, $previous);
    }

    // }}}
    // {{{ _compareBLOBDefinition()

    /**
     * Obtain an array of changes that may need to applied to an BLOB field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containing all changes that will need to be applied
     * @access protected
     */
    function _compareBLOBDefinition($current, $previous)
    {
        return $this->_compareTextDefinition($current, $previous);
    }

    // }}}
    // {{{ _compareDateDefinition()

    /**
     * Obtain an array of changes that may need to applied to an date field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containing all changes that will need to be applied
     * @access protected
     */
    function _compareDateDefinition($current, $previous)
    {
        return array();
    }

    // }}}
    // {{{ _compareTimeDefinition()

    /**
     * Obtain an array of changes that may need to applied to an time field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containing all changes that will need to be applied
     * @access protected
     */
    function _compareTimeDefinition($current, $previous)
    {
        return array();
    }

    // }}}
    // {{{ _compareTimestampDefinition()

    /**
     * Obtain an array of changes that may need to applied to an timestamp field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containing all changes that will need to be applied
     * @access protected
     */
    function _compareTimestampDefinition($current, $previous)
    {
        return array();
    }

    // }}}
    // {{{ _compareBooleanDefinition()

    /**
     * Obtain an array of changes that may need to applied to an boolean field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containing all changes that will need to be applied
     * @access protected
     */
    function _compareBooleanDefinition($current, $previous)
    {
        return array();
    }

    // }}}
    // {{{ _compareFloatDefinition()

    /**
     * Obtain an array of changes that may need to applied to an float field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containing all changes that will need to be applied
     * @access protected
     */
    function _compareFloatDefinition($current, $previous)
    {
        return array();
    }

    // }}}
    // {{{ _compareDecimalDefinition()

    /**
     * Obtain an array of changes that may need to applied to an decimal field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containing all changes that will need to be applied
     * @access protected
     */
    function _compareDecimalDefinition($current, $previous)
    {
        return array();
    }

    // }}}
    // {{{ quote()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param string $type type to which the value should be converted to
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access public
     */
    function quote($value, $type = null, $quote = true, $escape_wildcards = false)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (is_null($value)
            || ($value === '' && $db->options['portability'] & MDB2_PORTABILITY_EMPTY_TO_NULL)
        ) {
            if (!$quote) {
                return null;
            }
            return 'NULL';
        }

        if (is_null($type)) {
            switch (gettype($value)) {
            case 'integer':
                $type = 'integer';
                break;
            case 'double':
                // todo: default to decimal as float is quite unusual
                // $type = 'float';
                $type = 'decimal';
                break;
            case 'boolean':
                $type = 'boolean';
                break;
            case 'array':
                 $value = serialize($value);
            case 'object':
                 $type = 'text';
                break;
            default:
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
                    $type = 'timestamp';
                } elseif (preg_match('/^\d{2}:\d{2}$/', $value)) {
                    $type = 'time';
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    $type = 'date';
                } else {
                    $type = 'text';
                }
                break;
            }
        } elseif (!empty($db->options['datatype_map'][$type])) {
            $type = $db->options['datatype_map'][$type];
            if (!empty($db->options['datatype_map_callback'][$type])) {
                $parameter = array('type' => $type, 'value' => $value, 'quote' => $quote, 'escape_wildcards' => $escape_wildcards);
                return call_user_func_array($db->options['datatype_map_callback'][$type], array(&$db, __FUNCTION__, $parameter));
            }
        }

        if (!method_exists($this, "_quote{$type}")) {
            return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'type not defined: '.$type, __FUNCTION__);
        }
        $value = $this->{"_quote{$type}"}($value, $quote, $escape_wildcards);
        if ($quote && $escape_wildcards && $db->string_quoting['escape_pattern']
            && $db->string_quoting['escape'] !== $db->string_quoting['escape_pattern']
        ) {
            $value.= $this->patternEscapeString();
        }
        return $value;
    }

    // }}}
    // {{{ _quoteInteger()

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
    function _quoteInteger($value, $quote, $escape_wildcards)
    {
        return (int)$value;
    }

    // }}}
    // {{{ _quoteText()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that already contains any DBMS specific
     *       escaped character sequences.
     * @access protected
     */
    function _quoteText($value, $quote, $escape_wildcards)
    {
        if (!$quote) {
            return $value;
        }

        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $value = $db->escape($value, $escape_wildcards);
        if (PEAR::isError($value)) {
            return $value;
        }
        return "'".$value."'";
    }

    // }}}
    // {{{ _readFile()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _readFile($value)
    {
        $close = false;
        if (preg_match('/^(\w+:\/\/)(.*)$/', $value, $match)) {
            $close = true;
            if ($match[1] == 'file://') {
                $value = $match[2];
            }
            $value = @fopen($value, 'r');
        }

        if (is_resource($value)) {
            $db =& $this->getDBInstance();
            if (PEAR::isError($db)) {
                return $db;
            }

            $fp = $value;
            $value = '';
            while (!@feof($fp)) {
                $value.= @fread($fp, $db->options['lob_buffer_length']);
            }
            if ($close) {
                @fclose($fp);
            }
        }

        return $value;
    }

    // }}}
    // {{{ _quoteLOB()

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
    function _quoteLOB($value, $quote, $escape_wildcards)
    {
        $value = $this->_readFile($value);
        if (PEAR::isError($value)) {
            return $value;
        }
        return $this->_quoteText($value, $quote, $escape_wildcards);
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
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteCLOB($value, $quote, $escape_wildcards)
    {
        return $this->_quoteLOB($value, $quote, $escape_wildcards);
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
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteBLOB($value, $quote, $escape_wildcards)
    {
        return $this->_quoteLOB($value, $quote, $escape_wildcards);
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
        return ($value ? 1 : 0);
    }

    // }}}
    // {{{ _quoteDate()

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
    function _quoteDate($value, $quote, $escape_wildcards)
    {
        if ($value === 'CURRENT_DATE') {
            $db =& $this->getDBInstance();
            if (PEAR::isError($db)) {
                return $db;
            }
            if (isset($db->function) && is_a($db->function, 'MDB2_Driver_Function_Common')) {
                return $db->function->now('date');
            }
            return 'CURRENT_DATE';
        }
        return $this->_quoteText($value, $quote, $escape_wildcards);
    }

    // }}}
    // {{{ _quoteTimestamp()

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
    function _quoteTimestamp($value, $quote, $escape_wildcards)
    {
        if ($value === 'CURRENT_TIMESTAMP') {
            $db =& $this->getDBInstance();
            if (PEAR::isError($db)) {
                return $db;
            }
            if (isset($db->function) && is_a($db->function, 'MDB2_Driver_Function_Common')) {
                return $db->function->now('timestamp');
            }
            return 'CURRENT_TIMESTAMP';
        }
        return $this->_quoteText($value, $quote, $escape_wildcards);
    }

    // }}}
    // {{{ _quoteTime()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     *       compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteTime($value, $quote, $escape_wildcards)
    {
        if ($value === 'CURRENT_TIME') {
            $db =& $this->getDBInstance();
            if (PEAR::isError($db)) {
                return $db;
            }
            if (isset($db->function) && is_a($db->function, 'MDB2_Driver_Function_Common')) {
                return $db->function->now('time');
            }
            return 'CURRENT_TIME';
        }
        return $this->_quoteText($value, $quote, $escape_wildcards);
    }

    // }}}
    // {{{ _quoteFloat()

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
    function _quoteFloat($value, $quote, $escape_wildcards)
    {
        if (preg_match('/^(.*)e([-+])(\d+)$/i', $value, $matches)) {
            $decimal = $this->_quoteDecimal($matches[1], $quote, $escape_wildcards);
            $sign = $matches[2];
            $exponent = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
            $value = $decimal.'E'.$sign.$exponent;
        } else {
            $value = $this->_quoteDecimal($value, $quote, $escape_wildcards);
        }
        return $value;
    }

    // }}}
    // {{{ _quoteDecimal()

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
    function _quoteDecimal($value, $quote, $escape_wildcards)
    {
        $value = (string)$value;
        $value = preg_replace('/[^\d\.,\-+eE]/', '', $value);
        if (preg_match('/[^\.\d]/', $value)) {
            if (strpos($value, ',')) {
                // 1000,00
                if (!strpos($value, '.')) {
                    // convert the last "," to a "."
                    $value = strrev(str_replace(',', '.', strrev($value)));
                // 1.000,00
                } elseif (strpos($value, '.') && strpos($value, '.') < strpos($value, ',')) {
                    $value = str_replace('.', '', $value);
                    // convert the last "," to a "."
                    $value = strrev(str_replace(',', '.', strrev($value)));
                // 1,000.00
                } else {
                    $value = str_replace(',', '', $value);
                }
            }
        }
        return $value;
    }

    // }}}
    // {{{ writeLOBToFile()

    /**
     * retrieve LOB from the database
     *
     * @param resource $lob stream handle
     * @param string $file name of the file into which the LOb should be fetched
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access protected
     */
    function writeLOBToFile($lob, $file)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (preg_match('/^(\w+:\/\/)(.*)$/', $file, $match)) {
            if ($match[1] == 'file://') {
                $file = $match[2];
            }
        }

        $fp = @fopen($file, 'wb');
        while (!@feof($lob)) {
            $result = @fread($lob, $db->options['lob_buffer_length']);
            $read = strlen($result);
            if (@fwrite($fp, $result, $read) != $read) {
                @fclose($fp);
                return $db->raiseError(MDB2_ERROR, null, null,
                    'could not write to the output file', __FUNCTION__);
            }
        }
        @fclose($fp);
        return MDB2_OK;
    }

    // }}}
    // {{{ _retrieveLOB()

    /**
     * retrieve LOB from the database
     *
     * @param array $lob array
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access protected
     */
    function _retrieveLOB(&$lob)
    {
        if (is_null($lob['value'])) {
            $lob['value'] = $lob['resource'];
        }
        $lob['loaded'] = true;
        return MDB2_OK;
    }

    // }}}
    // {{{ readLOB()

    /**
     * Read data from large object input stream.
     *
     * @param resource $lob stream handle
     * @param string $data reference to a variable that will hold data
     *                          to be read from the large object input stream
     * @param integer $length    value that indicates the largest ammount ofdata
     *                          to be read from the large object input stream.
     * @return mixed the effective number of bytes read from the large object
     *                      input stream on sucess or an MDB2 error object.
     * @access public
     * @see endOfLOB()
     */
    function _readLOB($lob, $length)
    {
        return substr($lob['value'], $lob['position'], $length);
    }

    // }}}
    // {{{ _endOfLOB()

    /**
     * Determine whether it was reached the end of the large object and
     * therefore there is no more data to be read for the its input stream.
     *
     * @param array $lob array
     * @return mixed true or false on success, a MDB2 error on failure
     * @access protected
     */
    function _endOfLOB($lob)
    {
        return $lob['endOfLOB'];
    }

    // }}}
    // {{{ destroyLOB()

    /**
     * Free any resources allocated during the lifetime of the large object
     * handler object.
     *
     * @param resource $lob stream handle
     * @access public
     */
    function destroyLOB($lob)
    {
        $lob_data = stream_get_meta_data($lob);
        $lob_index = $lob_data['wrapper_data']->lob_index;
        fclose($lob);
        if (isset($this->lobs[$lob_index])) {
            $this->_destroyLOB($this->lobs[$lob_index]);
            unset($this->lobs[$lob_index]);
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ _destroyLOB()

    /**
     * Free any resources allocated during the lifetime of the large object
     * handler object.
     *
     * @param array $lob array
     * @access private
     */
    function _destroyLOB(&$lob)
    {
        return MDB2_OK;
    }

    // }}}
    // {{{ implodeArray()

    /**
     * apply a type to all values of an array and return as a comma seperated string
     * useful for generating IN statements
     *
     * @access public
     *
     * @param array $array data array
     * @param string $type determines type of the field
     *
     * @return string comma seperated values
     */
    function implodeArray($array, $type = false)
    {
        if (!is_array($array) || empty($array)) {
            return 'NULL';
        }
        if ($type) {
            foreach ($array as $value) {
                $return[] = $this->quote($value, $type);
            }
        } else {
            $return = $array;
        }
        return implode(', ', $return);
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $match = '';
        if (!is_null($operator)) {
            $operator = strtoupper($operator);
            switch ($operator) {
            // case insensitive
            case 'ILIKE':
                if (is_null($field)) {
                    return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                        'case insensitive LIKE matching requires passing the field name', __FUNCTION__);
                }
                $db->loadModule('Function', null, true);
                $match = $db->function->lower($field).' LIKE ';
                break;
            // case sensitive
            case 'LIKE':
                $match = is_null($field) ? 'LIKE ' : $field.' LIKE ';
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
                if ($operator === 'ILIKE') {
                    $value = strtolower($value);
                }
                $escaped = $db->escape($value);
                if (PEAR::isError($escaped)) {
                    return $escaped;
                }
                $match.= $db->escapePattern($escaped);
            }
        }
        $match.= "'";
        $match.= $this->patternEscapeString();
        return $match;
    }

    // }}}
    // {{{ patternEscapeString()

    /**
     * build string to define pattern escape character
     *
     * @access public
     *
     * @return string define pattern escape character
     */
    function patternEscapeString()
    {
        return '';
    }

    // }}}
    // {{{ mapNativeDatatype()

    /**
     * Maps a native array description of a field to a MDB2 datatype and length
     *
     * @param array  $field native field description
     * @return array containing the various possible types, length, sign, fixed
     * @access public
     */
    function mapNativeDatatype($field)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        // If the user has specified an option to map the native field
        // type to a custom MDB2 datatype...
        $db_type = strtok($field['type'], '(), ');
        if (!empty($db->options['nativetype_map_callback'][$db_type])) {
            return call_user_func_array($db->options['nativetype_map_callback'][$db_type], array($db, $field));
        }

        // Otherwise perform the built-in (i.e. normal) MDB2 native type to
        // MDB2 datatype conversion
        return $this->_mapNativeDatatype($field);
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'method not implemented', __FUNCTION__);
    }

    // }}}
    // {{{ mapPrepareDatatype()

    /**
     * Maps an mdb2 datatype to mysqli prepare type
     *
     * @param string $type
     * @return string
     * @access public
     */
    function mapPrepareDatatype($type)
    {
        $db =& $this->getDBInstance();
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

        return $type;
    }
}
?>
