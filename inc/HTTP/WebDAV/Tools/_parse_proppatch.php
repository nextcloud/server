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
 * helper class for parsing PROPPATCH request bodies
 * 
 * @package HTTP_WebDAV_Server
 * @author Hartmut Holzgraefe <hholzgra@php.net>
 * @version @package-version@
 */
class _parse_proppatch 
{
    /**
     *
     * 
     * @var
     * @access
     */
    var $success;

    /**
     *
     * 
     * @var
     * @access
     */
    var $props;

    /**
     *
     * 
     * @var
     * @access
     */
    var $depth;

    /**
     *
     * 
     * @var
     * @access
     */
    var $mode;

    /**
     *
     * 
     * @var
     * @access
     */
    var $current;

    /**
     * constructor
     * 
     * @param  string  path of input stream 
     * @access public
     */
    function _parse_proppatch($path) 
    {
        $this->success = true;

        $this->depth = 0;
        $this->props = array();
        $had_input = false;

        $f_in = fopen($path, "r");
        if (!$f_in) {
            $this->success = false;
            return;
        }

        $xml_parser = xml_parser_create_ns("UTF-8", " ");

        xml_set_element_handler($xml_parser,
                                array(&$this, "_startElement"),
                                array(&$this, "_endElement"));

        xml_set_character_data_handler($xml_parser,
                                       array(&$this, "_data"));

        xml_parser_set_option($xml_parser,
                              XML_OPTION_CASE_FOLDING, false);

        while($this->success && !feof($f_in)) {
            $line = fgets($f_in);
            if (is_string($line)) {
                $had_input = true;
                $this->success &= xml_parse($xml_parser, $line, false);
            }
        } 
        
        if($had_input) {
            $this->success &= xml_parse($xml_parser, "", true);
        }

        xml_parser_free($xml_parser);

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
        if (strstr($name, " ")) {
            list($ns, $tag) = explode(" ", $name);
            if ($ns == "")
                $this->success = false;
        } else {
            $ns = "";
            $tag = $name;
        }

        if ($this->depth == 1) {
            $this->mode = $tag;
        } 

        if ($this->depth == 3) {
            $prop = array("name" => $tag);
            $this->current = array("name" => $tag, "ns" => $ns, "status"=> 200);
            if ($this->mode == "set") {
                $this->current["val"] = "";     // default set val
            }
        }

        if ($this->depth >= 4) {
            $this->current["val"] .= "<$tag";
            if (isset($attr)) {
                foreach ($attr as $key => $val) {
                    $this->current["val"] .= ' '.$key.'="'.str_replace('"','&quot;', $val).'"';
                }
            }
            $this->current["val"] .= ">";
        }

        

        $this->depth++;
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
        if (strstr($name, " ")) {
            list($ns, $tag) = explode(" ", $name);
            if ($ns == "")
                $this->success = false;
        } else {
            $ns = "";
            $tag = $name;
        }

        $this->depth--;

        if ($this->depth >= 4) {
            $this->current["val"] .= "</$tag>";
        }

        if ($this->depth == 3) {
            if (isset($this->current)) {
                $this->props[] = $this->current;
                unset($this->current);
            }
        }
    }

    /**
     * input data handler
     *
     * @param  resource  parser
     * @param  string    data
     * @return void
     * @access private
     */
    function _data($parser, $data) 
    {
        if (isset($this->current)) {
            $this->current["val"] .= $data;
        }
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */
