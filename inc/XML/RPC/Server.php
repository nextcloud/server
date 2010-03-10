<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Server commands for our PHP implementation of the XML-RPC protocol
 *
 * This is a PEAR-ified version of Useful inc's XML-RPC for PHP.
 * It has support for HTTP transport, proxies and authentication.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: License is granted to use or modify this software
 * ("XML-RPC for PHP") for commercial or non-commercial use provided the
 * copyright of the author is preserved in any distributed or derivative work.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESSED OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Web Services
 * @package    XML_RPC
 * @author     Edd Dumbill <edd@usefulinc.com>
 * @author     Stig Bakken <stig@php.net>
 * @author     Martin Jansen <mj@php.net>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1999-2001 Edd Dumbill, 2001-2005 The PHP Group
 * @version    CVS: $Id: Server.php,v 1.29 2005/08/14 20:25:35 danielc Exp $
 * @link       http://pear.php.net/package/XML_RPC
 */


/**
 * Pull in the XML_RPC class
 */
require_once 'XML/RPC.php';


/**
 * signature for system.listMethods: return = array,
 * parameters = a string or nothing
 * @global array $GLOBALS['XML_RPC_Server_listMethods_sig']
 */
$GLOBALS['XML_RPC_Server_listMethods_sig'] = array(
    array($GLOBALS['XML_RPC_Array'],
          $GLOBALS['XML_RPC_String']
    ),
    array($GLOBALS['XML_RPC_Array'])
);

/**
 * docstring for system.listMethods
 * @global string $GLOBALS['XML_RPC_Server_listMethods_doc']
 */
$GLOBALS['XML_RPC_Server_listMethods_doc'] = 'This method lists all the'
        . ' methods that the XML-RPC server knows how to dispatch';

/**
 * signature for system.methodSignature: return = array,
 * parameters = string
 * @global array $GLOBALS['XML_RPC_Server_methodSignature_sig']
 */
$GLOBALS['XML_RPC_Server_methodSignature_sig'] = array(
    array($GLOBALS['XML_RPC_Array'],
          $GLOBALS['XML_RPC_String']
    )
);

/**
 * docstring for system.methodSignature
 * @global string $GLOBALS['XML_RPC_Server_methodSignature_doc']
 */
$GLOBALS['XML_RPC_Server_methodSignature_doc'] = 'Returns an array of known'
        . ' signatures (an array of arrays) for the method name passed. If'
        . ' no signatures are known, returns a none-array (test for type !='
        . ' array to detect missing signature)';

/**
 * signature for system.methodHelp: return = string,
 * parameters = string
 * @global array $GLOBALS['XML_RPC_Server_methodHelp_sig']
 */
$GLOBALS['XML_RPC_Server_methodHelp_sig'] = array(
    array($GLOBALS['XML_RPC_String'],
          $GLOBALS['XML_RPC_String']
    )
);

/**
 * docstring for methodHelp
 * @global string $GLOBALS['XML_RPC_Server_methodHelp_doc']
 */
$GLOBALS['XML_RPC_Server_methodHelp_doc'] = 'Returns help text if defined'
        . ' for the method passed, otherwise returns an empty string';

/**
 * dispatch map for the automatically declared XML-RPC methods.
 * @global array $GLOBALS['XML_RPC_Server_dmap']
 */
$GLOBALS['XML_RPC_Server_dmap'] = array(
    'system.listMethods' => array(
        'function'  => 'XML_RPC_Server_listMethods',
        'signature' => $GLOBALS['XML_RPC_Server_listMethods_sig'],
        'docstring' => $GLOBALS['XML_RPC_Server_listMethods_doc']
    ),
    'system.methodHelp' => array(
        'function'  => 'XML_RPC_Server_methodHelp',
        'signature' => $GLOBALS['XML_RPC_Server_methodHelp_sig'],
        'docstring' => $GLOBALS['XML_RPC_Server_methodHelp_doc']
    ),
    'system.methodSignature' => array(
        'function'  => 'XML_RPC_Server_methodSignature',
        'signature' => $GLOBALS['XML_RPC_Server_methodSignature_sig'],
        'docstring' => $GLOBALS['XML_RPC_Server_methodSignature_doc']
    )
);

