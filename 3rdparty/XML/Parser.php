<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * XML_Parser
 *
 * XML Parser package
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
 * @author    Stig Bakken <ssb@fast.no>
 * @author    Tomas V.V.Cox <cox@idecnet.com>
 * @author    Stephan Schmidt <schst@php.net>
 * @copyright 2002-2008 The PHP Group
 * @license   http://opensource.org/licenses/bsd-license New BSD License
 * @version   CVS: $Id: Parser.php 302733 2010-08-24 01:09:09Z clockwerx $
 * @link      http://pear.php.net/package/XML_Parser
 */

/**
 * uses PEAR's error handling
 */
require_once 'PEAR.php';

/**
 * resource could not be created
 */
define('XML_PARSER_ERROR_NO_RESOURCE', 200);

/**
 * unsupported mode
 */
define('XML_PARSER_ERROR_UNSUPPORTED_MODE', 201);

/**
 * invalid encoding was given
 */
define('XML_PARSER_ERROR_INVALID_ENCODING', 202);

/**
 * specified file could not be read
 */
define('XML_PARSER_ERROR_FILE_NOT_READABLE', 203);

/**
 * invalid input
 */
define('XML_PARSER_ERROR_INVALID_INPUT', 204);

/**
 * remote file cannot be retrieved in safe mode
 */
define('XML_PARSER_ERROR_REMOTE', 205);

/**
 * XML Parser class.
 *
 * This is an XML parser based on PHP's "xml" extension,
 * based on the bundled expat library.
 *
 * Notes:
 * - It requires PHP 4.0.4pl1 or greater
 * - From revision 1.17, the function names used by the 'func' mode
 *   are in the format "xmltag_$elem", for example: use "xmltag_name"
 *   to handle the <name></name> tags of your xml file.
 *          - different parsing modes
 *
 * @category  XML
 * @package   XML_Parser
 * @author    Stig Bakken <ssb@fast.no>
 * @author    Tomas V.V.Cox <cox@idecnet.com>
 * @author    Stephan Schmidt <schst@php.net>
 * @copyright 2002-2008 The PHP Group
 * @license   http://opensource.org/licenses/bsd-license New BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/XML_Parser
 * @todo      create XML_Parser_Namespace to parse documents with namespaces
 * @todo      create XML_Parser_Pull
 * @todo      Tests that need to be made:
 *            - mixing character encodings
 *            - a test using all expat handlers
 *            - options (folding, output charset)
 */
class XML_Parser extends PEAR
{
    // {{{ properties

    /**
     * XML parser handle
     *
     * @var  resource
     * @see  xml_parser_create()
     */
    var $parser;

    /**
     * File handle if parsing from a file
     *
     * @var  resource
     */
    var $fp;

    /**
     * Whether to do case folding
     *
     * If set to true, all tag and attribute names will
     * be converted to UPPER CASE.
     *
     * @var  boolean
     */
    var $folding = true;

    /**
     * Mode of operation, one of "event" or "func"
     *
     * @var  string
     */
    var $mode;

    /**
     * Mapping from expat handler function to class method.
     *
     * @var  array
     */
    var $handler = array(
        'character_data_handler'            => 'cdataHandler',
        'default_handler'                   => 'defaultHandler',
        'processing_instruction_handler'    => 'piHandler',
        'unparsed_entity_decl_handler'      => 'unparsedHandler',
        'notation_decl_handler'             => 'notationHandler',
        'external_entity_ref_handler'       => 'entityrefHandler'
    );

    /**
     * source encoding
     *
     * @var string
     */
    var $srcenc;

    /**
     * target encoding
     *
     * @var string
     */
    var $tgtenc;

    /**
     * handler object
     *
     * @var object
     */
    var $_handlerObj;

    /**
     * valid encodings
     *
     * @var array
     */
    var $_validEncodings = array('ISO-8859-1', 'UTF-8', 'US-ASCII');

    // }}}
    // {{{ php4 constructor

    /**
     * Creates an XML parser.
     *
     * This is needed for PHP4 compatibility, it will
     * call the constructor, when a new instance is created.
     *
     * @param string $srcenc source charset encoding, use NULL (default) to use
     *                       whatever the document specifies
     * @param string $mode   how this parser object should work, "event" for
     *                       startelement/endelement-type events, "func"
     *                       to have it call functions named after elements
     * @param string $tgtenc a valid target encoding
     */
    function XML_Parser($srcenc = null, $mode = 'event', $tgtenc = null)
    {
        XML_Parser::__construct($srcenc, $mode, $tgtenc);
    }
    // }}}
    // {{{ php5 constructor

