<?php /* vim: se et ts=4 sw=4 sts=4 fdm=marker tw=80: */
/**
 * Copyright (c) 1998-2010 Manuel Lemos, Tomas V.V.Cox,
 * Stig. S. Bakken, Lukas Smith, Igor Feghali
 * All rights reserved.
 *
 * MDB2_Schema enables users to maintain RDBMS independant schema files
 * in XML that can be used to manipulate both data and database schemas
 * This LICENSE is in the BSD license style.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in the
 * documentation and/or other materials provided with the distribution.
 *
 * Neither the name of Manuel Lemos, Tomas V.V.Cox, Stig. S. Bakken,
 * Lukas Smith, Igor Feghali nor the names of his contributors may be
 * used to endorse or promote products derived from this software
 * without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE
 * REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 *  OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY
 * WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * PHP version 5
 *
 * @category Database
 * @package  MDB2_Schema
 * @author   Christian Dickmann <dickmann@php.net>
 * @author   Igor Feghali <ifeghali@php.net>
 * @license  BSD http://www.opensource.org/licenses/bsd-license.php
 * @version  SVN: $Id$
 * @link     http://pear.php.net/packages/MDB2_Schema
 */

require_once 'XML/Parser.php';
require_once 'MDB2/Schema/Validate.php';

/**
 * Parses an XML schema file
 *
 * @category Database
 * @package  MDB2_Schema
 * @author   Christian Dickmann <dickmann@php.net>
 * @license  BSD http://www.opensource.org/licenses/bsd-license.php
 * @link     http://pear.php.net/packages/MDB2_Schema
 */
class MDB2_Schema_Parser extends XML_Parser
{
    var $database_definition = array();

    var $elements = array();

    var $element = '';

    var $count = 0;

    var $table = array();

    var $table_name = '';

    var $field = array();

    var $field_name = '';

    var $init = array();

    var $init_function = array();

    var $init_expression = array();

    var $init_field = array();

    var $index = array();

    var $index_name = '';

    var $constraint = array();

    var $constraint_name = '';

    var $var_mode = false;

    var $variables = array();

    var $sequence = array();

    var $sequence_name = '';

    var $error;

    var $structure = false;

    var $val;

    /**
     * PHP 5 constructor
     *
     * @param array $variables              mixed array with user defined schema
     *                                      variables
     * @param bool  $fail_on_invalid_names  array with reserved words per RDBMS
     * @param array $structure              multi dimensional array with 
     *                                      database schema and data
     * @param array $valid_types            information of all valid fields 
     *                                      types
     * @param bool  $force_defaults         if true sets a default value to
     *                                      field when not explicit
     * @param int   $max_identifiers_length maximum allowed size for entities 
     *                                      name
     *
     * @return void
     *
     * @access public
     * @static
     */
    function __construct($variables, $fail_on_invalid_names = true,
        $structure = false, $valid_types = array(), $force_defaults = true,
        $max_identifiers_length = null
    ) {
        // force ISO-8859-1 due to different defaults for PHP4 and PHP5
        // todo: this probably needs to be investigated some more andcleaned up
        parent::__construct('ISO-8859-1');

        $this->variables = $variables;
        $this->structure = $structure;
        $this->val       = new MDB2_Schema_Validate(
            $fail_on_invalid_names,
            $valid_types,
            $force_defaults,
            $max_identifiers_length
        );
    }

    /**
     * PHP 4 compatible constructor
     *
     * @param array $variables              mixed array with user defined schema
     *                                      variables
     * @param bool  $fail_on_invalid_names  array with reserved words per RDBMS
     * @param array $structure              multi dimensional array with 
     *                                      database schema and data
     * @param array $valid_types            information of all valid fields 
     *                                      types
     * @param bool  $force_defaults         if true sets a default value to
     *                                      field when not explicit
     * @param int   $max_identifiers_length maximum allowed size for entities 
     *                                      name
     *
     * @return void
     *
     * @access public
     * @static
     */
    function MDB2_Schema_Parser($variables, $fail_on_invalid_names = true,
        $structure = false, $valid_types = array(), $force_defaults = true,
        $max_identifiers_length = null
    ) {
        $this->__construct($variables, $fail_on_invalid_names, $structure, $valid_types, $force_defaults);
    }