/**
 * @global string $GLOBALS['XML_RPC_Server_debuginfo']
 */
$GLOBALS['XML_RPC_Server_debuginfo'] = '';


/**
 * Lists all the methods that the XML-RPC server knows how to dispatch
 *
 * @return object  a new XML_RPC_Response object
 */
function XML_RPC_Server_listMethods($server, $m)
{
    global $XML_RPC_err, $XML_RPC_str, $XML_RPC_Server_dmap;

    $v = new XML_RPC_Value();
    $outAr = array();
    foreach ($server->dmap as $key => $val) {
        $outAr[] = new XML_RPC_Value($key, 'string');
    }
    foreach ($XML_RPC_Server_dmap as $key => $val) {
        $outAr[] = new XML_RPC_Value($key, 'string');
    }
    $v->addArray($outAr);
    return new XML_RPC_Response($v);
}

/**
 * Returns an array of known signatures (an array of arrays)
 * for the given method
 *
 * If no signatures are known, returns a none-array
 * (test for type != array to detect missing signature)
 *
 * @return object  a new XML_RPC_Response object
 */
function XML_RPC_Server_methodSignature($server, $m)
{
    global $XML_RPC_err, $XML_RPC_str, $XML_RPC_Server_dmap;

    $methName = $m->getParam(0);
    $methName = $methName->scalarval();
    if (strpos($methName, 'system.') === 0) {
        $dmap = $XML_RPC_Server_dmap;
        $sysCall = 1;
    } else {
        $dmap = $server->dmap;
        $sysCall = 0;
    }
    //  print "<!-- ${methName} -->\n";
    if (isset($dmap[$methName])) {
        if ($dmap[$methName]['signature']) {
            $sigs = array();
            $thesigs = $dmap[$methName]['signature'];
            for ($i = 0; $i < sizeof($thesigs); $i++) {
                $cursig = array();
                $inSig = $thesigs[$i];
                for ($j = 0; $j < sizeof($inSig); $j++) {
                    $cursig[] = new XML_RPC_Value($inSig[$j], 'string');
                }
                $sigs[] = new XML_RPC_Value($cursig, 'array');
            }
            $r = new XML_RPC_Response(new XML_RPC_Value($sigs, 'array'));
        } else {
            $r = new XML_RPC_Response(new XML_RPC_Value('undef', 'string'));
        }
    } else {
        $r = new XML_RPC_Response(0, $XML_RPC_err['introspect_unknown'],
                                  $XML_RPC_str['introspect_unknown']);
    }
    return $r;
}

/**
 * Returns help text if defined for the method passed, otherwise returns
 * an empty string
 *
 * @return object  a new XML_RPC_Response object
 */
function XML_RPC_Server_methodHelp($server, $m)
{
    global $XML_RPC_err, $XML_RPC_str, $XML_RPC_Server_dmap;

    $methName = $m->getParam(0);
    $methName = $methName->scalarval();
    if (strpos($methName, 'system.') === 0) {
        $dmap = $XML_RPC_Server_dmap;
        $sysCall = 1;
    } else {
        $dmap = $server->dmap;
        $sysCall = 0;
    }

    if (isset($dmap[$methName])) {
        if ($dmap[$methName]['docstring']) {
            $r = new XML_RPC_Response(new XML_RPC_Value($dmap[$methName]['docstring']),
                                                        'string');
        } else {
            $r = new XML_RPC_Response(new XML_RPC_Value('', 'string'));
        }
    } else {
        $r = new XML_RPC_Response(0, $XML_RPC_err['introspect_unknown'],
                                     $XML_RPC_str['introspect_unknown']);
    }
    return $r;
}

/**
 * @return void
 */
function XML_RPC_Server_debugmsg($m)
{
    global $XML_RPC_Server_debuginfo;
    $XML_RPC_Server_debuginfo = $XML_RPC_Server_debuginfo . $m . "\n";
}


