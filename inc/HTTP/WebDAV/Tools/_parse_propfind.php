<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Hartmut Holzgraefe <hholzgra@php.net>                       |
// |          Christian Stocker <chregu@bitflux.ch>                       |
// +----------------------------------------------------------------------+
//
// $Id: _parse_propfind.php,v 1.2 2004/01/05 12:33:22 hholzgra Exp $
//

/**
 * helper class for parsing PROPFIND request bodies
 * 
 * @package HTTP_WebDAV_Server
 * @author Hartmut Holzgraefe <hholzgra@php.net>
 * @version 0.99.1dev
 */
class _parse_propfind 
{
	/**
	 * success state flag
	 *
	 * @var bool
	 * @access public
	 */
	var $success = false;

	/**
	 * found properties are collected here
	 *
	 * @var array
	 * @access public
	 */
	var $props = false;

	/**
	 * internal tag nesting depth counter
	 *
	 * @var int
	 * @access private
	 */
	var $depth = 0;

	
	/**
	 * constructor
	 *
	 * @access public
	 */
	function _parse_propfind($path) 
	{
		// success state flag
		$this->success = true;
		
		// property storage array
		$this->props = array();

		// internal tag depth counter
		$this->depth = 0;

		// remember if any input was parsed
		$had_input = false;

		// open input stream
		$f_in = fopen($path, "r");
		if (!$f_in) {
			$this->success = false;
			return;
		}

		// create XML parser
		$xml_parser = xml_parser_create_ns("UTF-8", " ");

		// set tag and data handlers
		xml_set_element_handler($xml_parser,
								array(&$this, "_startElement"),
								array(&$this, "_endElement"));

		// we want a case sensitive parser
		xml_parser_set_option($xml_parser, 
							  XML_OPTION_CASE_FOLDING, false);


		// parse input
		while($this->success && !feof($f_in)) {
			$line = fgets($f_in);
			if (is_string($line)) {
				$had_input = true;
				$this->success &= xml_parse($xml_parser, $line, false);
			}
		} 
		
		// finish parsing
		if($had_input) {
			$this->success &= xml_parse($xml_parser, "", true);
		}

		// free parser
		xml_parser_free($xml_parser);
		
		// close input stream
		fclose($f_in);

		// if no input was parsed it was a request
		if(!count($this->props)) $this->props = "all"; // default
	}
	

	/**
	 * start tag handler
	 * 
	 * @access private
	 * @param  resource  parser
	 * @param  string    tag name
	 * @param  array     tag attributes
	 */
	function _startElement($parser, $name, $attrs) 
	{
		// name space handling
		if (strstr($name, " ")) {
			list($ns, $tag) = explode(" ", $name);
			if ($ns == "")
				$this->success = false;
		} else {
			$ns = "";
			$tag = $name;
		}

		// special tags at level 1: <allprop> and <propname>
		if ($this->depth == 1) {
			if ($tag == "allprop")
				$this->props = "all";

			if ($tag == "propname")
				$this->props = "names";
		}

		// requested properties are found at level 2
		if ($this->depth == 2) {
			$prop = array("name" => $tag);
			if ($ns)
				$prop["xmlns"] = $ns;
			$this->props[] = $prop;
		}

		// increment depth count
		$this->depth++;
	}
	

	/**
	 * end tag handler
	 * 
	 * @access private
	 * @param  resource  parser
	 * @param  string    tag name
	 */
	function _endElement($parser, $name) 
	{
		// here we only need to decrement the depth count
		$this->depth--;
	}
}


?>