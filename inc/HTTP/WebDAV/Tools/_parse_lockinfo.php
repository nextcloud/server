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
// $Id: _parse_lockinfo.php,v 1.2 2004/01/05 12:32:40 hholzgra Exp $
//

/**
 * helper class for parsing LOCK request bodies
 * 
 * @package HTTP_WebDAV_Server
 * @author Hartmut Holzgraefe <hholzgra@php.net>
 * @version 0.99.1dev
 */
class _parse_lockinfo 
{
	/**
	 * success state flag
	 *
	 * @var bool
	 * @access public
	 */
	var $success = false;

	/**
	 * lock type, currently only "write"
	 *
	 * @var string
	 * @access public
	 */
	var $locktype = "";

	/**
	 * lock scope, "shared" or "exclusive"
	 *
	 * @var string
	 * @access public
	 */
	var $lockscope = "";

	/**
	 * lock owner information
	 *
	 * @var string
	 * @access public
	 */
	var $owner = "";

	/**
	 * flag that is set during lock owner read
	 *
	 * @var bool
	 * @access private
	 */
	var $collect_owner = false;
	
	/**
	 * constructor
	 *
	 * @param  string  path of stream to read
	 * @access public
	 */
    function _parse_lockinfo($path) 
	{
		// we assume success unless problems occur
		$this->success = true;

		// remember if any input was parsed
		$had_input = false;
		
		// open stream
		$f_in = fopen($path, "r");
		if (!$f_in) {
			$this->success = false;
			return;
		}

		// create namespace aware parser
		$xml_parser = xml_parser_create_ns("UTF-8", " ");

		// set tag and data handlers
		xml_set_element_handler($xml_parser,
								array(&$this, "_startElement"),
								array(&$this, "_endElement"));
		xml_set_character_data_handler($xml_parser,
									   array(&$this, "_data"));

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

		// check if required tags where found
		$this->success &= !empty($this->locktype);
		$this->success &= !empty($this->lockscope);

		// free parser resource
		xml_parser_free($xml_parser);

		// close input stream
		fclose($f_in);		
	}
    

	/**
	 * tag start handler
	 *
	 * @param  resource  parser
	 * @param  string    tag name
	 * @param  array     tag attributes
	 * @return void
	 * @access private
	 */
    function _startElement($parser, $name, $attrs) 
    {
		// namespace handling
        if (strstr($name, " ")) {
            list($ns, $tag) = explode(" ", $name);
        } else {
            $ns = "";
            $tag = $name;
        }
		
  
        if ($this->collect_owner) {
			// everything within the <owner> tag needs to be collected
            $ns_short = "";
            $ns_attr = "";
            if ($ns) {
                if ($ns == "DAV:") {
                    $ns_short = "D:";
                } else {
                    $ns_attr = " xmlns='$ns'";
                }
            }
            $this->owner .= "<$ns_short$tag$ns_attr>";
        } else if ($ns == "DAV:") {
			// parse only the essential tags
            switch ($tag) {
			case "write":
				$this->locktype = $tag;
				break;
			case "exclusive":
			case "shared":
				$this->lockscope = $tag;
				break;
			case "owner":
				$this->collect_owner = true;
				break;
            }
        }
    }
	
	/**
	 * data handler
	 *
	 * @param  resource  parser
	 * @param  string    data
	 * @return void
	 * @access private
	 */
    function _data($parser, $data) 
    {
		// only the <owner> tag has data content
        if ($this->collect_owner) {
            $this->owner .= $data;
        }
    }

	/**
	 * tag end handler
	 *
	 * @param  resource  parser
	 * @param  string    tag name
	 * @return void
	 * @access private
	 */
    function _endElement($parser, $name) 
    {
		// namespace handling
		if (strstr($name, " ")) {
			list($ns, $tag) = explode(" ", $name);
		} else {
			$ns = "";
			$tag = $name;
		}

		// <owner> finished?
		if (($ns == "DAV:") && ($tag == "owner")) {
			$this->collect_owner = false;
		}

		// within <owner> we have to collect everything
		if ($this->collect_owner) {
			$ns_short = "";
			$ns_attr = "";
			if ($ns) {
				if ($ns == "DAV:") {
                    $ns_short = "D:";
				} else {
					$ns_attr = " xmlns='$ns'";
				}
			}
			$this->owner .= "</$ns_short$tag$ns_attr>";
		}
    }
}

?>