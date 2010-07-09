<?php
/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 1998-2008 Manuel Lemos, Tomas V.V.Cox,
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
 * Author: Igor Feghali <ifeghali@php.net>
 *
 * @category Database
 * @package  MDB2_Schema
 * @author   Igor Feghali <ifeghali@php.net>
 * @license  BSD http://www.opensource.org/licenses/bsd-license.php
 * @version  CVS: $Id: Parser2.php,v 1.12 2008/11/30 03:34:00 clockwerx Exp $
 * @link     http://pear.php.net/packages/MDB2_Schema
 */

require_once 'XML/Unserializer.php';
require_once 'MDB2/Schema/Validate.php';

/**
 * Parses an XML schema file
 *
 * @category Database
 * @package  MDB2_Schema
 * @author   Lukas Smith <smith@pooteeweet.org>
 * @author   Igor Feghali <ifeghali@php.net>
 * @license  BSD http://www.opensource.org/licenses/bsd-license.php
 * @link     http://pear.php.net/packages/MDB2_Schema
 */
class MDB2_Schema_Parser2 extends XML_Unserializer
{
    var $database_definition = array();

    var $database_loaded = array();

    var $variables = array();

    var $error;

    var $structure = false;

    var $val;

    var $options = array();

    var $table = array();

    var $table_name = '';

    var $field = array();

    var $field_name = '';

    var $index = array();

    var $index_name = '';

    var $constraint = array();

    var $constraint_name = '';

    var $sequence = array();

    var $sequence_name = '';

    var $init = array();

    function __construct($variables, $fail_on_invalid_names = true, $structure = false, $valid_types = array(), $force_defaults = true)
    {
        // force ISO-8859-1 due to different defaults for PHP4 and PHP5
        // todo: this probably needs to be investigated some more and cleaned up
        $this->options['encoding'] = 'ISO-8859-1';

        $this->options['XML_UNSERIALIZER_OPTION_ATTRIBUTES_PARSE']    = true;
        $this->options['XML_UNSERIALIZER_OPTION_ATTRIBUTES_ARRAYKEY'] = false;

        $this->options['forceEnum'] = array('table', 'field', 'index', 'foreign', 'insert', 'update', 'delete', 'sequence');

        /*
         * todo: find a way to force the following items not to be parsed as arrays
         * as it cause problems in functions with multiple arguments
         */
        //$this->options['forceNEnum'] = array('value', 'column');
        $this->variables = $variables;
        $this->structure = $structure;

        $this->val =& new MDB2_Schema_Validate($fail_on_invalid_names, $valid_types, $force_defaults);
        parent::XML_Unserializer($this->options);
    }

    function MDB2_Schema_Parser2($variables, $fail_on_invalid_names = true, $structure = false, $valid_types = array(), $force_defaults = true)
    {
        $this->__construct($variables, $fail_on_invalid_names, $structure, $valid_types, $force_defaults);
    }

    function parse()
    {
        $result = $this->unserialize($this->filename, true);

        if (PEAR::isError($result)) {
            return $result;
        } else {
            $this->database_loaded = $this->getUnserializedData();
            return $this->fixDatabaseKeys($this->database_loaded);
        }
    }

    function setInputFile($filename)
    {
        $this->filename = $filename;
        return MDB2_OK;
    }

    function renameKey(&$arr, $oKey, $nKey)
    {
        $arr[$nKey] = &$arr[$oKey];
        unset($arr[$oKey]);
    }

    function fixDatabaseKeys($database)
    {
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

        if (!empty($database['name'])) {
            $this->database_definition['name'] = $database['name'];
        }
        if (!empty($database['create'])) {
            $this->database_definition['create'] = $database['create'];
        }
        if (!empty($database['overwrite'])) {
            $this->database_definition['overwrite'] = $database['overwrite'];
        }
        if (!empty($database['charset'])) {
            $this->database_definition['charset'] = $database['charset'];
        }
        if (!empty($database['description'])) {
            $this->database_definition['description'] = $database['description'];
        }
        if (!empty($database['comments'])) {
            $this->database_definition['comments'] = $database['comments'];
        }

        if (!empty($database['table']) && is_array($database['table'])) {
            foreach ($database['table'] as $table) {
                $this->fixTableKeys($table);
            }
        }

        if (!empty($database['sequence']) && is_array($database['sequence'])) {
            foreach ($database['sequence'] as $sequence) {
                $this->fixSequenceKeys($sequence);
            }
        }

        $result = $this->val->validateDatabase($this->database_definition);
        if (PEAR::isError($result)) {
            return $this->raiseError($result->getUserinfo());
        }

        return MDB2_OK;
    }