/**
 * A server for receiving and replying to XML RPC requests
 *
 * <code>
 * $server = new XML_RPC_Server(
 *     array(
 *         'isan8' =>
 *             array(
 *                 'function' => 'is_8',
 *                 'signature' =>
 *                      array(
 *                          array('boolean', 'int'),
 *                          array('boolean', 'int', 'boolean'),
 *                          array('boolean', 'string'),
 *                          array('boolean', 'string', 'boolean'),
 *                      ),
 *                 'docstring' => 'Is the value an 8?'
 *             ),
 *     ),
 *     1,
 *     0
 * ); 
 * </code>
 *
 * @category   Web Services
 * @package    XML_RPC
 * @author     Edd Dumbill <edd@usefulinc.com>
 * @author     Stig Bakken <stig@php.net>
 * @author     Martin Jansen <mj@php.net>
 * @author     Daniel Convissor <danielc@php.net>
 * @copyright  1999-2001 Edd Dumbill, 2001-2005 The PHP Group
 * @version    Release: 1.4.0
 * @link       http://pear.php.net/package/XML_RPC
 */
class XML_RPC_Server
{
    /**
     * The dispatch map, listing the methods this server provides.
     * @var array
     */
    var $dmap = array();

    /**
     * The present response's encoding
     * @var string
     * @see XML_RPC_Message::getEncoding()
     */
    var $encoding = '';

    /**
     * Debug mode (0 = off, 1 = on)
     * @var integer
     */
    var $debug = 0;

    /**
     * The response's HTTP headers
     * @var string
     */
    var $server_headers = '';

    /**
     * The response's XML payload
     * @var string
     */
    var $server_payload = '';


    /**
     * Constructor for the XML_RPC_Server class
     *
     * @param array $dispMap   the dispatch map. An associative array
     *                          explaining each function. The keys of the main
     *                          array are the procedure names used by the
     *                          clients. The value is another associative array
     *                          that contains up to three elements:
     *                            + The 'function' element's value is the name
     *                              of the function or method that gets called.
     *                              To define a class' method: 'class::method'.
     *                            + The 'signature' element (optional) is an
     *                              array describing the return values and
     *                              parameters
     *                            + The 'docstring' element (optional) is a
     *                              string describing what the method does
     * @param int $serviceNow  should the HTTP response be sent now?
     *                          (1 = yes, 0 = no)
     * @param int $debug       should debug output be displayed?
     *                          (1 = yes, 0 = no)
     *
     * @return void
     */
    function XML_RPC_Server($dispMap, $serviceNow = 1, $debug = 0)
    {
        global $HTTP_RAW_POST_DATA;

        if ($debug) {
            $this->debug = 1;
        } else {
            $this->debug = 0;
        }

        $this->dmap = $dispMap;

        if ($serviceNow) {
            $this->service();
        } else {
            $this->createServerPayload();
            $this->createServerHeaders();
        }
    }

    /**
     * @return string  the debug information if debug debug mode is on
     */
    function serializeDebug()
    {
        global $XML_RPC_Server_debuginfo, $HTTP_RAW_POST_DATA;

        if ($this->debug) {
            XML_RPC_Server_debugmsg('vvv POST DATA RECEIVED BY SERVER vvv' . "\n"
                                    . $HTTP_RAW_POST_DATA
                                    . "\n" . '^^^ END POST DATA ^^^');
        }

        if ($XML_RPC_Server_debuginfo != '') {
            return "<!-- PEAR XML_RPC SERVER DEBUG INFO:\n\n"
                   . preg_replace('/-(?=-)/', '- ', $XML_RPC_Server_debuginfo)
                   . "-->\n";
        } else {
            return '';
        }
    }