    /**
     * PHP5 constructor
     *
     * @param string $srcenc source charset encoding, use NULL (default) to use
     *                       whatever the document specifies
     * @param string $mode   how this parser object should work, "event" for
     *                       startelement/endelement-type events, "func"
     *                       to have it call functions named after elements
     * @param string $tgtenc a valid target encoding
     */
    function __construct($srcenc = null, $mode = 'event', $tgtenc = null)
    {
        $this->PEAR('XML_Parser_Error');

        $this->mode   = $mode;
        $this->srcenc = $srcenc;
        $this->tgtenc = $tgtenc;
    }
    // }}}

    /**
     * Sets the mode of the parser.
     *
     * Possible modes are:
     * - func
     * - event
     *
     * You can set the mode using the second parameter
     * in the constructor.
     *
     * This method is only needed, when switching to a new
     * mode at a later point.
     *
     * @param string $mode mode, either 'func' or 'event'
     *
     * @return boolean|object  true on success, PEAR_Error otherwise
     * @access public
     */
    function setMode($mode)
    {
        if ($mode != 'func' && $mode != 'event') {
            $this->raiseError('Unsupported mode given', 
                XML_PARSER_ERROR_UNSUPPORTED_MODE);
        }

        $this->mode = $mode;
        return true;
    }

    /**
     * Sets the object, that will handle the XML events
     *
     * This allows you to create a handler object independent of the
     * parser object that you are using and easily switch the underlying
     * parser.
     *
     * If no object will be set, XML_Parser assumes that you
     * extend this class and handle the events in $this.
     *
     * @param object &$obj object to handle the events
     *
     * @return boolean will always return true
     * @access public
     * @since v1.2.0beta3
     */
    function setHandlerObj(&$obj)
    {
        $this->_handlerObj = &$obj;
        return true;
    }

    /**
     * Init the element handlers
     *
     * @return mixed
     * @access private
     */
    function _initHandlers()
    {
        if (!is_resource($this->parser)) {
            return false;
        }

        if (!is_object($this->_handlerObj)) {
            $this->_handlerObj = &$this;
        }
        switch ($this->mode) {

        case 'func':
            xml_set_object($this->parser, $this->_handlerObj);
            xml_set_element_handler($this->parser, 
                array(&$this, 'funcStartHandler'), array(&$this, 'funcEndHandler'));
            break;

        case 'event':
            xml_set_object($this->parser, $this->_handlerObj);
            xml_set_element_handler($this->parser, 'startHandler', 'endHandler');
            break;
        default:
            return $this->raiseError('Unsupported mode given', 
                XML_PARSER_ERROR_UNSUPPORTED_MODE);
            break;
        }

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

    // {{{ _create()

    /**
     * create the XML parser resource
     *
     * Has been moved from the constructor to avoid
     * problems with object references.
     *
     * Furthermore it allows us returning an error
     * if something fails.
     *
     * NOTE: uses '@' error suppresion in this method
     *
     * @return bool|PEAR_Error true on success, PEAR_Error otherwise
     * @access private
     * @see xml_parser_create
     */
    function _create()
    {
        if ($this->srcenc === null) {
            $xp = @xml_parser_create();
        } else {
            $xp = @xml_parser_create($this->srcenc);
        }
        if (is_resource($xp)) {
            if ($this->tgtenc !== null) {
                if (!@xml_parser_set_option($xp, XML_OPTION_TARGET_ENCODING, 
                    $this->tgtenc)
                ) {
                    return $this->raiseError('invalid target encoding', 
                        XML_PARSER_ERROR_INVALID_ENCODING);
                }
            }
            $this->parser = $xp;
            $result       = $this->_initHandlers($this->mode);
            if ($this->isError($result)) {
                return $result;
            }
            xml_parser_set_option($xp, XML_OPTION_CASE_FOLDING, $this->folding);
            return true;
        }
        if (!in_array(strtoupper($this->srcenc), $this->_validEncodings)) {
            return $this->raiseError('invalid source encoding', 
                XML_PARSER_ERROR_INVALID_ENCODING);
        }
        return $this->raiseError('Unable to create XML parser resource.', 
            XML_PARSER_ERROR_NO_RESOURCE);
    }

    // }}}
    // {{{ reset()

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
        $result = $this->_create();
        if ($this->isError($result)) {
            return $result;
        }
        return true;
    }

    // }}}
    // {{{ setInputFile()

