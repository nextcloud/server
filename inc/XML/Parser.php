<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Stig Bakken <ssb@fast.no>                                    |
// |         Tomas V.V.Cox <cox@idecnet.com>                              |
// |         Stephan Schmidt <schst@php-tools.net>                        |
// +----------------------------------------------------------------------+
//
// $Id: Parser.php,v 1.25 2005/03/25 17:13:10 schst Exp $

/**
 * XML Parser class.
 *
 * This is an XML parser based on PHP's "xml" extension,
 * based on the bundled expat library.
 *
 * @category XML
 * @package XML_Parser
 * @author  Stig Bakken <ssb@fast.no>
 * @author  Tomas V.V.Cox <cox@idecnet.com>
 * @author  Stephan Schmidt <schst@php-tools.net>
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
 *
 * @category XML
 * @package XML_Parser
 * @author  Stig Bakken <ssb@fast.no>
 * @author  Tomas V.V.Cox <cox@idecnet.com>
 * @author  Stephan Schmidt <schst@php-tools.net>
 * @todo    create XML_Parser_Namespace to parse documents with namespaces
 * @todo    create XML_Parser_Pull
 * @todo    Tests that need to be made:
 *          - mixing character encodings
 *          - a test using all expat handlers
 *          - options (folding, output charset)
 *          - different parsing modes
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

    // }}}
    // {{{ constructor

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
     * @param string $tgenc  a valid target encoding
     */
    function XML_Parser($srcenc = null, $mode = 'event', $tgtenc = null)
    {
        XML_Parser::__construct($srcenc, $mode, $tgtenc);
    }
    // }}}

    /**
     * PHP5 constructor
     *
     * @param string $srcenc source charset encoding, use NULL (default) to use
     *                       whatever the document specifies
     * @param string $mode   how this parser object should work, "event" for
     *                       startelement/endelement-type events, "func"
     *                       to have it call functions named after elements
     * @param string $tgenc  a valid target encoding
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
     * @access  public
     * @param   string          mode, either 'func' or 'event'
     * @return  boolean|object  true on success, PEAR_Error otherwise   
     */
    function setMode($mode)
    {
        if ($mode != 'func' && $mode != 'event') {
            $this->raiseError('Unsupported mode given', XML_PARSER_ERROR_UNSUPPORTED_MODE);
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
     * @access  public
     * @param   object      object to handle the events
     * @return  boolean     will always return true
     * @since   v1.2.0beta3
     */
    function setHandlerObj(&$obj)
    {
        $this->_handlerObj = &$obj;
        return true;
    }

    /**
     * Init the element handlers
     *
     * @access  private
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
                xml_set_element_handler($this->parser, array(&$this, 'funcStartHandler'), array(&$this, 'funcEndHandler'));
                break;

            case 'event':
                xml_set_object($this->parser, $this->_handlerObj);
                xml_set_element_handler($this->parser, 'startHandler', 'endHandler');
                break;
            default:
                return $this->raiseError('Unsupported mode given', XML_PARSER_ERROR_UNSUPPORTED_MODE);
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
     * @access   private
     * @return   boolean|object     true on success, PEAR_Error otherwise
     *
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
                                            $this->tgtenc)) {
                    return $this->raiseError('invalid target encoding', XML_PARSER_ERROR_INVALID_ENCODING);
                }
            }
            $this->parser = $xp;
            $result = $this->_initHandlers($this->mode);
            if ($this->isError($result)) {
                return $result;
            }
            xml_parser_set_option($xp, XML_OPTION_CASE_FOLDING, $this->folding);

            return true;
        }
        return $this->raiseError('Unable to create XML parser resource.', XML_PARSER_ERROR_NO_RESOURCE);
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
        if ($this->isError( $result )) {
            return $result;
        }
        return true;
    }

    // }}}
    // {{{ setInputFile()

    /**
     * Sets the input xml file to be parsed
     *
     * @param    string      Filename (full path)
     * @return   resource    fopen handle of the given file
     * @throws   XML_Parser_Error
     * @see      setInput(), setInputString(), parse()
     * @access   public
     */
    function setInputFile($file)
    {
        /**
         * check, if file is a remote file
         */
        if (eregi('^(http|ftp)://', substr($file, 0, 10))) {
            if (!ini_get('allow_url_fopen')) {
            	return $this->raiseError('Remote files cannot be parsed, as safe mode is enabled.', XML_PARSER_ERROR_REMOTE);
            }
        }
        
        $fp = @fopen($file, 'rb');
        if (is_resource($fp)) {
            $this->fp = $fp;
            return $fp;
        }
        return $this->raiseError('File could not be opened.', XML_PARSER_ERROR_FILE_NOT_READABLE);
    }

    // }}}
    // {{{ setInputString()
    
    /**
     * XML_Parser::setInputString()
     * 
     * Sets the xml input from a string
     * 
     * @param string $data a string containing the XML document
     * @return null
     **/
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
     * @param    mixed  $fp  Can be either a resource returned from fopen(),
     *                       a URL, a local filename or a string.
     * @access   public
     * @see      parse()
     * @uses     setInputString(), setInputFile()
     */
    function setInput($fp)
    {
        if (is_resource($fp)) {
            $this->fp = $fp;
            return true;
        }
        // see if it's an absolute URL (has a scheme at the beginning)
        elseif (eregi('^[a-z]+://', substr($fp, 0, 10))) {
            return $this->setInputFile($fp);
        }
        // see if it's a local file
        elseif (file_exists($fp)) {
            return $this->setInputFile($fp);
        }
        // it must be a string
        else {
            $this->fp = $fp;
            return true;
        }

        return $this->raiseError('Illegal input format', XML_PARSER_ERROR_INVALID_INPUT);
    }

    // }}}
    // {{{ parse()

    /**
     * Central parsing function.
     *
     * @return   true|object PEAR error     returns true on success, or a PEAR_Error otherwise
     * @access   public
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
        // otherwise, $this->fp must be a string
        } else {
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
     * @param string $data
     * @param boolean $eof
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
     * @param    string  $data XML data
     * @param    boolean $eof  If set and TRUE, data is the last piece of data sent in this parser
     * @throws   XML_Parser_Error
     * @return   Pear Error|true   true on success or a PEAR Error
     * @see      _parseString()
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
     * @return XML_Parser_Error 
     **/
    function raiseError($msg = null, $ecode = 0)
    {
        $msg = !is_null($msg) ? $msg : $this->parser;
        $err = &new XML_Parser_Error($msg, $ecode);
        return parent::raiseError($err);
    }
    
    // }}}
    // {{{ funcStartHandler()

    function funcStartHandler($xp, $elem, $attribs)
    {
        $func = 'xmltag_' . $elem;
        if (strchr($func, '.')) {
            $func = str_replace('.', '_', $func);
        }
        if (method_exists($this->_handlerObj, $func)) {
            call_user_func(array(&$this->_handlerObj, $func), $xp, $elem, $attribs);
        } elseif (method_exists($this->_handlerObj, 'xmltag')) {
            call_user_func(array(&$this->_handlerObj, 'xmltag'), $xp, $elem, $attribs);
        }
    }

    // }}}
    // {{{ funcEndHandler()

    function funcEndHandler($xp, $elem)
    {
        $func = 'xmltag_' . $elem . '_';
        if (strchr($func, '.')) {
            $func = str_replace('.', '_', $func);
        }
        if (method_exists($this->_handlerObj, $func)) {
            call_user_func(array(&$this->_handlerObj, $func), $xp, $elem);
        } elseif (method_exists($this->_handlerObj, 'xmltag_')) {
            call_user_func(array(&$this->_handlerObj, 'xmltag_'), $xp, $elem);
        }
    }

    // }}}
    // {{{ startHandler()

    /**
     *
     * @abstract
     */
    function startHandler($xp, $elem, &$attribs)
    {
        return NULL;
    }

    // }}}
    // {{{ endHandler()

    /**
     *
     * @abstract
     */
    function endHandler($xp, $elem)
    {
        return NULL;
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
 * @package XML_Parser
 * @access  public
 * @see     PEAR_Error
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
    * @access   public
    * @param    string|resource     message or parser resource
    * @param    integer             error code
    * @param    integer             error handling
    * @param    integer             error level
    */    
    function XML_Parser_Error($msgorparser = 'unknown error', $code = 0, $mode = PEAR_ERROR_RETURN, $level = E_USER_NOTICE)
    {
        if (is_resource($msgorparser)) {
            $code = xml_get_error_code($msgorparser);
            $msgorparser = sprintf('%s at XML input line %d',
                                   xml_error_string($code),
                                   xml_get_current_line_number($msgorparser));
        }
        $this->PEAR_Error($msgorparser, $code, $mode, $level);
    }
    // }}}
}
?>