    /**
     * Sends the response
     *
     * The encoding and content-type are determined by
     * XML_RPC_Message::getEncoding()
     *
     * @return void
     *
     * @uses XML_RPC_Server::createServerPayload(),
     *       XML_RPC_Server::createServerHeaders()
     */
    function service()
    {
        if (!$this->server_payload) {
            $this->createServerPayload();
        }
        if (!$this->server_headers) {
            $this->createServerHeaders();
        }
        header($this->server_headers);
        print $this->server_payload;
    }

    /**
     * Generates the payload and puts it in the $server_payload property
     *
     * @return void
     *
     * @uses XML_RPC_Server::parseRequest(), XML_RPC_Server::$encoding,
     *       XML_RPC_Response::serialize(), XML_RPC_Server::serializeDebug()
     */
    function createServerPayload()
    {
        $r = $this->parseRequest();
        $this->server_payload = '<?xml version="1.0" encoding="'
                              . $this->encoding . '"?>' . "\n"
                              . $this->serializeDebug()
                              . $r->serialize();
    }

    /**
     * Determines the HTTP headers and puts them in the $server_headers
     * property
     *
     * @return boolean  TRUE if okay, FALSE if $server_payload isn't set.
     *
     * @uses XML_RPC_Server::createServerPayload(),
     *       XML_RPC_Server::$server_headers
     */
    function createServerHeaders()
    {
        if (!$this->server_payload) {
            return false;
        }
        $this->server_headers = 'Content-Length: '
                              . strlen($this->server_payload) . "\r\n"
                              . 'Content-Type: text/xml;'
                              . ' charset=' . $this->encoding;
        return true;
    }

    /**
     * @return array
     */
    function verifySignature($in, $sig)
    {
        for ($i = 0; $i < sizeof($sig); $i++) {
            // check each possible signature in turn
            $cursig = $sig[$i];
            if (sizeof($cursig) == $in->getNumParams() + 1) {
                $itsOK = 1;
                for ($n = 0; $n < $in->getNumParams(); $n++) {
                    $p = $in->getParam($n);
                    // print "<!-- $p -->\n";
                    if ($p->kindOf() == 'scalar') {
                        $pt = $p->scalartyp();
                    } else {
                        $pt = $p->kindOf();
                    }
                    // $n+1 as first type of sig is return type
                    if ($pt != $cursig[$n+1]) {
                        $itsOK = 0;
                        $pno = $n+1;
                        $wanted = $cursig[$n+1];
                        $got = $pt;
                        break;
                    }
                }
                if ($itsOK) {
                    return array(1);
                }
            }
        }
        if (isset($wanted)) {
            return array(0, "Wanted ${wanted}, got ${got} at param ${pno}");
        } else {
            $allowed = array();
            foreach ($sig as $val) {
                end($val);
                $allowed[] = key($val);
            }
            $allowed = array_unique($allowed);
            $last = count($allowed) - 1;
            if ($last > 0) {
                $allowed[$last] = 'or ' . $allowed[$last];
            }
            return array(0,
                         'Signature permits ' . implode(', ', $allowed)
                                . ' parameters but the request had '
                                . $in->getNumParams());
        }
    }