    /**
     * Triggered when reading a XML open tag <element>
     *
     * @param resource $xp      xml parser resource
     * @param string   $element element name
     * @param array    $attribs attributes
     *
     * @return void
     * @access private
     * @static
     */
    function startHandler($xp, $element, &$attribs)
    {
        if (strtolower($element) == 'variable') {
            $this->var_mode = true;
            return;
        }

        $this->elements[$this->count++] = strtolower($element);

        $this->element = implode('-', $this->elements);

        switch ($this->element) {
        /* Initialization */
        case 'database-table-initialization':
            $this->table['initialization'] = array();
            break;

        /* Insert */
        /* insert: field+ */
        case 'database-table-initialization-insert':
            $this->init = array('type' => 'insert', 'data' => array('field' => array()));
            break;
        /* insert-select: field+, table, where? */
        case 'database-table-initialization-insert-select':
            $this->init['data']['table'] = '';
            break;

        /* Update */
        /* update: field+, where? */
        case 'database-table-initialization-update':
            $this->init = array('type' => 'update', 'data' => array('field' => array()));
            break;

        /* Delete */
        /* delete: where */
        case 'database-table-initialization-delete':
            $this->init = array('type' => 'delete', 'data' => array('where' => array()));
            break;

        /* Insert and Update */
        case 'database-table-initialization-insert-field':
        case 'database-table-initialization-insert-select-field':
        case 'database-table-initialization-update-field':
            $this->init_field = array('name' => '', 'group' => array());
            break;
        case 'database-table-initialization-insert-field-value':
        case 'database-table-initialization-insert-select-field-value':
        case 'database-table-initialization-update-field-value':
            /* if value tag is empty cdataHandler is not called so we must force value element creation here */
            $this->init_field['group'] = array('type' => 'value', 'data' => '');
            break;
        case 'database-table-initialization-insert-field-null':
        case 'database-table-initialization-insert-select-field-null':
        case 'database-table-initialization-update-field-null':
            $this->init_field['group'] = array('type' => 'null');
            break;
        case 'database-table-initialization-insert-field-function':
        case 'database-table-initialization-insert-select-field-function':
        case 'database-table-initialization-update-field-function':
            $this->init_function = array('name' => '');
            break;
        case 'database-table-initialization-insert-field-expression':
        case 'database-table-initialization-insert-select-field-expression':
        case 'database-table-initialization-update-field-expression':
            $this->init_expression = array();
            break;

        /* All */
        case 'database-table-initialization-insert-select-where':
        case 'database-table-initialization-update-where':
        case 'database-table-initialization-delete-where':
            $this->init['data']['where'] = array('type' => '', 'data' => array());
            break;
        case 'database-table-initialization-insert-select-where-expression':
        case 'database-table-initialization-update-where-expression':
        case 'database-table-initialization-delete-where-expression':
            $this->init_expression = array();
            break;

        /* One level simulation of expression-function recursion */
        case 'database-table-initialization-insert-field-expression-function':
        case 'database-table-initialization-insert-select-field-expression-function':
        case 'database-table-initialization-insert-select-where-expression-function':
        case 'database-table-initialization-update-field-expression-function':
        case 'database-table-initialization-update-where-expression-function':
        case 'database-table-initialization-delete-where-expression-function':
            $this->init_function = array('name' => '');
            break;

        /* One level simulation of function-expression recursion */
        case 'database-table-initialization-insert-field-function-expression':
        case 'database-table-initialization-insert-select-field-function-expression':
        case 'database-table-initialization-insert-select-where-function-expression':
        case 'database-table-initialization-update-field-function-expression':
        case 'database-table-initialization-update-where-function-expression':
        case 'database-table-initialization-delete-where-function-expression':
            $this->init_expression = array();
            break;

        /* Definition */
        case 'database':
            $this->database_definition = array(
                'name' => '',
                'create' => '',
                'overwrite' => '',
                'charset' => '',
                'description' => '',
                'comments' => '',
                'tables' => array(),
                'sequences' => array()
            );
            break;
        case 'database-table':
            $this->table_name = '';

            $this->table = array(
                'was' => '',
                'description' => '',
                'comments' => '',
                'fields' => array(),
                'indexes' => array(),
                'constraints' => array(),
                'initialization' => array()
            );
            break;
        case 'database-table-declaration-field':
        case 'database-table-declaration-foreign-field':
        case 'database-table-declaration-foreign-references-field':
            $this->field_name = '';

            $this->field = array();
            break;
        case 'database-table-declaration-index-field':
            $this->field_name = '';

            $this->field = array('sorting' => '', 'length' => '');
            break;
        /* force field attributes to be initialized when the tag is empty in the XML */
        case 'database-table-declaration-field-was':
            $this->field['was'] = '';
            break;
        case 'database-table-declaration-field-type':
            $this->field['type'] = '';
            break;
        case 'database-table-declaration-field-fixed':
            $this->field['fixed'] = '';
            break;
        case 'database-table-declaration-field-default':
            $this->field['default'] = '';
            break;
        case 'database-table-declaration-field-notnull':
            $this->field['notnull'] = '';
            break;
        case 'database-table-declaration-field-autoincrement':
            $this->field['autoincrement'] = '';
            break;
        case 'database-table-declaration-field-unsigned':
            $this->field['unsigned'] = '';
            break;
        case 'database-table-declaration-field-length':
            $this->field['length'] = '';
            break;
        case 'database-table-declaration-field-description':
            $this->field['description'] = '';
            break;
        case 'database-table-declaration-field-comments':
            $this->field['comments'] = '';
            break;
        case 'database-table-declaration-index':
            $this->index_name = '';

            $this->index = array(
                'was' => '',
                'unique' =>'',
                'primary' => '',
                'fields' => array()
            );
            break;
        case 'database-table-declaration-foreign':
            $this->constraint_name = '';

            $this->constraint = array(
                'was' => '',
                'match' => '',
                'ondelete' => '',
                'onupdate' => '',
                'deferrable' => '',
                'initiallydeferred' => '',
                'foreign' => true,
                'fields' => array(),
                'references' => array('table' => '', 'fields' => array())
            );
            break;
        case 'database-sequence':
            $this->sequence_name = '';

            $this->sequence = array(
                'was' => '',
                'start' => '',
                'description' => '',
                'comments' => '',
            );
            break;
        }
    }

