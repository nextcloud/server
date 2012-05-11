<?php
/**
 * PEAR_XMLParser
 *
 * PHP versions 4 and 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @author     Stephan Schmidt (original XML_Unserializer code)
 * @copyright  1997-2009 The Authors
 * @license   http://opensource.org/licenses/bsd-license New BSD License
 * @version    CVS: $Id: XMLParser.php 313023 2011-07-06 19:17:11Z dufuz $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 1.4.0a1
 */

/**
 * Parser for any xml file
 * @category  pear
 * @package   PEAR
 * @author    Greg Beaver <cellog@php.net>
 * @author    Stephan Schmidt (original XML_Unserializer code)
 * @copyright 1997-2009 The Authors
 * @license   http://opensource.org/licenses/bsd-license New BSD License
 * @version   Release: 1.9.4
 * @link      http://pear.php.net/package/PEAR
 * @since     Class available since Release 1.4.0a1
 */
class PEAR_XMLParser
{
    /**
     * unserilialized data
     * @var string $_serializedData
     */
    var $_unserializedData = null;

    /**
     * name of the root tag
     * @var string $_root
     */
    var $_root = null;

    /**
     * stack for all data that is found
     * @var array    $_dataStack
     */
    var $_dataStack = array();

    /**
     * stack for all values that are generated
     * @var array    $_valStack
     */
    var $_valStack = array();

    /**
     * current tag depth
     * @var int    $_depth
     */
    var $_depth = 0;

    /**
     * The XML encoding to use
     * @var string $encoding
     */
    var $encoding = 'ISO-8859-1';

    /**
     * @return array
     */
    function getData()
    {
        return $this->_unserializedData;
    }

    /**
     * @param string xml content
     * @return true|PEAR_Error
     */
    function parse($data)
    {
        if (!extension_loaded('xml')) {
            include_once 'PEAR.php';
            return PEAR::raiseError("XML Extension not found", 1);
        }
        $this->_dataStack =  $this->_valStack = array();
        $this->_depth = 0;

        if (
            strpos($data, 'encoding="UTF-8"')
            || strpos($data, 'encoding="utf-8"')
            || strpos($data, "encoding='UTF-8'")
            || strpos($data, "encoding='utf-8'")
        ) {
            $this->encoding = 'UTF-8';
        }

        if (version_compare(phpversion(), '5.0.0', 'lt') && $this->encoding == 'UTF-8') {
            $data = utf8_decode($data);
            $this->encoding = 'ISO-8859-1';
        }

        $xp = xml_parser_create($this->encoding);
        xml_parser_set_option($xp, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object($xp, $this);
        xml_set_element_handler($xp, 'startHandler', 'endHandler');
        xml_set_character_data_handler($xp, 'cdataHandler');
        if (!xml_parse($xp, $data)) {
            $msg = xml_error_string(xml_get_error_code($xp));
            $line = xml_get_current_line_number($xp);
            xml_parser_free($xp);
            include_once 'PEAR.php';
            return PEAR::raiseError("XML Error: '$msg' on line '$line'", 2);
        }
        xml_parser_free($xp);
        return true;
    }

    /**
     * Start element handler for XML parser
     *
     * @access private
     * @param  object $parser  XML parser object
     * @param  string $element XML element
     * @param  array  $attribs attributes of XML tag
     * @return void
     */
    function startHandler($parser, $element, $attribs)
    {
        $this->_depth++;
        $this->_dataStack[$this->_depth] = null;

        $val = array(
            'name'         => $element,
            'value'        => null,
            'type'         => 'string',
            'childrenKeys' => array(),
            'aggregKeys'   => array()
       );

        if (count($attribs) > 0) {
            $val['children'] = array();
            $val['type'] = 'array';
            $val['children']['attribs'] = $attribs;
        }

        array_push($this->_valStack, $val);
    }

    /**
     * post-process data
     *
     * @param string $data
     * @param string $element element name
     */
    function postProcess($data, $element)
    {
        return trim($data);
    }

    /**
     * End element handler for XML parser
     *
     * @access private
     * @param  object XML parser object
     * @param  string
     * @return void
     */
    function endHandler($parser, $element)
    {
        $value = array_pop($this->_valStack);
        $data  = $this->postProcess($this->_dataStack[$this->_depth], $element);

        // adjust type of the value
        switch (strtolower($value['type'])) {
            // unserialize an array
            case 'array':
                if ($data !== '') {
                    $value['children']['_content'] = $data;
                }

                $value['value'] = isset($value['children']) ? $value['children'] : array();
                break;

            /*
             * unserialize a null value
             */
            case 'null':
                $data = null;
                break;

            /*
             * unserialize any scalar value
             */
            default:
                settype($data, $value['type']);
                $value['value'] = $data;
                break;
        }

        $parent = array_pop($this->_valStack);
        if ($parent === null) {
            $this->_unserializedData = &$value['value'];
            $this->_root = &$value['name'];
            return true;
        }

        // parent has to be an array
        if (!isset($parent['children']) || !is_array($parent['children'])) {
            $parent['children'] = array();
            if ($parent['type'] != 'array') {
                $parent['type'] = 'array';
            }
        }

        if (!empty($value['name'])) {
            // there already has been a tag with this name
            if (in_array($value['name'], $parent['childrenKeys'])) {
                // no aggregate has been created for this tag
                if (!in_array($value['name'], $parent['aggregKeys'])) {
                    if (isset($parent['children'][$value['name']])) {
                        $parent['children'][$value['name']] = array($parent['children'][$value['name']]);
                    } else {
                        $parent['children'][$value['name']] = array();
                    }
                    array_push($parent['aggregKeys'], $value['name']);
                }
                array_push($parent['children'][$value['name']], $value['value']);
            } else {
                $parent['children'][$value['name']] = &$value['value'];
                array_push($parent['childrenKeys'], $value['name']);
            }
        } else {
            array_push($parent['children'],$value['value']);
        }
        array_push($this->_valStack, $parent);

        $this->_depth--;
    }

    /**
     * Handler for character data
     *
     * @access private
     * @param  object XML parser object
     * @param  string CDATA
     * @return void
     */
    function cdataHandler($parser, $cdata)
    {
        $this->_dataStack[$this->_depth] .= $cdata;
    }
}