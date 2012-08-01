<?php

/**
 * Request wrapper class.  Prepares a request for consumption by the OAuth routines
 * 
 * @version $Id: OAuthRequest.php 174 2010-11-24 15:15:41Z brunobg@corollarium.com $
 * @author Marc Worrell <marcw@pobox.com>
 * @date  Nov 16, 2007 12:20:31 PM
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


require_once dirname(__FILE__) . '/OAuthException2.php';

/**
 * Object to parse an incoming OAuth request or prepare an outgoing OAuth request
 */
class OAuthRequest 
{
	/* the realm for this request */
	protected $realm;
	
	/* all the parameters, RFC3986 encoded name/value pairs */
	protected $param = array();

	/* the parsed request uri */
	protected $uri_parts;

	/* the raw request uri */
	protected $uri;

	/* the request headers */
	protected $headers;

	/* the request method */
	protected $method;
	
	/* the body of the OAuth request */
	protected $body;
	

	/**
	 * Construct from the current request. Useful for checking the signature of a request.
	 * When not supplied with any parameters this will use the current request.
	 * 
	 * @param string	uri				might include parameters
	 * @param string	method			GET, PUT, POST etc.
	 * @param string	parameters		additional post parameters, urlencoded (RFC1738)
	 * @param array		headers			headers for request
	 * @param string	body			optional body of the OAuth request (POST or PUT)
	 */
	function __construct ( $uri = null, $method = null, $parameters = '', $headers = array(), $body = null )
	{
		if (is_object($_SERVER))
		{
			// Tainted arrays - the normal stuff in anyMeta
			if (!$method) {
				$method	= $_SERVER->REQUEST_METHOD->getRawUnsafe();
			}
			if (empty($uri)) {
				$uri	= $_SERVER->REQUEST_URI->getRawUnsafe();
			}
		}
		else
		{
			// non anyMeta systems
			if (!$method) {
				if (isset($_SERVER['REQUEST_METHOD'])) {
					$method	= $_SERVER['REQUEST_METHOD'];
				}
				else {
					$method = 'GET';
				}
			}
			$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
			if (empty($uri)) {
				if (strpos($_SERVER['REQUEST_URI'], "://") !== false) {
					$uri = $_SERVER['REQUEST_URI'];
				}
				else {
					$uri = sprintf('%s://%s%s', $proto, $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);
				}
			}
		}
		$headers      = OAuthRequestLogger::getAllHeaders();
		$this->method = strtoupper($method);
		
		// If this is a post then also check the posted variables
		if (strcasecmp($method, 'POST') == 0)
		{
			// TODO: what to do with 'multipart/form-data'?
			if ($this->getRequestContentType() == 'multipart/form-data')
			{
				// Get the posted body (when available)
				if (!isset($headers['X-OAuth-Test']))
				{
					$parameters .= $this->getRequestBodyOfMultipart();
				}
			}
			if ($this->getRequestContentType() == 'application/x-www-form-urlencoded')
			{
				// Get the posted body (when available)
				if (!isset($headers['X-OAuth-Test']))
				{
					$parameters .= $this->getRequestBody();
				}
			}
			else
			{
				$body = $this->getRequestBody();
			}
		}
		else if (strcasecmp($method, 'PUT') == 0)
		{
			$body = $this->getRequestBody();
		}

		$this->method  = strtoupper($method);
		$this->headers = $headers;
		// Store the values, prepare for oauth
		$this->uri     = $uri;
		$this->body    = $body;
		$this->parseUri($parameters);
		$this->parseHeaders();
		$this->transcodeParams();
	}


