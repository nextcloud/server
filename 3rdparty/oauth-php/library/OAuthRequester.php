<?php

/**
 * Perform a signed OAuth request with a GET, POST, PUT or DELETE operation.
 * 
 * @version $Id: OAuthRequester.php 174 2010-11-24 15:15:41Z brunobg@corollarium.com $
 * @author Marc Worrell <marcw@pobox.com>
 * @date  Nov 20, 2007 1:41:38 PM
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

require_once dirname(__FILE__) . '/OAuthRequestSigner.php';
require_once dirname(__FILE__) . '/body/OAuthBodyContentDisposition.php';


class OAuthRequester extends OAuthRequestSigner
{
	protected $files;

	/**
	 * Construct a new request signer.  Perform the request with the doRequest() method below.
	 * 
	 * A request can have either one file or a body, not both. 
	 * 
	 * The files array consists of arrays:
	 * - file			the filename/path containing the data for the POST/PUT
	 * - data			data for the file, omit when you have a file
	 * - mime			content-type of the file
	 * - filename		filename for content disposition header
	 * 
	 * When OAuth (and PHP) can support multipart/form-data then we can handle more than one file.
	 * For now max one file, with all the params encoded in the query string.
	 * 
	 * @param string request
	 * @param string method		http method.  GET, PUT, POST etc.
	 * @param array params		name=>value array with request parameters
	 * @param string body		optional body to send
	 * @param array files		optional files to send (max 1 till OAuth support multipart/form-data posts)
	 */
	function __construct ( $request, $method = null, $params = null, $body = null, $files = null )
	{
		parent::__construct($request, $method, $params, $body);

		// When there are files, then we can construct a POST with a single file
		if (!empty($files))
		{
			$empty = true;
			foreach ($files as $f)
			{
				$empty = $empty && empty($f['file']) && !isset($f['data']);
			}
			
			if (!$empty)
			{
				if (!is_null($body))
				{
					throw new OAuthException2('When sending files, you can\'t send a body as well.');
				}
				$this->files = $files;
			}
		}
	}


	/**
	 * Perform the request, returns the response code, headers and body.
	 * 
	 * @param int usr_id			optional user id for which we make the request
	 * @param array curl_options	optional extra options for curl request
	 * @param array options			options like name and token_ttl
	 * @exception OAuthException2 when authentication not accepted
	 * @exception OAuthException2 when signing was not possible
	 * @return array (code=>int, headers=>array(), body=>string)
	 */
	function doRequest ( $usr_id = 0, $curl_options = array(), $options = array() )
	{
		$name = isset($options['name']) ? $options['name'] : '';
		if (isset($options['token_ttl']))
		{
			$this->setParam('xoauth_token_ttl', intval($options['token_ttl']));
		}

		if (!empty($this->files))
		{
			// At the moment OAuth does not support multipart/form-data, so try to encode
			// the supplied file (or data) as the request body and add a content-disposition header.
			list($extra_headers, $body) = OAuthBodyContentDisposition::encodeBody($this->files);
			$this->setBody($body);
			$curl_options = $this->prepareCurlOptions($curl_options, $extra_headers);
		}
		$this->sign($usr_id, null, $name);
		$text   = $this->curl_raw($curl_options);
		$result = $this->curl_parse($text);	
		if ($result['code'] >= 400)
		{
			throw new OAuthException2('Request failed with code ' . $result['code'] . ': ' . $result['body']);
		}

		// Record the token time to live for this server access token, immediate delete iff ttl <= 0
		// Only done on a succesful request.	
		$token_ttl = $this->getParam('xoauth_token_ttl', false);
		if (is_numeric($token_ttl))
		{
			$this->store->setServerTokenTtl($this->getParam('oauth_consumer_key',true), $this->getParam('oauth_token',true), $token_ttl);
		}

		return $result;
	}

	
	/**
	 * Request a request token from the site belonging to consumer_key
	 * 
	 * @param string consumer_key
	 * @param int usr_id
	 * @param array params (optional) extra arguments for when requesting the request token
	 * @param string method (optional) change the method of the request, defaults to POST (as it should be)
	 * @param array options (optional) options like name and token_ttl
	 * @param array curl_options	optional extra options for curl request
	 * @exception OAuthException2 when no key could be fetched
	 * @exception OAuthException2 when no server with consumer_key registered
	 * @return array (authorize_uri, token)
	 */
	static function requestRequestToken ( $consumer_key, $usr_id, $params = null, $method = 'POST', $options = array(), $curl_options = array())
	{
		OAuthRequestLogger::start();

		if (isset($options['token_ttl']) && is_numeric($options['token_ttl']))
		{
			$params['xoauth_token_ttl'] = intval($options['token_ttl']);
		}

		$store	= OAuthStore::instance();
		$r		= $store->getServer($consumer_key, $usr_id);
		$uri 	= $r['request_token_uri'];

		$oauth 	= new OAuthRequester($uri, $method, $params);
		$oauth->sign($usr_id, $r, '', 'requestToken');
		$text	= $oauth->curl_raw($curl_options);

		if (empty($text))
		{
			throw new OAuthException2('No answer from the server "'.$uri.'" while requesting a request token');
		}
		$data	= $oauth->curl_parse($text);
		if ($data['code'] != 200)
		{
			throw new OAuthException2('Unexpected result from the server "'.$uri.'" ('.$data['code'].') while requesting a request token');
		}
		$token  = array();
		$params = explode('&', $data['body']);
		foreach ($params as $p)
		{
			@list($name, $value) = explode('=', $p, 2);
			$token[$name] = $oauth->urldecode($value);
		}
		
		if (!empty($token['oauth_token']) && !empty($token['oauth_token_secret']))
		{
			$opts = array();
			if (isset($options['name']))
			{
				$opts['name'] = $options['name'];
			}
			if (isset($token['xoauth_token_ttl']))
			{
				$opts['token_ttl'] = $token['xoauth_token_ttl'];
			}
			$store->addServerToken($consumer_key, 'request', $token['oauth_token'], $token['oauth_token_secret'], $usr_id, $opts);
		}
		else
		{
			throw new OAuthException2('The server "'.$uri.'" did not return the oauth_token or the oauth_token_secret');
		}

		OAuthRequestLogger::flush();

		// Now we can direct a browser to the authorize_uri
		return array(
					'authorize_uri' => $r['authorize_uri'],
					'token'			=> $token['oauth_token']
				);
	}


	/**
	 * Request an access token from the site belonging to consumer_key.
	 * Before this we got an request token, now we want to exchange it for
	 * an access token.
	 * 
	 * @param string consumer_key
	 * @param string token
	 * @param int usr_id		user requesting the access token
	 * @param string method (optional) change the method of the request, defaults to POST (as it should be)
	 * @param array options (optional) extra options for request, eg token_ttl
	 * @param array curl_options	optional extra options for curl request
	 *  
	 * @exception OAuthException2 when no key could be fetched
	 * @exception OAuthException2 when no server with consumer_key registered
	 */
	static function requestAccessToken ( $consumer_key, $token, $usr_id, $method = 'POST', $options = array(), $curl_options = array() )
	{
		OAuthRequestLogger::start();
				
		$store	    = OAuthStore::instance();
		$r		    = $store->getServerTokenSecrets($consumer_key, $token, 'request', $usr_id);
		$uri 	    = $r['access_token_uri'];
		$token_name	= $r['token_name'];
		
		// Delete the server request token, this one was for one use only
		$store->deleteServerToken($consumer_key, $r['token'], 0, true);

		// Try to exchange our request token for an access token
		$oauth 	= new OAuthRequester($uri, $method);

		if (isset($options['oauth_verifier'])) 
		{
			$oauth->setParam('oauth_verifier', $options['oauth_verifier']);
        }
		if (isset($options['token_ttl']) && is_numeric($options['token_ttl']))
		{
			$oauth->setParam('xoauth_token_ttl', intval($options['token_ttl']));
		}

		OAuthRequestLogger::setRequestObject($oauth);

		$oauth->sign($usr_id, $r, '', 'accessToken');
		$text	= $oauth->curl_raw($curl_options);
		if (empty($text))
		{
			throw new OAuthException2('No answer from the server "'.$uri.'" while requesting an access token');
		}
		$data	= $oauth->curl_parse($text);

		if ($data['code'] != 200)
		{
			throw new OAuthException2('Unexpected result from the server "'.$uri.'" ('.$data['code'].') while requesting an access token');
		}

		$token  = array();
		$params = explode('&', $data['body']);
		foreach ($params as $p)
		{
			@list($name, $value) = explode('=', $p, 2);
			$token[$oauth->urldecode($name)] = $oauth->urldecode($value);
		}
		
		if (!empty($token['oauth_token']) && !empty($token['oauth_token_secret']))
		{
			$opts         = array();
			$opts['name'] = $token_name;
			if (isset($token['xoauth_token_ttl']))
			{
				$opts['token_ttl'] = $token['xoauth_token_ttl'];
			}
			$store->addServerToken($consumer_key, 'access', $token['oauth_token'], $token['oauth_token_secret'], $usr_id, $opts);
		}
		else
		{
			throw new OAuthException2('The server "'.$uri.'" did not return the oauth_token or the oauth_token_secret');
		}

		OAuthRequestLogger::flush();
	}



	/**
	 * Open and close a curl session passing all the options to the curl libs
	 * 
	 * @param array opts the curl options.
	 * @exception OAuthException2 when temporary file for PUT operation could not be created
	 * @return string the result of the curl action
	 */
	protected function curl_raw ( $opts = array() )
	{
		if (isset($opts[CURLOPT_HTTPHEADER]))
		{
			$header = $opts[CURLOPT_HTTPHEADER];
		}
		else
		{
			$header = array();
		}
		
		$ch 		= curl_init();
		$method		= $this->getMethod();
		$url		= $this->getRequestUrl();
		$header[]	= $this->getAuthorizationHeader();
		$query		= $this->getQueryString();
		$body		= $this->getBody();

		$has_content_type = false;
		foreach ($header as $h)
		{
			if (strncasecmp($h, 'Content-Type:', 13) == 0)
			{
				$has_content_type = true;
			}
		}
		
		if (!is_null($body))
		{
			if ($method == 'TRACE')
			{
				throw new OAuthException2('A body can not be sent with a TRACE operation');
			}

			// PUT and POST allow a request body
			if (!empty($query))
			{
				$url .= '?'.$query;
			}

			// Make sure that the content type of the request is ok
			if (!$has_content_type)
			{
				$header[]         = 'Content-Type: application/octet-stream';
				$has_content_type = true;
			}
			
			// When PUTting, we need to use an intermediate file (because of the curl implementation)
			if ($method == 'PUT')
			{
				/*
				if (version_compare(phpversion(), '5.2.0') >= 0)
				{
					// Use the data wrapper to create the file expected by the put method
					$put_file = fopen('data://application/octet-stream;base64,'.base64_encode($body));
				}
				*/
				
				$put_file = @tmpfile();
				if (!$put_file)
				{
					throw new OAuthException2('Could not create tmpfile for PUT operation');
				}
				fwrite($put_file, $body);
				fseek($put_file, 0);

				curl_setopt($ch, CURLOPT_PUT, 		  true);
  				curl_setopt($ch, CURLOPT_INFILE, 	  $put_file);
  				curl_setopt($ch, CURLOPT_INFILESIZE,  strlen($body));
			}
			else
			{
				curl_setopt($ch, CURLOPT_POST,		  true);
				curl_setopt($ch, CURLOPT_POSTFIELDS,  $body);
  			}
		}
		else
		{
			// a 'normal' request, no body to be send
			if ($method == 'POST')
			{
				if (!$has_content_type)
				{
					$header[]         = 'Content-Type: application/x-www-form-urlencoded';
					$has_content_type = true;
				}

				curl_setopt($ch, CURLOPT_POST, 		  true);
				curl_setopt($ch, CURLOPT_POSTFIELDS,  $query);
			}
			else
			{
				if (!empty($query))
				{
					$url .= '?'.$query;
				}
				if ($method != 'GET')
				{
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
				}
			}
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER,	 $header);
		curl_setopt($ch, CURLOPT_USERAGENT,		 'anyMeta/OAuth 1.0 - ($LastChangedRevision: 174 $)');
		curl_setopt($ch, CURLOPT_URL, 			 $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 		 true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 		 30);
	
		foreach ($opts as $k => $v)
		{
			if ($k != CURLOPT_HTTPHEADER)
			{
				curl_setopt($ch, $k, $v);
			}
		}

		$txt = curl_exec($ch);
		if ($txt === false) {
			$error = curl_error($ch);
			curl_close($ch);
			throw new OAuthException2('CURL error: ' . $error);
		} 
		curl_close($ch);
		
		if (!empty($put_file))
		{
			fclose($put_file);
		}

		// Tell the logger what we requested and what we received back
		$data = $method . " $url\n".implode("\n",$header);
		if (is_string($body))
		{
			$data .= "\n\n".$body;
		}
		else if ($method == 'POST')
		{
			$data .= "\n\n".$query;
		}

		OAuthRequestLogger::setSent($data, $body);
		OAuthRequestLogger::setReceived($txt);

		return $txt;
	}
	
	
	/**
	 * Parse an http response
	 * 
	 * @param string response the http text to parse
	 * @return array (code=>http-code, headers=>http-headers, body=>body)
	 */
	protected function curl_parse ( $response )
	{
		if (empty($response))
		{
			return array();
		}
	
		@list($headers,$body) = explode("\r\n\r\n",$response,2);
		$lines = explode("\r\n",$headers);

		if (preg_match('@^HTTP/[0-9]\.[0-9] +100@', $lines[0]))
		{
			/* HTTP/1.x 100 Continue
			 * the real data is on the next line
			 */
			@list($headers,$body) = explode("\r\n\r\n",$body,2);
			$lines = explode("\r\n",$headers);
		}
	
		// first line of headers is the HTTP response code 
		$http_line = array_shift($lines);
		if (preg_match('@^HTTP/[0-9]\.[0-9] +([0-9]{3})@', $http_line, $matches))
		{
			$code = $matches[1];
		}
	
		// put the rest of the headers in an array
		$headers = array();
		foreach ($lines as $l)
		{
			list($k, $v) = explode(': ', $l, 2);
			$headers[strtolower($k)] = $v;
		}
	
		return array( 'code' => $code, 'headers' => $headers, 'body' => $body);
	}


	/**
	 * Mix the given headers into the headers that were given to curl
	 * 
	 * @param array curl_options
	 * @param array extra_headers
	 * @return array new curl options
	 */
	protected function prepareCurlOptions ( $curl_options, $extra_headers )
	{
		$hs = array();
		if (!empty($curl_options[CURLOPT_HTTPHEADER]) && is_array($curl_options[CURLOPT_HTTPHEADER]))
		{
			foreach ($curl_options[CURLOPT_HTTPHEADER] as $h)
			{
				list($opt, $val) = explode(':', $h, 2);
				$opt      = str_replace(' ', '-', ucwords(str_replace('-', ' ', $opt)));
				$hs[$opt] = $val;
			}
		}

		$curl_options[CURLOPT_HTTPHEADER] = array();
		$hs = array_merge($hs, $extra_headers);		
		foreach ($hs as $h => $v)
		{
			$curl_options[CURLOPT_HTTPHEADER][] = "$h: $v";
		}
		return $curl_options;
	}
}

/* vi:set ts=4 sts=4 sw=4 binary noeol: */

?>