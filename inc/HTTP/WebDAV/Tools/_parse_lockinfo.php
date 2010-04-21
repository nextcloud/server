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
 * helper class for parsing LOCK request bodies
 * 
 * @package HTTP_WebDAV_Server
 * @author Hartmut Holzgraefe <hholzgra@php.net>
 * @version @package-version@
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
            $ns  = "";
            $tag = $name;
        }
        
  
        if ($this->collect_owner) {
            // everything within the <owner> tag needs to be collected
            $ns_short = "";
            $ns_attr  = "";
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
            $ns  = "";
            $tag = $name;
        }

        // <owner> finished?
        if (($ns == "DAV:") && ($tag == "owner")) {
            $this->collect_owner = false;
        }

        // within <owner> we have to collect everything
        if ($this->collect_owner) {
            $ns_short = "";
            $ns_attr  = "";
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