    function fixTableKeys($table)
    {
        $this->table = array(
            'was' => '',
            'description' => '',
            'comments' => '',
            'fields' => array(),
            'indexes' => array(),
            'constraints' => array(),
            'initialization' => array()
        );

        if (!empty($table['name'])) {
            $this->table_name = $table['name'];
        } else {
            $this->table_name = '';
        }
        if (!empty($table['was'])) {
            $this->table['was'] = $table['was'];
        }
        if (!empty($table['description'])) {
            $this->table['description'] = $table['description'];
        }
        if (!empty($table['comments'])) {
            $this->table['comments'] = $table['comments'];
        }

        if (!empty($table['declaration']) && is_array($table['declaration'])) {
            if (!empty($table['declaration']['field']) && is_array($table['declaration']['field'])) {
                foreach ($table['declaration']['field'] as $field) {
                    $this->fixTableFieldKeys($field);
                }
            }

            if (!empty($table['declaration']['index']) && is_array($table['declaration']['index'])) {
                foreach ($table['declaration']['index'] as $index) {
                    $this->fixTableIndexKeys($index);
                }
            }

            if (!empty($table['declaration']['foreign']) && is_array($table['declaration']['foreign'])) {
                foreach ($table['declaration']['foreign'] as $constraint) {
                    $this->fixTableConstraintKeys($constraint);
                }
            }
        }

        if (!empty($table['initialization']) && is_array($table['initialization'])) {
            if (!empty($table['initialization']['insert']) && is_array($table['initialization']['insert'])) {
                foreach ($table['initialization']['insert'] as $init) {
                    $this->fixTableInitializationKeys($init, 'insert');
                }
            }
            if (!empty($table['initialization']['update']) && is_array($table['initialization']['update'])) {
                foreach ($table['initialization']['update'] as $init) {
                    $this->fixTableInitializationKeys($init, 'update');
                }
            }
            if (!empty($table['initialization']['delete']) && is_array($table['initialization']['delete'])) {
                foreach ($table['initialization']['delete'] as $init) {
                    $this->fixTableInitializationKeys($init, 'delete');
                }
            }
        }

        $result = $this->val->validateTable($this->database_definition['tables'], $this->table, $this->table_name);
        if (PEAR::isError($result)) {
            return $this->raiseError($result->getUserinfo());
        } else {
            $this->database_definition['tables'][$this->table_name] = $this->table;
        }

        return MDB2_OK;
    }

    function fixTableFieldKeys($field)
    {
        $this->field = array();
        if (!empty($field['name'])) {
            $this->field_name = $field['name'];
        } else {
            $this->field_name = '';
        }
        if (!empty($field['was'])) {
            $this->field['was'] = $field['was'];
        }
        if (!empty($field['type'])) {
            $this->field['type'] = $field['type'];
        }
        if (!empty($field['fixed'])) {
            $this->field['fixed'] = $field['fixed'];
        }
        if (isset($field['default'])) {
            $this->field['default'] = $field['default'];
        }
        if (!empty($field['notnull'])) {
            $this->field['notnull'] = $field['notnull'];
        }
        if (!empty($field['autoincrement'])) {
            $this->field['autoincrement'] = $field['autoincrement'];
        }
        if (!empty($field['unsigned'])) {
            $this->field['unsigned'] = $field['unsigned'];
        }
        if (!empty($field['length'])) {
            $this->field['length'] = $field['length'];
        }
        if (!empty($field['description'])) {
            $this->field['description'] = $field['description'];
        }
        if (!empty($field['comments'])) {
            $this->field['comments'] = $field['comments'];
        }

        $result = $this->val->validateField($this->table['fields'], $this->field, $this->field_name);
        if (PEAR::isError($result)) {
            return $this->raiseError($result->getUserinfo());
        } else {
            $this->table['fields'][$this->field_name] = $this->field;
        }

        return MDB2_OK;
    }

