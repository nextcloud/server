<?php

/**
 * Verify the current request.  Checks if signed and if the signature is correct.
 * When correct then also figures out on behalf of which user this request is being made.
 *  
 * @version $Id: OAuthRequestVerifier.php 155 2010-09-10 18:38:33Z brunobg@corollarium.com $
 * @author Marc Worrell <marcw@pobox.com>
 * @date  Nov 16, 2007 4:35:03 PM
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

require_once dirname(__FILE__) . '/OAuthStore.php';
require_once dirname(__FILE__) . '/OAuthRequest.php';


class OAuthRequestVerifier extends OAuthRequest
{
	private $request;
	private $store;
	private $accepted_signatures = null;
	
	/**
	 * Construct the request to be verified
	 * 
	 * @param string request
	 * @param string method
	 * @param array params The request parameters
	 */
	function __construct ( $uri = null, $method = null, $params = null )
	{
 		if ($params) {
 			$encodedParams = array();
 			foreach ($params as $key => $value) {
 				if (preg_match("/^oauth_/", $key)) {  
 					continue;
 				}
 				$encodedParams[rawurlencode($key)] = rawurlencode($value);
 			}
 			$this->param = array_merge($this->param, $encodedParams);
 		}
 
		$this->store = OAuthStore::instance();
		parent::__construct($uri, $method);
		
		OAuthRequestLogger::start($this);
	}
	
	
	/**
	 * See if the current request is signed with OAuth
	 * 
	 * @return boolean
	 */
	static public function requestIsSigned ()
	{
		if (isset($_REQUEST['oauth_signature']))
		{
			$signed = true;
		}
		else
		{
			$hs = OAuthRequestLogger::getAllHeaders();
			if (isset($hs['Authorization']) && strpos($hs['Authorization'], 'oauth_signature') !== false)
			{
				$signed = true;
			}
			else
			{
				$signed = false;
			}
		}
		return $signed;
	}


	/**
	 * Verify the request if it seemed to be signed.
	 * 
	 * @param string token_type the kind of token needed, defaults to 'access'
	 * @exception OAuthException2 thrown when the request did not verify
	 * @return boolean	true when signed, false when not signed
	 */
	public function verifyIfSigned ( $token_type = 'access' )
	{
		if ($this->getParam('oauth_consumer_key'))
		{
			OAuthRequestLogger::start($this);
			$this->verify($token_type);
			$signed = true;
			OAuthRequestLogger::flush();
		}
		else
		{
			$signed = false;
		}
		return $signed;
	}



	/**
	 * Verify the request
	 * 
	 * @param string token_type the kind of token needed, defaults to 'access' (false, 'access', 'request')
	 * @exception OAuthException2 thrown when the request did not verify
	 * @return int user_id associated with token (false when no user associated)
	 */
	public function verify ( $token_type = 'access' ) 
	{
		$retval = $this->verifyExtended($token_type);
		return $retval['user_id'];
	}
	
	
	/**
	 * Verify the request
	 * 
	 * @param string token_type the kind of token needed, defaults to 'access' (false, 'access', 'request')
	 * @exception OAuthException2 thrown when the request did not verify
	 * @return array ('user_id' => associated with token (false when no user associated),
	 *  'consumer_key' => the associated consumer_key)
	 * 
	 */
	public function verifyExtended ( $token_type = 'access' )
	{
		$consumer_key = $this->getParam('oauth_consumer_key');
		$token        = $this->getParam('oauth_token');
		$user_id      = false;
		$secrets      = array();

		if ($consumer_key && ($token_type === false || $token))
		{
			$secrets = $this->store->getSecretsForVerify(	$this->urldecode($consumer_key), 
															$this->urldecode($token), 
															$token_type);

			$this->store->checkServerNonce(	$this->urldecode($consumer_key),
											$this->urldecode($token),
											$this->getParam('oauth_timestamp', true),
											$this->getParam('oauth_nonce', true));

			$oauth_sig = $this->getParam('oauth_signature');
			if (empty($oauth_sig))
			{
				throw new OAuthException2('Verification of signature failed (no oauth_signature in request).');
			} 
			
			try
			{
				$this->verifySignature($secrets['consumer_secret'], $secrets['token_secret'], $token_type);
			}
			catch (OAuthException2 $e)
			{
				throw new OAuthException2('Verification of signature failed (signature base string was "'.$this->signatureBaseString().'").' 
					. " with  " . print_r(array($secrets['consumer_secret'], $secrets['token_secret'], $token_type), true));
			}
			
			// Check the optional body signature
			if ($this->getParam('xoauth_body_signature'))
			{
				$method = $this->getParam('xoauth_body_signature_method');
				if (empty($method))
				{
					$method = $this->getParam('oauth_signature_method');
				}

				try
				{
					$this->verifyDataSignature($this->getBody(), $secrets['consumer_secret'], $secrets['token_secret'], $method, $this->getParam('xoauth_body_signature'));
				}
				catch (OAuthException2 $e)
				{
					throw new OAuthException2('Verification of body signature failed.');
				}
			}
			
			// All ok - fetch the user associated with this request
			if (isset($secrets['user_id']))
			{
				$user_id = $secrets['user_id'];
			}
			
			// Check if the consumer wants us to reset the ttl of this token
			$ttl = $this->getParam('xoauth_token_ttl', true);
			if (is_numeric($ttl))
			{
				$this->store->setConsumerAccessTokenTtl($this->urldecode($token), $ttl);
			}
		}
		else
		{
			throw new OAuthException2('Can\'t verify request, missing oauth_consumer_key or oauth_token');
		}
		return array('user_id' => $user_id, 'consumer_key' => $consumer_key, 'osr_id' => $secrets['osr_id']); 
	}


	
	/**
	 * Verify the signature of the request, using the method in oauth_signature_method.
	 * The signature is returned encoded in the form as used in the url.  So the base64 and
	 * urlencoding has been done.
	 * 
	 * @param string consumer_secret
	 * @param string token_secret
	 * @exception OAuthException2 thrown when the signature method is unknown 
	 * @exception OAuthException2 when not all parts available
	 * @exception OAuthException2 when signature does not match
	 */
	public function verifySignature ( $consumer_secret, $token_secret, $token_type = 'access' )
	{
		$required = array(
						'oauth_consumer_key',
						'oauth_signature_method',
						'oauth_timestamp',
						'oauth_nonce',
						'oauth_signature'
					);

		if ($token_type !== false)
		{
			$required[] = 'oauth_token';
		}

		foreach ($required as $req)
		{
			if (!isset($this->param[$req]))
			{
				throw new OAuthException2('Can\'t verify request signature, missing parameter "'.$req.'"');
			}
		}

		$this->checks();

		$base = $this->signatureBaseString();
		$this->verifyDataSignature($base, $consumer_secret, $token_secret, $this->param['oauth_signature_method'], $this->param['oauth_signature']);
	}



	/**
	 * Verify the signature of a string.
	 * 
	 * @param string 	data
	 * @param string	consumer_secret
	 * @param string	token_secret
	 * @param string 	signature_method
	 * @param string 	signature
	 * @exception OAuthException2 thrown when the signature method is unknown 
	 * @exception OAuthException2 when signature does not match
	 */
	public function verifyDataSignature ( $data, $consumer_secret, $token_secret, $signature_method, $signature )
	{
		if (is_null($data))
		{
			$data = '';
		}

		$sig = $this->getSignatureMethod($signature_method);
		if (!$sig->verify($this, $data, $consumer_secret, $token_secret, $signature))
		{
			throw new OAuthException2('Signature verification failed ('.$signature_method.')');
		}
	}

	/**
	 * 
	 * @param array $accepted The array of accepted signature methods, or if null is passed 
	 * all supported methods are accepted and there is no filtering.
	 * 
	 */
	public function setAcceptedSignatureMethods($accepted = null) {
		if (is_array($accepted))
			$this->accepted_signatures = $accepted;
		else if ($accepted == null)
			$this->accepted_signatures = null;
	}
}


/* vi:set ts=4 sts=4 sw=4 binary noeol: */

?>