    /**
     * Triggered when reading a XML close tag </element>
     *
     * @param resource $xp      xml parser resource
     * @param string   $element element name
     *
     * @return void
     * @access private
     * @static
     */
    function endHandler($xp, $element)
    {
        if (strtolower($element) == 'variable') {
            $this->var_mode = false;
            return;
        }

        switch ($this->element) {
        /* Initialization */

        /* Insert */
        case 'database-table-initialization-insert-select':
            $this->init['data'] = array('select' => $this->init['data']);
            break;

        /* Insert and Delete */
        case 'database-table-initialization-insert-field':
        case 'database-table-initialization-insert-select-field':
        case 'database-table-initialization-update-field':
            $result = $this->val->validateDataField($this->table['fields'], $this->init['data']['field'], $this->init_field);
            if (PEAR::isError($result)) {
                $this->raiseError($result->getUserinfo(), 0, $xp, $result->getCode());
            } else {
                $this->init['data']['field'][] = $this->init_field;
            }
            break;
        case 'database-table-initialization-insert-field-function':
        case 'database-table-initialization-insert-select-field-function':
        case 'database-table-initialization-update-field-function':
            $this->init_field['group'] = array('type' => 'function', 'data' => $this->init_function);
            break;
        case 'database-table-initialization-insert-field-expression':
        case 'database-table-initialization-insert-select-field-expression':
        case 'database-table-initialization-update-field-expression':
            $this->init_field['group'] = array('type' => 'expression', 'data' => $this->init_expression);
            break;

        /* All */
        case 'database-table-initialization-insert-select-where-expression':
        case 'database-table-initialization-update-where-expression':
        case 'database-table-initialization-delete-where-expression':
            $this->init['data']['where']['type'] = 'expression';
            $this->init['data']['where']['data'] = $this->init_expression;
            break;
        case 'database-table-initialization-insert':
        case 'database-table-initialization-delete':
        case 'database-table-initialization-update':
            $this->table['initialization'][] = $this->init;
            break;

        /* One level simulation of expression-function recursion */
        case 'database-table-initialization-insert-field-expression-function':
        case 'database-table-initialization-insert-select-field-expression-function':
        case 'database-table-initialization-insert-select-where-expression-function':
        case 'database-table-initialization-update-field-expression-function':
        case 'database-table-initialization-update-where-expression-function':
        case 'database-table-initialization-delete-where-expression-function':
            $this->init_expression['operants'][] = array('type' => 'function', 'data' => $this->init_function);
            break;

        /* One level simulation of function-expression recursion */
        case 'database-table-initialization-insert-field-function-expression':
        case 'database-table-initialization-insert-select-field-function-expression':
        case 'database-table-initialization-insert-select-where-function-expression':
        case 'database-table-initialization-update-field-function-expression':
        case 'database-table-initialization-update-where-function-expression':
        case 'database-table-initialization-delete-where-function-expression':
            $this->init_function['arguments'][] = array('type' => 'expression', 'data' => $this->init_expression);
            break;

        /* Table definition */
        case 'database-table':
            $result = $this->val->validateTable($this->database_definition['tables'], $this->table, $this->table_name);
            if (PEAR::isError($result)) {
                $this->raiseError($result->getUserinfo(), 0, $xp, $result->getCode());
            } else {
                $this->database_definition['tables'][$this->table_name] = $this->table;
            }
            break;
        case 'database-table-name':
            if (isset($this->structure['tables'][$this->table_name])) {
                $this->table = $this->structure['tables'][$this->table_name];
            }
            break;

        /* Field declaration */
        case 'database-table-declaration-field':
            $result = $this->val->validateField($this->table['fields'], $this->field, $this->field_name);
            if (PEAR::isError($result)) {
                $this->raiseError($result->getUserinfo(), 0, $xp, $result->getCode());
            } else {
                $this->table['fields'][$this->field_name] = $this->field;
            }
            break;

        /* Index declaration */
        case 'database-table-declaration-index':
            $result = $this->val->validateIndex($this->table['indexes'], $this->index, $this->index_name);
            if (PEAR::isError($result)) {
                $this->raiseError($result->getUserinfo(), 0, $xp, $result->getCode());
            } else {
                $this->table['indexes'][$this->index_name] = $this->index;
            }
            break;
        case 'database-table-declaration-index-field':
            $result = $this->val->validateIndexField($this->index['fields'], $this->field, $this->field_name);
            if (PEAR::isError($result)) {
                $this->raiseError($result->getUserinfo(), 0, $xp, $result->getCode());
            } else {
                $this->index['fields'][$this->field_name] = $this->field;
            }
            break;

        /* Foreign Key declaration */
        case 'database-table-declaration-foreign':
            $result = $this->val->validateConstraint($this->table['constraints'], $this->constraint, $this->constraint_name);
            if (PEAR::isError($result)) {
                $this->raiseError($result->getUserinfo(), 0, $xp, $result->getCode());
            } else {
                $this->table['constraints'][$this->constraint_name] = $this->constraint;
            }
            break;
        case 'database-table-declaration-foreign-field':
            $result = $this->val->validateConstraintField($this->constraint['fields'], $this->field_name);
            if (PEAR::isError($result)) {
                $this->raiseError($result->getUserinfo(), 0, $xp, $result->getCode());
            } else {
                $this->constraint['fields'][$this->field_name] = '';
            }
            break;
        case 'database-table-declaration-foreign-references-field':
            $result = $this->val->validateConstraintReferencedField($this->constraint['references']['fields'], $this->field_name);
            if (PEAR::isError($result)) {
                $this->raiseError($result->getUserinfo(), 0, $xp, $result->getCode());
            } else {
                $this->constraint['references']['fields'][$this->field_name] = '';
            }
            break;

        /* Sequence declaration */
        case 'database-sequence':
            $result = $this->val->validateSequence($this->database_definition['sequences'], $this->sequence, $this->sequence_name);
            if (PEAR::isError($result)) {
                $this->raiseError($result->getUserinfo(), 0, $xp, $result->getCode());
            } else {
                $this->database_definition['sequences'][$this->sequence_name] = $this->sequence;
            }
            break;

        /* End of File */
        case 'database':
            $result = $this->val->validateDatabase($this->database_definition);
            if (PEAR::isError($result)) {
                $this->raiseError($result->getUserinfo(), 0, $xp, $result->getCode());
            }
            break;
        }

        unset($this->elements[--$this->count]);
        $this->element = implode('-', $this->elements);
    }

