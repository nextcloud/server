<?php

/**
 * Handle the discovery of OAuth service provider endpoints and static consumer identity.
 * 
 * @version $Id$
 * @author Marc Worrell <marcw@pobox.com>
 * @date  Sep 4, 2008 5:05:19 PM
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

require_once dirname(__FILE__).'/discovery/xrds_parse.php';

require_once dirname(__FILE__).'/OAuthException2.php';
require_once dirname(__FILE__).'/OAuthRequestLogger.php';


class OAuthDiscovery
{
	/**
	 * Return a description how we can do a consumer allocation.  Prefers static allocation if
	 * possible.  If static allocation is possible
	 * 
	 * See also: http://oauth.net/discovery/#consumer_identity_types
	 * 
	 * @param string uri
	 * @return array		provider description
	 */
	static function discover ( $uri )
	{
		// See what kind of consumer allocations are available
		$xrds_file = self::discoverXRDS($uri);
		if (!empty($xrds_file))
		{
			$xrds = xrds_parse($xrds_file);
			if (empty($xrds))
			{
				throw new OAuthException2('Could not discover OAuth information for '.$uri);
			}
		}
		else
		{
			throw new OAuthException2('Could not discover XRDS file at '.$uri);
		}

		// Fill an OAuthServer record for the uri found
		$ps			= parse_url($uri);
		$host		= isset($ps['host']) ? $ps['host'] : 'localhost';
		$server_uri = $ps['scheme'].'://'.$host.'/';

		$p = array(
				'user_id'			=> null,
				'consumer_key'		=> '',
				'consumer_secret'	=> '',
				'signature_methods'	=> '',
				'server_uri'		=> $server_uri,
				'request_token_uri'	=> '',
				'authorize_uri'		=> '',
				'access_token_uri'	=> ''
			);


		// Consumer identity (out of bounds or static)
		if (isset($xrds['consumer_identity']))
		{
			// Try to find a static consumer allocation, we like those :)
			foreach ($xrds['consumer_identity'] as $ci)
			{
				if ($ci['method'] == 'static' && !empty($ci['consumer_key']))
				{
					$p['consumer_key']    = $ci['consumer_key'];
					$p['consumer_secret'] = '';
				}
				else if ($ci['method'] == 'oob' && !empty($ci['uri']))
				{
					// TODO: Keep this uri somewhere for the user?
					$p['consumer_oob_uri'] = $ci['uri'];
				}
			}
		}

		// The token uris
		if (isset($xrds['request'][0]['uri']))
		{
			$p['request_token_uri'] = $xrds['request'][0]['uri'];
			if (!empty($xrds['request'][0]['signature_method']))
			{
				$p['signature_methods'] = $xrds['request'][0]['signature_method'];
			}
		}
		if (isset($xrds['authorize'][0]['uri']))
		{
			$p['authorize_uri'] = $xrds['authorize'][0]['uri'];
			if (!empty($xrds['authorize'][0]['signature_method']))
			{
				$p['signature_methods'] = $xrds['authorize'][0]['signature_method'];
			}
		}
		if (isset($xrds['access'][0]['uri']))
		{
			$p['access_token_uri'] = $xrds['access'][0]['uri'];
			if (!empty($xrds['access'][0]['signature_method']))
			{
				$p['signature_methods'] = $xrds['access'][0]['signature_method'];
			}
		}
		return $p;
	}
	
	
	/**
	 * Discover the XRDS file at the uri.  This is a bit primitive, you should overrule
	 * this function so that the XRDS file can be cached for later referral.
	 * 
	 * @param string uri
	 * @return string		false when no XRDS file found
	 */
	static protected function discoverXRDS ( $uri, $recur = 0 )
	{
		// Bail out when we are following redirects
		if ($recur > 10)
		{
			return false;
		}
		
		$data = self::curl($uri);

		// Check what we got back, could be:
		// 1. The XRDS discovery file itself (check content-type)
		// 2. The X-XRDS-Location header
		
		if (is_string($data) && !empty($data))
		{
			list($head,$body) = explode("\r\n\r\n", $data);
			$body = trim($body);
			$m	  = false;

			// See if we got the XRDS file itself or we have to follow a location header
			if (	preg_match('/^Content-Type:\s*application\/xrds+xml/im', $head)
				||	preg_match('/^<\?xml[^>]*\?>\s*<xrds\s/i', $body)
				||	preg_match('/^<xrds\s/i', $body)
				)
			{
				$xrds = $body;
			}
			else if (	preg_match('/^X-XRDS-Location:\s*([^\r\n]*)/im', $head, $m)
					||	preg_match('/^Location:\s*([^\r\n]*)/im', $head, $m))
			{
				// Recurse to the given location
				if ($uri != $m[1])
				{
					$xrds = self::discoverXRDS($m[1], $recur+1);
				}
				else
				{
					// Referring to the same uri, bail out
					$xrds = false;
				}
			}
			else
			{
				// Not an XRDS file an nowhere else to check
				$xrds = false;
			}
		}
		else
		{
			$xrds = false;
		}
		return $xrds;
	}
	
	
	/**
	 * Try to fetch an XRDS file at the given location.  Sends an accept header preferring the xrds file.
	 * 
	 * @param string uri
	 * @return array	(head,body), false on an error
	 */
	static protected function curl ( $uri )
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_HTTPHEADER,	 array('Accept: application/xrds+xml, */*;q=0.1'));
		curl_setopt($ch, CURLOPT_USERAGENT,		 'anyMeta/OAuth 1.0 - (OAuth Discovery $LastChangedRevision: 45 $)');
		curl_setopt($ch, CURLOPT_URL, 			 $uri);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 		 true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 		 30);

		$txt = curl_exec($ch);
		curl_close($ch);

		// Tell the logger what we requested and what we received back
		$data = "GET $uri";
		OAuthRequestLogger::setSent($data, "");
		OAuthRequestLogger::setReceived($txt);

		return $txt;
	}
}


/* vi:set ts=4 sts=4 sw=4 binary noeol: */

?>