    /**
     * Sets the input xml file to be parsed
     *
     * @param string $file Filename (full path)
     *
     * @return resource fopen handle of the given file
     * @access public
     * @throws XML_Parser_Error
     * @see setInput(), setInputString(), parse()
     */
    function setInputFile($file)
    {
        /**
         * check, if file is a remote file
         */
        if (preg_match('/^(http|ftp):\/\//i', substr($file, 0, 10))) {
            if (!ini_get('allow_url_fopen')) {
                return $this->
                raiseError('Remote files cannot be parsed, as safe mode is enabled.',
                XML_PARSER_ERROR_REMOTE);
            }
        }

        $fp = @fopen($file, 'rb');
        if (is_resource($fp)) {
            $this->fp = $fp;
            return $fp;
        }
        return $this->raiseError('File could not be opened.', 
            XML_PARSER_ERROR_FILE_NOT_READABLE);
    }

    // }}}
    // {{{ setInputString()

    /**
     * XML_Parser::setInputString()
     *
     * Sets the xml input from a string
     *
     * @param string $data a string containing the XML document
     *
     * @return null
     */
    function setInputString($data)
    {
        $this->fp = $data;
        return null;
    }

    // }}}
    // {{{ setInput()

    /**
     * Sets the file handle to use with parse().
     *
     * You should use setInputFile() or setInputString() if you
     * pass a string
     *
     * @param mixed $fp Can be either a resource returned from fopen(),
     *                  a URL, a local filename or a string.
     *
     * @return mixed
     * @access public
     * @see parse()
     * @uses setInputString(), setInputFile()
     */
    function setInput($fp)
    {
        if (is_resource($fp)) {
            $this->fp = $fp;
            return true;
        } elseif (preg_match('/^[a-z]+:\/\//i', substr($fp, 0, 10))) {
            // see if it's an absolute URL (has a scheme at the beginning)
            return $this->setInputFile($fp);
        } elseif (file_exists($fp)) {
            // see if it's a local file
            return $this->setInputFile($fp);
        } else {
            // it must be a string
            $this->fp = $fp;
            return true;
        }

        return $this->raiseError('Illegal input format', 
            XML_PARSER_ERROR_INVALID_INPUT);
    }

    // }}}
    // {{{ parse()

    /**
     * Central parsing function.
     *
     * @return bool|PEAR_Error returns true on success, or a PEAR_Error otherwise
     * @access public
     */
    function parse()
    {
        /**
         * reset the parser
         */
        $result = $this->reset();
        if ($this->isError($result)) {
            return $result;
        }
        // if $this->fp was fopened previously
        if (is_resource($this->fp)) {

            while ($data = fread($this->fp, 4096)) {
                if (!$this->_parseString($data, feof($this->fp))) {
                    $error = &$this->raiseError();
                    $this->free();
                    return $error;
                }
            }
        } else {
            // otherwise, $this->fp must be a string
            if (!$this->_parseString($this->fp, true)) {
                $error = &$this->raiseError();
                $this->free();
                return $error;
            }
        }
        $this->free();

        return true;
    }

    /**
     * XML_Parser::_parseString()
     *
     * @param string $data data
     * @param bool   $eof  end-of-file flag
     *
     * @return bool
     * @access private
     * @see parseString()
     **/
    function _parseString($data, $eof = false)
    {
        return xml_parse($this->parser, $data, $eof);
    }

    // }}}
    // {{{ parseString()

    /**
     * XML_Parser::parseString()
     *
     * Parses a string.
     *
     * @param string  $data XML data
     * @param boolean $eof  If set and TRUE, data is the last piece 
     *                      of data sent in this parser
     *
     * @return bool|PEAR_Error true on success or a PEAR Error
     * @throws XML_Parser_Error
     * @see _parseString()
     */
    function parseString($data, $eof = false)
    {
        if (!isset($this->parser) || !is_resource($this->parser)) {
            $this->reset();
        }

        if (!$this->_parseString($data, $eof)) {
            $error = &$this->raiseError();
            $this->free();
            return $error;
        }

        if ($eof === true) {
            $this->free();
        }
        return true;
    }

    /**
     * XML_Parser::free()
     *
     * Free the internal resources associated with the parser
     *
     * @return null
     **/
    function free()
    {
        if (isset($this->parser) && is_resource($this->parser)) {
            xml_parser_free($this->parser);
            unset( $this->parser );
        }
        if (isset($this->fp) && is_resource($this->fp)) {
            fclose($this->fp);
        }
        unset($this->fp);
        return null;
    }