	/**
	 * Return the signature base string.
	 * Note that we can't use rawurlencode due to specified use of RFC3986.
	 * 
	 * @return string
	 */
	function signatureBaseString ()
	{
		$sig 	= array();
		$sig[]	= $this->method;
		$sig[]	= $this->getRequestUrl();
		$sig[]	= $this->getNormalizedParams();
		
		return implode('&', array_map(array($this, 'urlencode'), $sig));
	}
	
	
	/**
	 * Calculate the signature of the request, using the method in oauth_signature_method.
	 * The signature is returned encoded in the form as used in the url.  So the base64 and
	 * urlencoding has been done.
	 * 
	 * @param string consumer_secret
	 * @param string token_secret
	 * @param string token_type
	 * @exception when not all parts available
	 * @return string
	 */
	function calculateSignature ( $consumer_secret, $token_secret, $token_type = 'access' )
	{
		$required = array(
						'oauth_consumer_key',
						'oauth_signature_method',
						'oauth_timestamp',
						'oauth_nonce'
					);

		if ($token_type != 'requestToken')
		{
			$required[] = 'oauth_token';
		}

		foreach ($required as $req)
		{
			if (!isset($this->param[$req]))
			{
				throw new OAuthException2('Can\'t sign request, missing parameter "'.$req.'"');
			}
		}

		$this->checks();

		$base      = $this->signatureBaseString();
		$signature = $this->calculateDataSignature($base, $consumer_secret, $token_secret, $this->param['oauth_signature_method']);
		return $signature;
	}

	
	/**
	 * Calculate the signature of a string.
	 * Uses the signature method from the current parameters.
	 * 
	 * @param string 	data
	 * @param string	consumer_secret
	 * @param string	token_secret
	 * @param string 	signature_method
	 * @exception OAuthException2 thrown when the signature method is unknown 
	 * @return string signature
	 */
	function calculateDataSignature ( $data, $consumer_secret, $token_secret, $signature_method )
	{
		if (is_null($data))
		{
			$data = '';
		}

		$sig = $this->getSignatureMethod($signature_method);
		return $sig->signature($this, $data, $consumer_secret, $token_secret);
	}


	/**
	 * Select a signature method from the list of available methods.
	 * We try to check the most secure methods first.
	 * 
	 * @todo Let the signature method tell us how secure it is
	 * @param array methods
	 * @exception OAuthException2 when we don't support any method in the list
	 * @return string
	 */
	public function selectSignatureMethod ( $methods )
	{
		if (in_array('HMAC-SHA1', $methods))
		{
			$method = 'HMAC-SHA1';
		}
		else if (in_array('MD5', $methods))
		{
			$method = 'MD5';
		}
		else
		{
			$method = false;
			foreach ($methods as $m)
			{
				$m = strtoupper($m);
				$m2 = preg_replace('/[^A-Z0-9]/', '_', $m);
				if (file_exists(dirname(__FILE__).'/signature_method/OAuthSignatureMethod_'.$m2.'.php'))
				{
					$method = $m;
					break;
				}
			}
			
			if (empty($method))
			{
				throw new OAuthException2('None of the signing methods is supported.');
			}
		}
		return $method;
	}

	
	/**
	 * Fetch the signature object used for calculating and checking the signature base string
	 * 
	 * @param string method
	 * @return OAuthSignatureMethod object
	 */
	function getSignatureMethod ( $method )
	{
		$m     = strtoupper($method);
		$m     = preg_replace('/[^A-Z0-9]/', '_', $m);
		$class = 'OAuthSignatureMethod_'.$m;

		if (file_exists(dirname(__FILE__).'/signature_method/'.$class.'.php'))
		{
			require_once dirname(__FILE__).'/signature_method/'.$class.'.php';
			$sig = new $class();
		}
		else
		{
			throw new OAuthException2('Unsupported signature method "'.$m.'".');
		}
		return $sig;
	}


	/**
	 * Perform some sanity checks.
	 * 
	 * @exception OAuthException2 thrown when sanity checks failed
	 */
	function checks ()
	{
		if (isset($this->param['oauth_version']))
		{
			$version = $this->urldecode($this->param['oauth_version']);
			if ($version != '1.0')
			{
				throw new OAuthException2('Expected OAuth version 1.0, got "'.$this->param['oauth_version'].'"');
			}
		}
	}


	/**
	 * Return the request method
	 * 
	 * @return string
	 */
	function getMethod ()
	{
		return $this->method;
	}