    function fixTableIndexKeys($index)
    {
        $this->index = array(
            'was' => '',
            'unique' =>'',
            'primary' => '',
            'fields' => array()
        );

        if (!empty($index['name'])) {
            $this->index_name = $index['name'];
        } else {
            $this->index_name = '';
        }
        if (!empty($index['was'])) {
            $this->index['was'] = $index['was'];
        }
        if (!empty($index['unique'])) {
            $this->index['unique'] = $index['unique'];
        }
        if (!empty($index['primary'])) {
            $this->index['primary'] = $index['primary'];
        }
        if (!empty($index['field'])) {
            foreach ($index['field'] as $field) {
                if (!empty($field['name'])) {
                    $this->field_name = $field['name'];
                } else {
                    $this->field_name = '';
                }
                $this->field = array(
                    'sorting' => '',
                    'length' => ''
                );

                if (!empty($field['sorting'])) {
                    $this->field['sorting'] = $field['sorting'];
                }
                if (!empty($field['length'])) {
                    $this->field['length'] = $field['length'];
                }

                $result = $this->val->validateIndexField($this->index['fields'], $this->field, $this->field_name);
                if (PEAR::isError($result)) {
                    return $this->raiseError($result->getUserinfo());
                }

                $this->index['fields'][$this->field_name] = $this->field;
            }
        }

        $result = $this->val->validateIndex($this->table['indexes'], $this->index, $this->index_name);
        if (PEAR::isError($result)) {
            return $this->raiseError($result->getUserinfo());
        } else {
            $this->table['indexes'][$this->index_name] = $this->index;
        }

        return MDB2_OK;
    }

    function fixTableConstraintKeys($constraint) 
    {
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

        if (!empty($constraint['name'])) {
            $this->constraint_name = $constraint['name'];
        } else {
            $this->constraint_name = '';
        }
        if (!empty($constraint['was'])) {
            $this->constraint['was'] = $constraint['was'];
        }
        if (!empty($constraint['match'])) {
            $this->constraint['match'] = $constraint['match'];
        }
        if (!empty($constraint['ondelete'])) {
            $this->constraint['ondelete'] = $constraint['ondelete'];
        }
        if (!empty($constraint['onupdate'])) {
            $this->constraint['onupdate'] = $constraint['onupdate'];
        }
        if (!empty($constraint['deferrable'])) {
            $this->constraint['deferrable'] = $constraint['deferrable'];
        }
        if (!empty($constraint['initiallydeferred'])) {
            $this->constraint['initiallydeferred'] = $constraint['initiallydeferred'];
        }
        if (!empty($constraint['field']) && is_array($constraint['field'])) {
            foreach ($constraint['field'] as $field) {
                $result = $this->val->validateConstraintField($this->constraint['fields'], $field);
                if (PEAR::isError($result)) {
                    return $this->raiseError($result->getUserinfo());
                }

                $this->constraint['fields'][$field] = '';
            }
        }

        if (!empty($constraint['references']) && is_array($constraint['references'])) {
            /**
             * As we forced 'table' to be enumerated
             * we have to fix it on the foreign-references-table context
             */
            if (!empty($constraint['references']['table']) && is_array($constraint['references']['table'])) {
                $this->constraint['references']['table'] = $constraint['references']['table'][0];
            }

            if (!empty($constraint['references']['field']) && is_array($constraint['references']['field'])) {
                foreach ($constraint['references']['field'] as $field) {
                    $result = $this->val->validateConstraintReferencedField($this->constraint['references']['fields'], $field);
                    if (PEAR::isError($result)) {
                        return $this->raiseError($result->getUserinfo());
                    }

                    $this->constraint['references']['fields'][$field] = '';
                }
            }
        }

        $result = $this->val->validateConstraint($this->table['constraints'], $this->constraint, $this->constraint_name);
        if (PEAR::isError($result)) {
            return $this->raiseError($result->getUserinfo());
        } else {
            $this->table['constraints'][$this->constraint_name] = $this->constraint;
        }

        return MDB2_OK;
    }

