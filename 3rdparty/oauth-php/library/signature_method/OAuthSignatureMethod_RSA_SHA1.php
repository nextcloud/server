<?php

/**
 * OAuth signature implementation using PLAINTEXT
 * 
 * @version $Id$
 * @author Marc Worrell <marcw@pobox.com>
 * @date  Sep 8, 2008 12:00:14 PM
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


require_once dirname(__FILE__).'/OAuthSignatureMethod.class.php';

class OAuthSignatureMethod_RSA_SHA1 extends OAuthSignatureMethod
{
	public function name() 
	{
		return 'RSA-SHA1';
	}
	

	/**
	 * Fetch the public CERT key for the signature
	 * 
	 * @param OAuthRequest request
	 * @return string public key
	 */
	protected function fetch_public_cert ( $request )
	{
		// not implemented yet, ideas are:
		// (1) do a lookup in a table of trusted certs keyed off of consumer
		// (2) fetch via http using a url provided by the requester
		// (3) some sort of specific discovery code based on request
		//
		// either way should return a string representation of the certificate
		throw OAuthException2("OAuthSignatureMethod_RSA_SHA1::fetch_public_cert not implemented");
	}
	
	
	/**
	 * Fetch the private CERT key for the signature
	 * 
	 * @param OAuthRequest request
	 * @return string private key
	 */
	protected function fetch_private_cert ( $request )
	{
		// not implemented yet, ideas are:
		// (1) do a lookup in a table of trusted certs keyed off of consumer
		//
		// either way should return a string representation of the certificate
		throw OAuthException2("OAuthSignatureMethod_RSA_SHA1::fetch_private_cert not implemented");
	}


	/**
	 * Calculate the signature using RSA-SHA1
	 * This function is copyright Andy Smith, 2008.
	 * 
	 * @param OAuthRequest request
	 * @param string base_string
	 * @param string consumer_secret
	 * @param string token_secret
	 * @return string  
	 */
	public function signature ( $request, $base_string, $consumer_secret, $token_secret )
	{
		// Fetch the private key cert based on the request
		$cert = $this->fetch_private_cert($request);
		
		// Pull the private key ID from the certificate
		$privatekeyid = openssl_get_privatekey($cert);
		
		// Sign using the key
		$sig = false;
		$ok  = openssl_sign($base_string, $sig, $privatekeyid);   
		
		// Release the key resource
		openssl_free_key($privatekeyid);
		  
		return $request->urlencode(base64_encode($sig));
	}
	

	/**
	 * Check if the request signature is the same as the one calculated for the request.
	 * 
	 * @param OAuthRequest request
	 * @param string base_string
	 * @param string consumer_secret
	 * @param string token_secret
	 * @param string signature
	 * @return string  
	 */
	public function verify ( $request, $base_string, $consumer_secret, $token_secret, $signature )
	{
		$decoded_sig = base64_decode($request->urldecode($signature));
		  
		// Fetch the public key cert based on the request
		$cert = $this->fetch_public_cert($request);
		
		// Pull the public key ID from the certificate
		$publickeyid = openssl_get_publickey($cert);
		
		// Check the computed signature against the one passed in the query
		$ok = openssl_verify($base_string, $decoded_sig, $publickeyid);   
		
		// Release the key resource
		openssl_free_key($publickeyid);
		return $ok == 1;
	}

}

/* vi:set ts=4 sts=4 sw=4 binary noeol: */

?>