	/**
	 * Return the complete parameter string for the signature check.
	 * All parameters are correctly urlencoded and sorted on name and value
	 * 
	 * @return string
	 */
	function getNormalizedParams ()
	{
		/*
		// sort by name, then by value 
		// (needed when we start allowing multiple values with the same name)
		$keys   = array_keys($this->param);
		$values = array_values($this->param);
		array_multisort($keys, SORT_ASC, $values, SORT_ASC);
        */
        $params     = $this->param;
		$normalized = array();

		ksort($params);
		foreach ($params as $key => $value)
		{
		    // all names and values are already urlencoded, exclude the oauth signature
		    if ($key != 'oauth_signature')
		   	{
				if (is_array($value))
				{
					$value_sort = $value;
					sort($value_sort);
					foreach ($value_sort as $v)
					{
						$normalized[] = $key.'='.$v;
					}
				}
				else
				{
					$normalized[] = $key.'='.$value;
				}
			}
		}
		return implode('&', $normalized);
	}


	/**
	 * Return the normalised url for signature checks
	 */
	function getRequestUrl ()
	{
        $url =  $this->uri_parts['scheme'] . '://'
              . $this->uri_parts['user'] . (!empty($this->uri_parts['pass']) ? ':' : '')
              . $this->uri_parts['pass'] . (!empty($this->uri_parts['user']) ? '@' : '')
			  . $this->uri_parts['host'];
			  
		if (	$this->uri_parts['port'] 
			&&	$this->uri_parts['port'] != $this->defaultPortForScheme($this->uri_parts['scheme']))
		{
			$url .= ':'.$this->uri_parts['port'];
		}
		if (!empty($this->uri_parts['path']))
		{
			$url .= $this->uri_parts['path'];
		}
		return $url;
	}
	
	
	/**
	 * Get a parameter, value is always urlencoded
	 * 
	 * @param string	name
	 * @param boolean	urldecode	set to true to decode the value upon return
	 * @return string value		false when not found
	 */
	function getParam ( $name, $urldecode = false )
	{
		if (isset($this->param[$name]))
		{
			$s = $this->param[$name];
		}
		else if (isset($this->param[$this->urlencode($name)]))
		{
			$s = $this->param[$this->urlencode($name)];
		}
		else
		{
			$s = false;
		}
		if (!empty($s) && $urldecode)
		{
			if (is_array($s))
			{
				$s = array_map(array($this,'urldecode'), $s);
			}
			else
			{
				$s = $this->urldecode($s);
			}
		}
		return $s;
	}

	/**
	 * Set a parameter
	 * 
	 * @param string	name
	 * @param string	value
	 * @param boolean	encoded	set to true when the values are already encoded
	 */
	function setParam ( $name, $value, $encoded = false )
	{
		if (!$encoded)
		{
			$name_encoded = $this->urlencode($name);
			if (is_array($value))
			{
				foreach ($value as $v)
				{
					$this->param[$name_encoded][] = $this->urlencode($v);
				}
			}
			else
			{
				$this->param[$name_encoded] = $this->urlencode($value);
			}
		}
		else
		{
			$this->param[$name] = $value;
		}
	}


	/**
	 * Re-encode all parameters so that they are encoded using RFC3986.
	 * Updates the $this->param attribute.
	 */
	protected function transcodeParams ()
	{
		$params      = $this->param;
		$this->param = array();
		
		foreach ($params as $name=>$value)
		{
			if (is_array($value))
			{
				$this->param[$this->urltranscode($name)] = array_map(array($this,'urltranscode'), $value);
			}
			else
			{
				$this->param[$this->urltranscode($name)] = $this->urltranscode($value);
			}
		}
	}



	/**
	 * Return the body of the OAuth request.
	 * 
	 * @return string		null when no body
	 */
	function getBody ()
	{
		return $this->body;
	}


	/**
	 * Return the body of the OAuth request.
	 * 
	 * @return string		null when no body
	 */
	function setBody ( $body )
	{
		$this->body = $body;
	}


