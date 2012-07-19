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

// $Id: oci8.php 295587 2010-02-28 17:16:38Z quipo $

require_once 'MDB2/Driver/Datatype/Common.php';

/**
 * MDB2 OCI8 driver
 *
 * @package MDB2
 * @category Database
 * @author Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Driver_Datatype_oci8 extends MDB2_Driver_Datatype_Common
{
    // {{{ _baseConvertResult()

    /**
     * general type conversion method
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
        case 'text':
            if (is_object($value) && is_a($value, 'OCI-Lob')) {
                //LOB => fetch into variable
                $clob = $this->_baseConvertResult($value, 'clob', $rtrim);
                if (!PEAR::isError($clob) && is_resource($clob)) {
                    $clob_value = '';
                    while (!feof($clob)) {
                        $clob_value .= fread($clob, 8192);
                    }
                    $this->destroyLOB($clob);
                }
                $value = $clob_value;
            }
            if ($rtrim) {
                $value = rtrim($value);
            }
            return $value;
        case 'date':
            return substr($value, 0, strlen('YYYY-MM-DD'));
        case 'time':
            return substr($value, strlen('YYYY-MM-DD '), strlen('HH:MI:SS'));
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
            $length = !empty($field['length'])
                ? $field['length'] : $db->options['default_text_field_length'];
            $fixed = !empty($field['fixed']) ? $field['fixed'] : false;
            return $fixed ? 'CHAR('.$length.')' : 'VARCHAR2('.$length.')';
        case 'clob':
            return 'CLOB';
        case 'blob':
            return 'BLOB';
        case 'integer':
            if (!empty($field['length'])) {
                switch((int)$field['length']) {
                    case 1:  $digit = 3;  break;
                    case 2:  $digit = 5;  break;
                    case 3:  $digit = 8;  break;
                    case 4:  $digit = 10; break;
                    case 5:  $digit = 13; break;
                    case 6:  $digit = 15; break;
                    case 7:  $digit = 17; break;
                    case 8:  $digit = 20; break;
                    default: $digit = 10;
                }
                return 'NUMBER('.$digit.')';
            }
            return 'INT';
        case 'boolean':
            return 'NUMBER(1)';
        case 'date':
        case 'time':
        case 'timestamp':
            return 'DATE';
        case 'float':
            return 'NUMBER';
        case 'decimal':
            $scale = !empty($field['scale']) ? $field['scale'] : $db->options['decimal_places'];
            return 'NUMBER(*,'.$scale.')';
        }
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
     *        a DBMS specific format.
     * @access protected
     */
    function _quoteCLOB($value, $quote, $escape_wildcards)
    {
        return 'EMPTY_CLOB()';
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
     *        a DBMS specific format.
     * @access protected
     */
    function _quoteBLOB($value, $quote, $escape_wildcards)
    {
        return 'EMPTY_BLOB()';
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
     *        a DBMS specific format.
     * @access protected
     */
    function _quoteDate($value, $quote, $escape_wildcards)
    {
       return $this->_quoteText("$value 00:00:00", $quote, $escape_wildcards);
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
     *        a DBMS specific format.
     * @access protected
     */
    //function _quoteTimestamp($value, $quote, $escape_wildcards)
    //{
    //   return $this->_quoteText($value, $quote, $escape_wildcards);
    //}

    // }}}
    // {{{ _quoteTime()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     *        compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
     * @access protected
     */
    function _quoteTime($value, $quote, $escape_wildcards)
    {
       return $this->_quoteText("0001-01-01 $value", $quote, $escape_wildcards);
    }

    // }}}
    // {{{ writeLOBToFile()

    /**
     * retrieve LOB from the database
     *
     * @param array $lob array
     * @param string $file name of the file into which the LOb should be fetched
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access protected
     */
    function writeLOBToFile($lob, $file)
    {
        if (preg_match('/^(\w+:\/\/)(.*)$/', $file, $match)) {
            if ($match[1] == 'file://') {
                $file = $match[2];
            }
        }
        $lob_data = stream_get_meta_data($lob);
        $lob_index = $lob_data['wrapper_data']->lob_index;
        $result = $this->lobs[$lob_index]['resource']->writetofile($file);
        $this->lobs[$lob_index]['resource']->seek(0);
        if (!$result) {
            $db = $this->getDBInstance();
            if (PEAR::isError($db)) {
                return $db;
            }

            return $db->raiseError(null, null, null,
                'Unable to write LOB to file', __FUNCTION__);
        }
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
        if (!is_object($lob['resource'])) {
            $db = $this->getDBInstance();
            if (PEAR::isError($db)) {
                return $db;
            }

           return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
               'attemped to retrieve LOB from non existing or NULL column', __FUNCTION__);
        }

        if (!$lob['loaded']
#            && !method_exists($lob['resource'], 'read')
        ) {
            $lob['value'] = $lob['resource']->load();
            $lob['resource']->seek(0);
        }
        $lob['loaded'] = true;
        return MDB2_OK;
    }

    // }}}
    // {{{ _readLOB()

    /**
     * Read data from large object input stream.
     *
     * @param array $lob array
     * @param blob $data reference to a variable that will hold data to be
     *      read from the large object input stream
     * @param int $length integer value that indicates the largest ammount of
     *      data to be read from the large object input stream.
     * @return mixed length on success, a MDB2 error on failure
     * @access protected
     */
    function _readLOB($lob, $length)
    {
        if ($lob['loaded']) {
            return parent::_readLOB($lob, $length);
        }

        if (!is_object($lob['resource'])) {
            $db = $this->getDBInstance();
            if (PEAR::isError($db)) {
                return $db;
            }

           return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
               'attemped to retrieve LOB from non existing or NULL column', __FUNCTION__);
        }

        $data = $lob['resource']->read($length);
        if (!is_string($data)) {
            $db = $this->getDBInstance();
            if (PEAR::isError($db)) {
                return $db;
            }

            return $db->raiseError(null, null, null,
                    'Unable to read LOB', __FUNCTION__);
        }
        return $data;
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
        return " ESCAPE '". $db->string_quoting['escape_pattern'] ."'";
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
        $type = array();
        $length = $unsigned = $fixed = null;
        if (!empty($field['length'])) {
            $length = $field['length'];
        }
        switch ($db_type) {
        case 'integer':
        case 'pls_integer':
        case 'binary_integer':
            $type[] = 'integer';
            if ($length == '1') {
                $type[] = 'boolean';
                if (preg_match('/^(is|has)/', $field['name'])) {
                    $type = array_reverse($type);
                }
            }
            break;
        case 'varchar':
        case 'varchar2':
        case 'nvarchar2':
            $fixed = false;
        case 'char':
        case 'nchar':
            $type[] = 'text';
            if ($length == '1') {
                $type[] = 'boolean';
                if (preg_match('/^(is|has)/', $field['name'])) {
                    $type = array_reverse($type);
                }
            }
            if ($fixed !== false) {
                $fixed = true;
            }
            break;
        case 'date':
        case 'timestamp':
            $type[] = 'timestamp';
            $length = null;
            break;
        case 'float':
            $type[] = 'float';
            break;
        case 'number':
            if (!empty($field['scale'])) {
                $type[] = 'decimal';
                $length = $length.','.$field['scale'];
            } else {
                $type[] = 'integer';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                }
            }
            break;
        case 'long':
            $type[] = 'text';
        case 'clob':
        case 'nclob':
            $type[] = 'clob';
            break;
        case 'blob':
        case 'raw':
        case 'long raw':
        case 'bfile':
            $type[] = 'blob';
            $length = null;
            break;
        case 'rowid':
        case 'urowid':
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
}

?>