    /**
     * XML_Parser::raiseError()
     *
     * Throws a XML_Parser_Error
     *
     * @param string  $msg   the error message
     * @param integer $ecode the error message code
     *
     * @return XML_Parser_Error reference to the error object
     **/
    function &raiseError($msg = null, $ecode = 0)
    {
        $msg = !is_null($msg) ? $msg : $this->parser;
        $err = &new XML_Parser_Error($msg, $ecode);
        return parent::raiseError($err);
    }

    // }}}
    // {{{ funcStartHandler()

    /**
     * derives and calls the Start Handler function
     *
     * @param mixed $xp      ??
     * @param mixed $elem    ??
     * @param mixed $attribs ??
     *
     * @return void
     */
    function funcStartHandler($xp, $elem, $attribs)
    {
        $func = 'xmltag_' . $elem;
        $func = str_replace(array('.', '-', ':'), '_', $func);
        if (method_exists($this->_handlerObj, $func)) {
            call_user_func(array(&$this->_handlerObj, $func), $xp, $elem, $attribs);
        } elseif (method_exists($this->_handlerObj, 'xmltag')) {
            call_user_func(array(&$this->_handlerObj, 'xmltag'), 
                $xp, $elem, $attribs);
        }
    }

    // }}}
    // {{{ funcEndHandler()

    /**
     * derives and calls the End Handler function
     *
     * @param mixed $xp   ??
     * @param mixed $elem ??
     *
     * @return void
     */
    function funcEndHandler($xp, $elem)
    {
        $func = 'xmltag_' . $elem . '_';
        $func = str_replace(array('.', '-', ':'), '_', $func);
        if (method_exists($this->_handlerObj, $func)) {
            call_user_func(array(&$this->_handlerObj, $func), $xp, $elem);
        } elseif (method_exists($this->_handlerObj, 'xmltag_')) {
            call_user_func(array(&$this->_handlerObj, 'xmltag_'), $xp, $elem);
        }
    }

    // }}}
    // {{{ startHandler()

    /**
     * abstract method signature for Start Handler
     *
     * @param mixed $xp       ??
     * @param mixed $elem     ??
     * @param mixed &$attribs ??
     *
     * @return null
     * @abstract
     */
    function startHandler($xp, $elem, &$attribs)
    {
        return null;
    }

    // }}}
    // {{{ endHandler()

    /**
     * abstract method signature for End Handler
     *
     * @param mixed $xp   ??
     * @param mixed $elem ??
     *
     * @return null
     * @abstract
     */
    function endHandler($xp, $elem)
    {
        return null;
    }


    // }}}me
}

/**
 * error class, replaces PEAR_Error
 *
 * An instance of this class will be returned
 * if an error occurs inside XML_Parser.
 *
 * There are three advantages over using the standard PEAR_Error:
 * - All messages will be prefixed
 * - check for XML_Parser error, using is_a( $error, 'XML_Parser_Error' )
 * - messages can be generated from the xml_parser resource
 *
 * @category  XML
 * @package   XML_Parser
 * @author    Stig Bakken <ssb@fast.no>
 * @author    Tomas V.V.Cox <cox@idecnet.com>
 * @author    Stephan Schmidt <schst@php.net>
 * @copyright 2002-2008 The PHP Group
 * @license   http://opensource.org/licenses/bsd-license New BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/XML_Parser
 * @see       PEAR_Error
 */
class XML_Parser_Error extends PEAR_Error
{
    // {{{ properties

    /**
    * prefix for all messages
    *
    * @var      string
    */
    var $error_message_prefix = 'XML_Parser: ';

    // }}}
    // {{{ constructor()
    /**
    * construct a new error instance
    *
    * You may either pass a message or an xml_parser resource as first
    * parameter. If a resource has been passed, the last error that
    * happened will be retrieved and returned.
    *
    * @param string|resource $msgorparser message or parser resource
    * @param integer         $code        error code
    * @param integer         $mode        error handling
    * @param integer         $level       error level
    *
    * @access   public
    * @todo PEAR CS - can't meet 85char line limit without arg refactoring
    */
    function XML_Parser_Error($msgorparser = 'unknown error', $code = 0, $mode = PEAR_ERROR_RETURN, $level = E_USER_NOTICE)
    {
        if (is_resource($msgorparser)) {
            $code        = xml_get_error_code($msgorparser);
            $msgorparser = sprintf('%s at XML input line %d:%d',
                xml_error_string($code),
                xml_get_current_line_number($msgorparser),
                xml_get_current_column_number($msgorparser));
        }
        $this->PEAR_Error($msgorparser, $code, $mode, $level);
    }
    // }}}
}
?>