    function fixTableInitializationKeys($element, $type = '')
    {
        if (!empty($element['select']) && is_array($element['select'])) {
            $this->fixTableInitializationDataKeys($element['select']);
            $this->init = array( 'select' => $this->init );
        } else {
            $this->fixTableInitializationDataKeys($element);
        }

        $this->table['initialization'][] = array( 'type' => $type, 'data' => $this->init );
    }

    function fixTableInitializationDataKeys($element)
    {
        $this->init = array();
        if (!empty($element['field']) && is_array($element['field'])) {
            foreach ($element['field'] as $field) {
                $name = $field['name'];
                unset($field['name']);

                $this->setExpression($field);
                $this->init['field'][] = array( 'name' => $name, 'group' => $field );
            }
        }
        /**
         * As we forced 'table' to be enumerated
         * we have to fix it on the insert-select context
         */
        if (!empty($element['table']) && is_array($element['table'])) {
            $this->init['table'] = $element['table'][0];
        }
        if (!empty($element['where']) && is_array($element['where'])) {
            $this->init['where'] = $element['where'];
            $this->setExpression($this->init['where']);
        }
    }

    function setExpression(&$arr)
    {
        $element = each($arr);

        $arr = array( 'type' => $element['key'] );

        $element = $element['value'];

        switch ($arr['type']) {
        case 'null':
            break;
        case 'value':
        case 'column':
            $arr['data'] = $element;
            break;
        case 'function':
            if (!empty($element)
                && is_array($element)
            ) {
                $arr['data'] = array( 'name' => $element['name'] );
                unset($element['name']);

                foreach ($element as $type => $value) {
                    if (!empty($value)) {
                        if (is_array($value)) {
                            foreach ($value as $argument) {
                                $argument = array( $type => $argument );
                                $this->setExpression($argument);
                                $arr['data']['arguments'][] = $argument;
                            }
                        } else {
                            $arr['data']['arguments'][] = array( 'type' => $type, 'data' => $value );
                        }
                    }
                }
            }
            break;
        case 'expression':
            $arr['data'] = array( 'operants' => array(), 'operator' => $element['operator'] );
            unset($element['operator']);

            foreach ($element as $k => $v) {
                $argument = array( $k => $v );
                $this->setExpression($argument);
                $arr['data']['operants'][] = $argument;
            }
            break;
        }
    }

    function fixSequenceKeys($sequence)
    {
        $this->sequence = array(
            'was' => '',
            'start' => '',
            'description' => '',
            'comments' => '',
            'on' => array('table' => '', 'field' => '')
        );

        if (!empty($sequence['name'])) {
            $this->sequence_name = $sequence['name'];
        } else {
            $this->sequence_name = '';
        }
        if (!empty($sequence['was'])) {
            $this->sequence['was'] = $sequence['was'];
        }
        if (!empty($sequence['start'])) {
            $this->sequence['start'] = $sequence['start'];
        }
        if (!empty($sequence['description'])) {
            $this->sequence['description'] = $sequence['description'];
        }
        if (!empty($sequence['comments'])) {
            $this->sequence['comments'] = $sequence['comments'];
        }
        if (!empty($sequence['on']) && is_array($sequence['on'])) {
            /**
             * As we forced 'table' to be enumerated
             * we have to fix it on the sequence-on-table context
             */
            if (!empty($sequence['on']['table']) && is_array($sequence['on']['table'])) {
                $this->sequence['on']['table'] = $sequence['on']['table'][0];
            }

            /**
             * As we forced 'field' to be enumerated
             * we have to fix it on the sequence-on-field context
             */
            if (!empty($sequence['on']['field']) && is_array($sequence['on']['field'])) {
                $this->sequence['on']['field'] = $sequence['on']['field'][0];
            }
        }

        $result = $this->val->validateSequence($this->database_definition['sequences'], $this->sequence, $this->sequence_name);
        if (PEAR::isError($result)) {
            return $this->raiseError($result->getUserinfo());
        } else {
            $this->database_definition['sequences'][$this->sequence_name] = $this->sequence;
        }

        return MDB2_OK;
    }

    function &raiseError($msg = null, $ecode = MDB2_SCHEMA_ERROR_PARSE)
    {
        if (is_null($this->error)) {
            $error = 'Parser error: '.$msg."\n";

            $this->error =& MDB2_Schema::raiseError($ecode, null, null, $error);
        }
        return $this->error;
    }
}

?>