    /**
     * Pushes a MDB2_Schema_Error into stack and returns it
     *
     * @param string   $msg      textual message
     * @param int      $xmlecode PHP's XML parser error code
     * @param resource $xp       xml parser resource
     * @param int      $ecode    MDB2_Schema's error code
     *
     * @return object
     * @access private
     * @static
     */
    static function &raiseError($msg = null, $xmlecode = 0, $xp = null, $ecode = MDB2_SCHEMA_ERROR_PARSE, $userinfo = null,
                         $error_class = null,
                         $skipmsg = false)
    {
        if (is_null($this->error)) {
            $error = '';
            if (is_resource($msg)) {
                $error .= 'Parser error: '.xml_error_string(xml_get_error_code($msg));
                $xp     = $msg;
            } else {
                $error .= 'Parser error: '.$msg;
                if (!is_resource($xp)) {
                    $xp = $this->parser;
                }
            }

            if ($error_string = xml_error_string($xmlecode)) {
                $error .= ' - '.$error_string;
            }

            if (is_resource($xp)) {
                $byte   = @xml_get_current_byte_index($xp);
                $line   = @xml_get_current_line_number($xp);
                $column = @xml_get_current_column_number($xp);
                $error .= " - Byte: $byte; Line: $line; Col: $column";
            }

            $error .= "\n";

            $this->error = MDB2_Schema::raiseError($ecode, null, null, $error);
        }
        return $this->error;
    }

