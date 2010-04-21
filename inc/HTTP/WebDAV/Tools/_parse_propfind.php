<?php // $Id$
/*
   +----------------------------------------------------------------------+
   | Copyright (c) 2002-2007 Christian Stocker, Hartmut Holzgraefe        |
   | All rights reserved                                                  |
   |                                                                      |
   | Redistribution and use in source and binary forms, with or without   |
   | modification, are permitted provided that the following conditions   |
   | are met:                                                             |
   |                                                                      |
   | 1. Redistributions of source code must retain the above copyright    |
   |    notice, this list of conditions and the following disclaimer.     |
   | 2. Redistributions in binary form must reproduce the above copyright |
   |    notice, this list of conditions and the following disclaimer in   |
   |    the documentation and/or other materials provided with the        |
   |    distribution.                                                     |
   | 3. The names of the authors may not be used to endorse or promote    |
   |    products derived from this software without specific prior        |
   |    written permission.                                               |
   |                                                                      |
   | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
   | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
   | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
   | FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE       |
   | COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,  |
   | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
   | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;     |
   | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER     |
   | CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT   |
   | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN    |
   | ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE      |
   | POSSIBILITY OF SUCH DAMAGE.                                          |
   +----------------------------------------------------------------------+
*/

/**
 * helper class for parsing PROPFIND request bodies
 * 
 * @package HTTP_WebDAV_Server
 * @author Hartmut Holzgraefe <hholzgra@php.net>
 * @version @package-version@
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
        while ($this->success && !feof($f_in)) {
            $line = fgets($f_in);
            if (is_string($line)) {
                $had_input = true;
                $this->success &= xml_parse($xml_parser, $line, false);
            }
        } 
        
        // finish parsing
        if ($had_input) {
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
            $ns  = "";
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