	/**
	 * Parse the uri into its parts.  Fill in the missing parts.
	 * 
	 * @param string $parameters  optional extra parameters (from eg the http post)
	 */
	protected function parseUri ( $parameters )
	{
		$ps = @parse_url($this->uri);

		// Get the current/requested method
		$ps['scheme'] = strtolower($ps['scheme']);

		// Get the current/requested host
		if (function_exists('mb_strtolower'))
			$ps['host'] = mb_strtolower($ps['host']);
		else
			$ps['host'] = strtolower($ps['host']);
			
		if (!preg_match('/^[a-z0-9\.\-]+$/', $ps['host']))
		{
			throw new OAuthException2('Unsupported characters in host name');
		}

		// Get the port we are talking on
		if (empty($ps['port']))
		{
			$ps['port'] = $this->defaultPortForScheme($ps['scheme']);
		}
		
		if (empty($ps['user']))
		{
			$ps['user'] = '';
		}
		if (empty($ps['pass']))
		{
			$ps['pass'] = '';
		}
		if (empty($ps['path']))
		{
			$ps['path'] = '/';
		}
		if (empty($ps['query']))
		{
			$ps['query'] = '';
		}
		if (empty($ps['fragment']))
		{
			$ps['fragment'] = '';
		}

		// Now all is complete - parse all parameters
		foreach (array($ps['query'], $parameters) as $params)
		{
			if (strlen($params) > 0)
			{
				$params = explode('&', $params);
				foreach ($params as $p)
				{
					@list($name, $value) = explode('=', $p, 2);
					if (!strlen($name)) 
					{
						continue;
					}

					if (array_key_exists($name, $this->param)) 
					{
						if (is_array($this->param[$name]))
							$this->param[$name][] = $value;
						else
							$this->param[$name] = array($this->param[$name], $value);
					}
					else 
					{
						$this->param[$name]  = $value;
					}
				}
			}
		}
		$this->uri_parts = $ps;
	}


	/**
	 * Return the default port for a scheme
	 * 
	 * @param string scheme
	 * @return int
	 */
	protected function defaultPortForScheme ( $scheme )
	{
		switch ($scheme)
		{
		case 'http':	return 80;
		case 'https':	return 443;
		default:
			throw new OAuthException2('Unsupported scheme type, expected http or https, got "'.$scheme.'"');
			break;
		}
	}
	
	
	/**
	 * Encode a string according to the RFC3986
	 * 
	 * @param string s
	 * @return string
	 */
	function urlencode ( $s )
	{
		if ($s === false)
		{
			return $s;
		}
		else
		{
			return str_replace('%7E', '~', rawurlencode($s));
		}
	}
	
	/**
	 * Decode a string according to RFC3986.
	 * Also correctly decodes RFC1738 urls.
	 * 
	 * @param string s
	 * @return string
	 */
	function urldecode ( $s )
	{
		if ($s === false)
		{
			return $s;
		}
		else
		{
			return rawurldecode($s);
		}
	}

	/**
	 * urltranscode - make sure that a value is encoded using RFC3986.
	 * We use a basic urldecode() function so that any use of '+' as the
	 * encoding of the space character is correctly handled.
	 * 
	 * @param string s
	 * @return string
	 */
	function urltranscode ( $s )
	{
		if ($s === false)
		{
			return $s;
		}
		else
		{
			return $this->urlencode(rawurldecode($s));
			// return $this->urlencode(urldecode($s));
		}
	}