    /**
     * Triggered when reading data in a XML element (text between tags) 
     *
     * @param resource $xp   xml parser resource
     * @param string   $data text
     *
     * @return void
     * @access private
     * @static
     */
    function cdataHandler($xp, $data)
    {
        if ($this->var_mode == true) {
            if (!isset($this->variables[$data])) {
                $this->raiseError('variable "'.$data.'" not found', null, $xp);
                return;
            }
            $data = $this->variables[$data];
        }

        switch ($this->element) {
        /* Initialization */

        /* Insert */
        case 'database-table-initialization-insert-select-table':
            $this->init['data']['table'] = $data;
            break;

        /* Insert and Update */
        case 'database-table-initialization-insert-field-name':
        case 'database-table-initialization-insert-select-field-name':
        case 'database-table-initialization-update-field-name':
            $this->init_field['name'] .= $data;
            break;
        case 'database-table-initialization-insert-field-value':
        case 'database-table-initialization-insert-select-field-value':
        case 'database-table-initialization-update-field-value':
            $this->init_field['group']['data'] .= $data;
            break;
        case 'database-table-initialization-insert-field-function-name':
        case 'database-table-initialization-insert-select-field-function-name':
        case 'database-table-initialization-update-field-function-name':
            $this->init_function['name'] .= $data;
            break;
        case 'database-table-initialization-insert-field-function-value':
        case 'database-table-initialization-insert-select-field-function-value':
        case 'database-table-initialization-update-field-function-value':
            $this->init_function['arguments'][] = array('type' => 'value', 'data' => $data);
            break;
        case 'database-table-initialization-insert-field-function-column':
        case 'database-table-initialization-insert-select-field-function-column':
        case 'database-table-initialization-update-field-function-column':
            $this->init_function['arguments'][] = array('type' => 'column', 'data' => $data);
            break;
        case 'database-table-initialization-insert-field-column':
        case 'database-table-initialization-insert-select-field-column':
        case 'database-table-initialization-update-field-column':
            $this->init_field['group'] = array('type' => 'column', 'data' => $data);
            break;

        /* All */
        case 'database-table-initialization-insert-field-expression-operator':
        case 'database-table-initialization-insert-select-field-expression-operator':
        case 'database-table-initialization-insert-select-where-expression-operator':
        case 'database-table-initialization-update-field-expression-operator':
        case 'database-table-initialization-update-where-expression-operator':
        case 'database-table-initialization-delete-where-expression-operator':
            $this->init_expression['operator'] = $data;
            break;
        case 'database-table-initialization-insert-field-expression-value':
        case 'database-table-initialization-insert-select-field-expression-value':
        case 'database-table-initialization-insert-select-where-expression-value':
        case 'database-table-initialization-update-field-expression-value':
        case 'database-table-initialization-update-where-expression-value':
        case 'database-table-initialization-delete-where-expression-value':
            $this->init_expression['operants'][] = array('type' => 'value', 'data' => $data);
            break;
        case 'database-table-initialization-insert-field-expression-column':
        case 'database-table-initialization-insert-select-field-expression-column':
        case 'database-table-initialization-insert-select-where-expression-column':
        case 'database-table-initialization-update-field-expression-column':
        case 'database-table-initialization-update-where-expression-column':
        case 'database-table-initialization-delete-where-expression-column':
            $this->init_expression['operants'][] = array('type' => 'column', 'data' => $data);
            break;

        case 'database-table-initialization-insert-field-function-function':
        case 'database-table-initialization-insert-field-function-expression':
        case 'database-table-initialization-insert-field-expression-expression':
        case 'database-table-initialization-update-field-function-function':
        case 'database-table-initialization-update-field-function-expression':
        case 'database-table-initialization-update-field-expression-expression':
        case 'database-table-initialization-update-where-expression-expression':
        case 'database-table-initialization-delete-where-expression-expression':
            /* Recursion to be implemented yet */
            break;

        /* One level simulation of expression-function recursion */
        case 'database-table-initialization-insert-field-expression-function-name':
        case 'database-table-initialization-insert-select-field-expression-function-name':
        case 'database-table-initialization-insert-select-where-expression-function-name':
        case 'database-table-initialization-update-field-expression-function-name':
        case 'database-table-initialization-update-where-expression-function-name':
        case 'database-table-initialization-delete-where-expression-function-name':
            $this->init_function['name'] .= $data;
            break;
        case 'database-table-initialization-insert-field-expression-function-value':
        case 'database-table-initialization-insert-select-field-expression-function-value':
        case 'database-table-initialization-insert-select-where-expression-function-value':
        case 'database-table-initialization-update-field-expression-function-value':
        case 'database-table-initialization-update-where-expression-function-value':
        case 'database-table-initialization-delete-where-expression-function-value':
            $this->init_function['arguments'][] = array('type' => 'value', 'data' => $data);
            break;
        case 'database-table-initialization-insert-field-expression-function-column':
        case 'database-table-initialization-insert-select-field-expression-function-column':
        case 'database-table-initialization-insert-select-where-expression-function-column':
        case 'database-table-initialization-update-field-expression-function-column':
        case 'database-table-initialization-update-where-expression-function-column':
        case 'database-table-initialization-delete-where-expression-function-column':
            $this->init_function['arguments'][] = array('type' => 'column', 'data' => $data);
            break;

        /* One level simulation of function-expression recursion */
        case 'database-table-initialization-insert-field-function-expression-operator':
        case 'database-table-initialization-insert-select-field-function-expression-operator':
        case 'database-table-initialization-update-field-function-expression-operator':
            $this->init_expression['operator'] = $data;
            break;
        case 'database-table-initialization-insert-field-function-expression-value':
        case 'database-table-initialization-insert-select-field-function-expression-value':
        case 'database-table-initialization-update-field-function-expression-value':
            $this->init_expression['operants'][] = array('type' => 'value', 'data' => $data);
            break;
        case 'database-table-initialization-insert-field-function-expression-column':
        case 'database-table-initialization-insert-select-field-function-expression-column':
        case 'database-table-initialization-update-field-function-expression-column':
            $this->init_expression['operants'][] = array('type' => 'column', 'data' => $data);
            break;

        /* Database */
        case 'database-name':
            $this->database_definition['name'] .= $data;
            break;
        case 'database-create':
            $this->database_definition['create'] .= $data;
            break;
        case 'database-overwrite':
            $this->database_definition['overwrite'] .= $data;
            break;
        case 'database-charset':
            $this->database_definition['charset'] .= $data;
            break;
        case 'database-description':
            $this->database_definition['description'] .= $data;
            break;
        case 'database-comments':
            $this->database_definition['comments'] .= $data;
            break;

        /* Table declaration */
        case 'database-table-name':
            $this->table_name .= $data;
            break;
        case 'database-table-was':
            $this->table['was'] .= $data;
            break;
        case 'database-table-description':
            $this->table['description'] .= $data;
            break;
        case 'database-table-comments':
            $this->table['comments'] .= $data;
            break;

        /* Field declaration */
        case 'database-table-declaration-field-name':
            $this->field_name .= $data;
            break;
        case 'database-table-declaration-field-was':
            $this->field['was'] .= $data;
            break;
        case 'database-table-declaration-field-type':
            $this->field['type'] .= $data;
            break;
        case 'database-table-declaration-field-fixed':
            $this->field['fixed'] .= $data;
            break;
        case 'database-table-declaration-field-default':
            $this->field['default'] .= $data;
            break;
        case 'database-table-declaration-field-notnull':
            $this->field['notnull'] .= $data;
            break;
        case 'database-table-declaration-field-autoincrement':
            $this->field['autoincrement'] .= $data;
            break;
        case 'database-table-declaration-field-unsigned':
            $this->field['unsigned'] .= $data;
            break;
        case 'database-table-declaration-field-length':
            $this->field['length'] .= $data;
            break;
        case 'database-table-declaration-field-description':
            $this->field['description'] .= $data;
            break;
        case 'database-table-declaration-field-comments':
            $this->field['comments'] .= $data;
            break;

        /* Index declaration */
        case 'database-table-declaration-index-name':
            $this->index_name .= $data;
            break;
        case 'database-table-declaration-index-was':
            $this->index['was'] .= $data;
            break;
        case 'database-table-declaration-index-unique':
            $this->index['unique'] .= $data;
            break;
        case 'database-table-declaration-index-primary':
            $this->index['primary'] .= $data;
            break;
        case 'database-table-declaration-index-field-name':
            $this->field_name .= $data;
            break;
        case 'database-table-declaration-index-field-sorting':
            $this->field['sorting'] .= $data;
            break;
        /* Add by Leoncx */
        case 'database-table-declaration-index-field-length':
            $this->field['length'] .= $data;
            break;

        /* Foreign Key declaration */
        case 'database-table-declaration-foreign-name':
            $this->constraint_name .= $data;
            break;
        case 'database-table-declaration-foreign-was':
            $this->constraint['was'] .= $data;
            break;
        case 'database-table-declaration-foreign-match':
            $this->constraint['match'] .= $data;
            break;
        case 'database-table-declaration-foreign-ondelete':
            $this->constraint['ondelete'] .= $data;
            break;
        case 'database-table-declaration-foreign-onupdate':
            $this->constraint['onupdate'] .= $data;
            break;
        case 'database-table-declaration-foreign-deferrable':
            $this->constraint['deferrable'] .= $data;
            break;
        case 'database-table-declaration-foreign-initiallydeferred':
            $this->constraint['initiallydeferred'] .= $data;
            break;
        case 'database-table-declaration-foreign-field':
            $this->field_name .= $data;
            break;
        case 'database-table-declaration-foreign-references-table':
            $this->constraint['references']['table'] .= $data;
            break;
        case 'database-table-declaration-foreign-references-field':
            $this->field_name .= $data;
            break;

        /* Sequence declaration */
        case 'database-sequence-name':
            $this->sequence_name .= $data;
            break;
        case 'database-sequence-was':
            $this->sequence['was'] .= $data;
            break;
        case 'database-sequence-start':
            $this->sequence['start'] .= $data;
            break;
        case 'database-sequence-description':
            $this->sequence['description'] .= $data;
            break;
        case 'database-sequence-comments':
            $this->sequence['comments'] .= $data;
            break;
        case 'database-sequence-on':
            $this->sequence['on'] = array('table' => '', 'field' => '');
            break;
        case 'database-sequence-on-table':
            $this->sequence['on']['table'] .= $data;
            break;
        case 'database-sequence-on-field':
            $this->sequence['on']['field'] .= $data;
            break;
        }
    }
}
