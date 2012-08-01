<?php

/**
 * Parse a XRDS discovery description to a simple array format.
 * 
 * For now a simple parse of the document. Better error checking
 * in a later version.
 * 
 * @version $Id$
 * @author Marc Worrell <marcw@pobox.com>
 * 
 * 
 * The MIT License
 * 
 * Copyright (c) 2007-2008 Mediamatic Lab
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/* example of use:

header('content-type: text/plain');
$file = file_get_contents('../../test/discovery/xrds-magnolia.xrds');
$xrds = xrds_parse($file);
print_r($xrds);

 */ 

/**
 * Parse the xrds file in the argument.  The xrds description must have been 
 * fetched via curl or something else.
 * 
 * TODO: more robust checking, support for more service documents
 * TODO: support for URIs to definition instead of local xml:id
 * 
 * @param string data contents of xrds file
 * @exception Exception when the file is in an unknown format
 * @return array
 */
function xrds_parse ( $data )
{
	$oauth = array();
	$doc   = @DOMDocument::loadXML($data);
	if ($doc === false)
	{
		throw new Exception('Error in XML, can\'t load XRDS document');
	}
	
	$xpath = new DOMXPath($doc);
	$xpath->registerNamespace('xrds',	'xri://$xrds');
	$xpath->registerNamespace('xrd',  	'xri://$XRD*($v*2.0)');
	$xpath->registerNamespace('simple', 'http://xrds-simple.net/core/1.0');

	// Yahoo! uses this namespace, with lowercase xrd in it
	$xpath->registerNamespace('xrd2',  	'xri://$xrd*($v*2.0)');

	$uris = xrds_oauth_service_uris($xpath);

	foreach ($uris as $uri)
	{
		// TODO: support uris referring to service documents outside this one
		if ($uri{0} == '#')
		{
			$id    = substr($uri, 1);
			$oauth = xrds_xrd_oauth($xpath, $id);
			if (is_array($oauth) && !empty($oauth))
			{
				return $oauth;
			}
		}
	}

	return false;
}


/**
 * Parse a XRD definition for OAuth and return the uris etc.
 * 
 * @param XPath xpath
 * @param string id
 * @return array
 */
function xrds_xrd_oauth ( $xpath, $id )
{
	$oauth = array();
	$xrd   = $xpath->query('//xrds:XRDS/xrd:XRD[@xml:id="'.$id.'"]');
	if ($xrd->length == 0)
	{
		// Yahoo! uses another namespace
		$xrd = $xpath->query('//xrds:XRDS/xrd2:XRD[@xml:id="'.$id.'"]');
	}

	if ($xrd->length >= 1)
	{
		$x 		  = $xrd->item(0);
		$services = array();
		foreach ($x->childNodes as $n)
		{
			switch ($n->nodeName)
			{
			case 'Type':
				if ($n->nodeValue != 'xri://$xrds*simple')
				{
					// Not a simple XRDS document
					return false;
				}
				break;
			case 'Expires':
				$oauth['expires'] = $n->nodeValue;
				break;
			case 'Service':
				list($type,$service) = xrds_xrd_oauth_service($n);
				if ($type)
				{
					$services[$type][xrds_priority($n)][] = $service;
				}
				break;
			}
		}
		
		// Flatten the services on priority
		foreach ($services as $type => $service)
		{
			$oauth[$type] = xrds_priority_flatten($service);
		}
	}
	else
	{
		$oauth = false;
	}
	return $oauth;
}


/**
 * Parse a service definition for OAuth in a simple xrd element
 * 
 * @param DOMElement n
 * @return array (type, service desc)
 */
function xrds_xrd_oauth_service ( $n )
{
	$service = array(
				'uri'				=> '',
				'signature_method'	=> array(),
				'parameters'		=> array()
				);

	$type    = false;
	foreach ($n->childNodes as $c)
	{
		$name  = $c->nodeName;
		$value = $c->nodeValue;
		
		if ($name == 'URI')
		{
			$service['uri'] = $value;
		}
		else if ($name == 'Type')
		{
			if (strncmp($value, 'http://oauth.net/core/1.0/endpoint/', 35) == 0)
			{
				$type = basename($value);
			}
			else if (strncmp($value, 'http://oauth.net/core/1.0/signature/', 36) == 0)
			{
				$service['signature_method'][] = basename($value);
			}
			else if (strncmp($value, 'http://oauth.net/core/1.0/parameters/', 37) == 0)
			{
				$service['parameters'][] = basename($value);
			}
			else if (strncmp($value, 'http://oauth.net/discovery/1.0/consumer-identity/', 49) == 0)
			{
				$type = 'consumer_identity';
				$service['method'] = basename($value);
				unset($service['signature_method']);
				unset($service['parameters']);
			}
			else
			{
				$service['unknown'][] = $value;
			}
		}
		else if ($name == 'LocalID')
		{
			$service['consumer_key'] = $value;
		}
		else if ($name{0} != '#')
		{
			$service[strtolower($name)] = $value;
		}
	}
	return array($type, $service);
}


/**
 * Return the OAuth service uris in order of the priority.
 * 
 * @param XPath xpath
 * @return array
 */
function xrds_oauth_service_uris ( $xpath )
{
	$uris	   = array();
	$xrd_oauth = $xpath->query('//xrds:XRDS/xrd:XRD/xrd:Service/xrd:Type[.=\'http://oauth.net/discovery/1.0\']');
	if ($xrd_oauth->length > 0)
	{
		$service = array();
		foreach ($xrd_oauth as $xo)
		{
			// Find the URI of the service definition
			$cs = $xo->parentNode->childNodes;
			foreach ($cs as $c)
			{
				if ($c->nodeName == 'URI')
				{
					$prio 			  = xrds_priority($xo);
					$service[$prio][] = $c->nodeValue;
				}
			}
		}
		$uris = xrds_priority_flatten($service);
	}
	return $uris;
}



/**
 * Flatten an array according to the priority
 * 
 * @param array  ps buckets per prio
 * @return array one dimensional array
 */
function xrds_priority_flatten ( $ps )
{
	$prio = array();
	$null = array();
	ksort($ps);
	foreach ($ps as $idx => $bucket)
	{
		if (!empty($bucket))
		{
			if ($idx == 'null')
			{
				$null = $bucket;
			}
			else
			{
				$prio = array_merge($prio, $bucket);
			}
		}
	}
	$prio = array_merge($prio, $bucket);
	return $prio;
}


/**
 * Fetch the priority of a element
 * 
 * @param DOMElement elt
 * @return mixed		'null' or int
 */
function xrds_priority ( $elt )
{
	if ($elt->hasAttribute('priority'))
	{
		$prio = $elt->getAttribute('priority');
		if (is_numeric($prio))
		{
			$prio = intval($prio);
		}
	}
	else
	{
		$prio = 'null';
	}
	return $prio;
}


/* vi:set ts=4 sts=4 sw=4 binary noeol: */
 
?>