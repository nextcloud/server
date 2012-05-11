<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * XML_Parser
 *
 * XML Parser's Simple parser class 
 *
 * PHP versions 4 and 5
 *
 * LICENSE:
 *
 * Copyright (c) 2002-2008 The PHP Group
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * The name of the author may not be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  XML
 * @package   XML_Parser
 * @author    Stephan Schmidt <schst@php.net>
 * @copyright 2004-2008 Stephan Schmidt <schst@php.net>
 * @license   http://opensource.org/licenses/bsd-license New BSD License
 * @version   CVS: $Id: Simple.php 265444 2008-08-24 21:48:21Z ashnazg $
 * @link      http://pear.php.net/package/XML_Parser
 */

/**
 * built on XML_Parser
 */
require_once 'XML/Parser.php';

/**
 * Simple XML parser class.
 *
 * This class is a simplified version of XML_Parser.
 * In most XML applications the real action is executed,
 * when a closing tag is found.
 *
 * XML_Parser_Simple allows you to just implement one callback
 * for each tag that will receive the tag with its attributes
 * and CData.
 *
 * <code>
 * require_once '../Parser/Simple.php';
 *
 * class myParser extends XML_Parser_Simple
 * {
 *     function myParser()
 *     {
 *        $this->XML_Parser_Simple();
 *      }
 * 
 *    function handleElement($name, $attribs, $data)
 *     {
 *         printf('handle %s<br>', $name);
 *     }
 * }
 * 
 * $p = &new myParser();
 * 
 * $result = $p->setInputFile('myDoc.xml');
 * $result = $p->parse();
 * </code>
 *
 * @category  XML
 * @package   XML_Parser
 * @author    Stephan Schmidt <schst@php.net>
 * @copyright 2004-2008 The PHP Group
 * @license   http://opensource.org/licenses/bsd-license New BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/XML_Parser
 */
class XML_Parser_Simple extends XML_Parser
{
    /**
     * element stack
     *
     * @access   private
     * @var      array
     */
    var $_elStack = array();

    /**
     * all character data
     *
     * @access   private
     * @var      array
     */
    var $_data = array();

    /**
     * element depth
     *
     * @access   private
     * @var      integer
     */
    var $_depth = 0;

    /**
     * Mapping from expat handler function to class method.
     *
     * @var  array
     */
    var $handler = array(
        'default_handler'                   => 'defaultHandler',
        'processing_instruction_handler'    => 'piHandler',
        'unparsed_entity_decl_handler'      => 'unparsedHandler',
        'notation_decl_handler'             => 'notationHandler',
        'external_entity_ref_handler'       => 'entityrefHandler'
    );
    
    /**
     * Creates an XML parser.
     *
     * This is needed for PHP4 compatibility, it will
     * call the constructor, when a new instance is created.
     *
     * @param string $srcenc source charset encoding, use NULL (default) to use
     *                       whatever the document specifies
     * @param string $mode   how this parser object should work, "event" for
     *                       handleElement(), "func" to have it call functions
     *                       named after elements (handleElement_$name())
     * @param string $tgtenc a valid target encoding
     */
    function XML_Parser_Simple($srcenc = null, $mode = 'event', $tgtenc = null)
    {
        $this->XML_Parser($srcenc, $mode, $tgtenc);
    }

    /**
     * inits the handlers
     *
     * @return mixed
     * @access private
     */
    function _initHandlers()
    {
        if (!is_object($this->_handlerObj)) {
            $this->_handlerObj = &$this;
        }

        if ($this->mode != 'func' && $this->mode != 'event') {
            return $this->raiseError('Unsupported mode given', 
                XML_PARSER_ERROR_UNSUPPORTED_MODE);
        }
        xml_set_object($this->parser, $this->_handlerObj);

        xml_set_element_handler($this->parser, array(&$this, 'startHandler'), 
            array(&$this, 'endHandler'));
        xml_set_character_data_handler($this->parser, array(&$this, 'cdataHandler'));
        
        /**
         * set additional handlers for character data, entities, etc.
         */
        foreach ($this->handler as $xml_func => $method) {
            if (method_exists($this->_handlerObj, $method)) {
                $xml_func = 'xml_set_' . $xml_func;
                $xml_func($this->parser, $method);
            }
        }
    }

    /**
     * Reset the parser.
     *
     * This allows you to use one parser instance
     * to parse multiple XML documents.
     *
     * @access   public
     * @return   boolean|object     true on success, PEAR_Error otherwise
     */
    function reset()
    {
        $this->_elStack = array();
        $this->_data    = array();
        $this->_depth   = 0;
        
        $result = $this->_create();
        if ($this->isError($result)) {
            return $result;
        }
        return true;
    }

    /**
     * start handler
     *
     * Pushes attributes and tagname onto a stack
     *
     * @param resource $xp       xml parser resource
     * @param string   $elem     element name
     * @param array    &$attribs attributes
     *
     * @return mixed
     * @access private
     * @final
     */
    function startHandler($xp, $elem, &$attribs)
    {
        array_push($this->_elStack, array(
            'name'    => $elem,
            'attribs' => $attribs
        ));
        $this->_depth++;
        $this->_data[$this->_depth] = '';
    }

    /**
     * end handler
     *
     * Pulls attributes and tagname from a stack
     *
     * @param resource $xp   xml parser resource
     * @param string   $elem element name
     *
     * @return mixed
     * @access private
     * @final
     */
    function endHandler($xp, $elem)
    {
        $el   = array_pop($this->_elStack);
        $data = $this->_data[$this->_depth];
        $this->_depth--;

        switch ($this->mode) {
        case 'event':
            $this->_handlerObj->handleElement($el['name'], $el['attribs'], $data);
            break;
        case 'func':
            $func = 'handleElement_' . $elem;
            if (strchr($func, '.')) {
                $func = str_replace('.', '_', $func);
            }
            if (method_exists($this->_handlerObj, $func)) {
                call_user_func(array(&$this->_handlerObj, $func), 
                    $el['name'], $el['attribs'], $data);
            }
            break;
        }
    }

    /**
     * handle character data
     *
     * @param resource $xp   xml parser resource
     * @param string   $data data
     *
     * @return void
     * @access private
     * @final
     */
    function cdataHandler($xp, $data)
    {
        $this->_data[$this->_depth] .= $data;
    }

    /**
     * handle a tag
     *
     * Implement this in your parser 
     *
     * @param string $name    element name
     * @param array  $attribs attributes
     * @param string $data    character data
     *
     * @return void
     * @access public
     * @abstract
     */
    function handleElement($name, $attribs, $data)
    {
    }

    /**
     * get the current tag depth
     *
     * The root tag is in depth 0.
     *
     * @access   public
     * @return   integer
     */
    function getCurrentDepth()
    {
        return $this->_depth;
    }

    /**
     * add some string to the current ddata.
     *
     * This is commonly needed, when a document is parsed recursively.
     *
     * @param string $data data to add
     *
     * @return void
     * @access public
     */
    function addToData($data)
    {
        $this->_data[$this->_depth] .= $data;
    }
}
?>