    /**
     * @return object  a new XML_RPC_Response object
     *
     * @uses XML_RPC_Message::getEncoding(), XML_RPC_Server::$encoding
     */
    function parseRequest($data = '')
    {
        global $XML_RPC_xh, $HTTP_RAW_POST_DATA,
                $XML_RPC_err, $XML_RPC_str, $XML_RPC_errxml,
                $XML_RPC_defencoding, $XML_RPC_Server_dmap;

        if ($data == '') {
            $data = $HTTP_RAW_POST_DATA;
        }

        $this->encoding = XML_RPC_Message::getEncoding($data);
        $parser_resource = xml_parser_create($this->encoding);
        $parser = (int) $parser_resource;

        $XML_RPC_xh[$parser] = array();
        $XML_RPC_xh[$parser]['cm']     = 0;
        $XML_RPC_xh[$parser]['isf']    = 0;
        $XML_RPC_xh[$parser]['params'] = array();
        $XML_RPC_xh[$parser]['method'] = '';
        $XML_RPC_xh[$parser]['stack'] = array();	
        $XML_RPC_xh[$parser]['valuestack'] = array();	

        $plist = '';

        // decompose incoming XML into request structure

        xml_parser_set_option($parser_resource, XML_OPTION_CASE_FOLDING, true);
        xml_set_element_handler($parser_resource, 'XML_RPC_se', 'XML_RPC_ee');
        xml_set_character_data_handler($parser_resource, 'XML_RPC_cd');
        if (!xml_parse($parser_resource, $data, 1)) {
            // return XML error as a faultCode
            $r = new XML_RPC_Response(0,
                                      $XML_RPC_errxml+xml_get_error_code($parser_resource),
                                      sprintf('XML error: %s at line %d',
                                              xml_error_string(xml_get_error_code($parser_resource)),
                                              xml_get_current_line_number($parser_resource)));
            xml_parser_free($parser_resource);
        } elseif ($XML_RPC_xh[$parser]['isf']>1) {
            $r = new XML_RPC_Response(0,
                                      $XML_RPC_err['invalid_request'],
                                      $XML_RPC_str['invalid_request']
                                      . ': '
                                      . $XML_RPC_xh[$parser]['isf_reason']);
            xml_parser_free($parser_resource);
        } else {
            xml_parser_free($parser_resource);
            $m = new XML_RPC_Message($XML_RPC_xh[$parser]['method']);
            // now add parameters in
            for ($i = 0; $i < sizeof($XML_RPC_xh[$parser]['params']); $i++) {
                // print '<!-- ' . $XML_RPC_xh[$parser]['params'][$i]. "-->\n";
                $plist .= "$i - " . var_export($XML_RPC_xh[$parser]['params'][$i], true) . " \n";
                $m->addParam($XML_RPC_xh[$parser]['params'][$i]);
            }
            XML_RPC_Server_debugmsg($plist);

            // now to deal with the method
            $methName = $XML_RPC_xh[$parser]['method'];
            if (strpos($methName, 'system.') === 0) {
                $dmap = $XML_RPC_Server_dmap;
                $sysCall = 1;
            } else {
                $dmap = $this->dmap;
                $sysCall = 0;
            }

            if (isset($dmap[$methName]['function'])
                && is_string($dmap[$methName]['function'])
                && strpos($dmap[$methName]['function'], '::') !== false)
            {
                $dmap[$methName]['function'] =
                        explode('::', $dmap[$methName]['function']);
            }

            if (isset($dmap[$methName]['function'])
                && is_callable($dmap[$methName]['function']))
            {
                // dispatch if exists
                if (isset($dmap[$methName]['signature'])) {
                    $sr = $this->verifySignature($m,
                                                 $dmap[$methName]['signature'] );
                }
                if (!isset($dmap[$methName]['signature']) || $sr[0]) {
                    // if no signature or correct signature
                    if ($sysCall) {
                        $r = call_user_func($dmap[$methName]['function'], $this, $m);
                    } else {
                        $r = call_user_func($dmap[$methName]['function'], $m);
                    }
                    if (!is_a($r, 'XML_RPC_Response')) {
                        $r = new XML_RPC_Response(0, $XML_RPC_err['not_response_object'],
                                                  $XML_RPC_str['not_response_object']);
                    }
                } else {
                    $r = new XML_RPC_Response(0, $XML_RPC_err['incorrect_params'],
                                              $XML_RPC_str['incorrect_params']
                                              . ': ' . $sr[1]);
                }
            } else {
                // else prepare error response
                $r = new XML_RPC_Response(0, $XML_RPC_err['unknown_method'],
                                          $XML_RPC_str['unknown_method']);
            }
        }
        return $r;
    }

    /**
     * Echos back the input packet as a string value
     *
     * @return void
     *
     * Useful for debugging.
     */
    function echoInput()
    {
        global $HTTP_RAW_POST_DATA;

        $r = new XML_RPC_Response(0);
        $r->xv = new XML_RPC_Value("'Aha said I: '" . $HTTP_RAW_POST_DATA, 'string');
        print $r->serialize();
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