	/**
	 * Parse the oauth parameters from the request headers
	 * Looks for something like:
	 *
     * Authorization: OAuth realm="http://photos.example.net/authorize",
     *           oauth_consumer_key="dpf43f3p2l4k3l03",
     *           oauth_token="nnch734d00sl2jdk",
     *           oauth_signature_method="HMAC-SHA1",
     *           oauth_signature="tR3%2BTy81lMeYAr%2FFid0kMTYa%2FWM%3D",
     *           oauth_timestamp="1191242096",
     *           oauth_nonce="kllo9940pd9333jh",
     *           oauth_version="1.0"
     */
	private function parseHeaders ()
	{
/*
		$this->headers['Authorization'] = 'OAuth realm="http://photos.example.net/authorize",
                oauth_consumer_key="dpf43f3p2l4k3l03",
                oauth_token="nnch734d00sl2jdk",
                oauth_signature_method="HMAC-SHA1",
                oauth_signature="tR3%2BTy81lMeYAr%2FFid0kMTYa%2FWM%3D",
                oauth_timestamp="1191242096",
                oauth_nonce="kllo9940pd9333jh",
                oauth_version="1.0"';
*/		
		if (isset($this->headers['Authorization']))
		{
			$auth = trim($this->headers['Authorization']);
			if (strncasecmp($auth, 'OAuth', 4) == 0)
			{
				$vs = explode(',', substr($auth, 6));
				foreach ($vs as $v)
				{
					if (strpos($v, '='))
					{
						$v = trim($v);
						list($name,$value) = explode('=', $v, 2);
						if (!empty($value) && $value{0} == '"' && substr($value, -1) == '"')
						{
							$value = substr(substr($value, 1), 0, -1);
						}
						
						if (strcasecmp($name, 'realm') == 0)
						{
							$this->realm = $value;
						}
						else
						{
							$this->param[$name] = $value;
						}
					}
				}
			}
		}
	}


	/**
	 * Fetch the content type of the current request
	 * 
	 * @return string
	 */
	private function getRequestContentType ()
	{
		$content_type = 'application/octet-stream';
		if (!empty($_SERVER) && array_key_exists('CONTENT_TYPE', $_SERVER))
		{
			list($content_type) = explode(';', $_SERVER['CONTENT_TYPE']);
		}
		return trim($content_type);
	}


	/**
	 * Get the body of a POST or PUT.
	 * 
	 * Used for fetching the post parameters and to calculate the body signature.
	 * 
	 * @return string		null when no body present (or wrong content type for body)
	 */
	private function getRequestBody ()
	{
		$body = null;
		if ($this->method == 'POST' || $this->method == 'PUT')
		{
			$body = '';
			$fh   = @fopen('php://input', 'r');
			if ($fh)
			{
				while (!feof($fh))
				{
					$s = fread($fh, 1024);
					if (is_string($s))
					{
						$body .= $s;
					}
				}
				fclose($fh);
			}
		}
		return $body;
	}

	/**
	 * Get the body of a POST with multipart/form-data by Edison tsai on 16:52 2010/09/16
	 *
	 * Used for fetching the post parameters and to calculate the body signature.
	 *
	 * @return string               null when no body present (or wrong content type for body)
	 */
	private function getRequestBodyOfMultipart()
	{
		$body = null;
		if ($this->method == 'POST')
		{
			$body = '';
			if (is_array($_POST) && count($_POST) > 1) 
			{
				foreach ($_POST AS $k => $v) {
					$body .= $k . '=' . $this->urlencode($v) . '&';
				} #end foreach
				if(substr($body,-1) == '&')
				{
					$body = substr($body, 0, strlen($body)-1);
				} #end if
			} #end if
		} #end if

		return $body;
	}
	
	
	/**
	 * Simple function to perform a redirect (GET).
	 * Redirects the User-Agent, does not return.
	 * 
	 * @param string uri
	 * @param array params		parameters, urlencoded
	 * @exception OAuthException2 when redirect uri is illegal
	 */
	public function redirect ( $uri, $params )
	{
		if (!empty($params))
		{
			$q = array();
			foreach ($params as $name=>$value)
			{
				$q[] = $name.'='.$value;
			}
			$q_s = implode('&', $q);
			
			if (strpos($uri, '?'))
			{
				$uri .= '&'.$q_s;
			}
			else
			{
				$uri .= '?'.$q_s;
			}
		}
		
		// simple security - multiline location headers can inject all kinds of extras
		$uri = preg_replace('/\s/', '%20', $uri);
		if (strncasecmp($uri, 'http://', 7) && strncasecmp($uri, 'https://', 8))
		{
			if (strpos($uri, '://'))
			{
				throw new OAuthException2('Illegal protocol in redirect uri '.$uri);
			}
			$uri = 'http://'.$uri;
		}
		
		header('HTTP/1.1 302 Found');
		header('Location: '.$uri);
		echo '';
		exit();
	}
}


/* vi:set ts=4 sts=4 sw=4 binary noeol: */

?>