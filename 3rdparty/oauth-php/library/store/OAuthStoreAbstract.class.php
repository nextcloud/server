<?php

/**
 * Abstract base class for OAuthStore implementations
 * 
 * @version $Id$
 * @author Marc Worrell <marcw@pobox.com>
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

abstract class OAuthStoreAbstract
{
	abstract public function getSecretsForVerify ( $consumer_key, $token, $token_type = 'access' );
	abstract public function getSecretsForSignature ( $uri, $user_id );
	abstract public function getServerTokenSecrets ( $consumer_key, $token, $token_type, $user_id, $name = '' );
	abstract public function addServerToken ( $consumer_key, $token_type, $token, $token_secret, $user_id, $options = array() );

	abstract public function deleteServer ( $consumer_key, $user_id, $user_is_admin = false );
	abstract public function getServer( $consumer_key, $user_id, $user_is_admin = false );
	abstract public function getServerForUri ( $uri, $user_id );
	abstract public function listServerTokens ( $user_id );
	abstract public function countServerTokens ( $consumer_key );
	abstract public function getServerToken ( $consumer_key, $token, $user_id );
	abstract public function deleteServerToken ( $consumer_key, $token, $user_id, $user_is_admin = false );
	abstract public function listServers ( $q = '', $user_id );
	abstract public function updateServer ( $server, $user_id, $user_is_admin = false );

	abstract public function updateConsumer ( $consumer, $user_id, $user_is_admin = false );
	abstract public function deleteConsumer ( $consumer_key, $user_id, $user_is_admin = false );
	abstract public function getConsumer ( $consumer_key, $user_id, $user_is_admin = false );
	abstract public function getConsumerStatic ();

	abstract public function addConsumerRequestToken ( $consumer_key, $options = array() );
	abstract public function getConsumerRequestToken ( $token );
	abstract public function deleteConsumerRequestToken ( $token );
	abstract public function authorizeConsumerRequestToken ( $token, $user_id, $referrer_host = '' );
	abstract public function countConsumerAccessTokens ( $consumer_key );
	abstract public function exchangeConsumerRequestForAccessToken ( $token, $options = array() );
	abstract public function getConsumerAccessToken ( $token, $user_id );
	abstract public function deleteConsumerAccessToken ( $token, $user_id, $user_is_admin = false );
	abstract public function setConsumerAccessTokenTtl ( $token, $ttl );
	
	abstract public function listConsumers ( $user_id );
	abstract public function listConsumerApplications( $begin = 0, $total = 25 ); 
	abstract public function listConsumerTokens ( $user_id );

	abstract public function checkServerNonce ( $consumer_key, $token, $timestamp, $nonce );
	
	abstract public function addLog ( $keys, $received, $sent, $base_string, $notes, $user_id = null );
	abstract public function listLog ( $options, $user_id );
	
	abstract public function install ();	
	
	/**
	 * Fetch the current static consumer key for this site, create it when it was not found.
	 * The consumer secret for the consumer key is always empty.
	 * 
	 * @return string	consumer key 
	 */
	
	
	/* ** Some handy utility functions ** */
	
	/**
	 * Generate a unique key
	 * 
	 * @param boolean unique	force the key to be unique
	 * @return string
	 */
	public function generateKey ( $unique = false )
	{
		$key = md5(uniqid(rand(), true));
		if ($unique)
		{
			list($usec,$sec) = explode(' ',microtime());
			$key .= dechex($usec).dechex($sec);
		}
		return $key;
	}

	/**
	 * Check to see if a string is valid utf8
	 * 
	 * @param string $s
	 * @return boolean
	 */
	protected function isUTF8 ( $s )
	{
		return preg_match('%(?:
	       [\xC2-\xDF][\x80-\xBF]              # non-overlong 2-byte
	       |\xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
	       |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
	       |\xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
	       |\xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
	       |[\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
	       |\xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
	       )+%xs', $s);
	}
	
	
	/**
	 * Make a string utf8, replacing all non-utf8 chars with a '.'
	 * 
	 * @param string
	 * @return string
	 */
	protected function makeUTF8 ( $s )
	{
		if (function_exists('iconv'))
		{
			do
			{
				$ok   = true;
				$text = @iconv('UTF-8', 'UTF-8//TRANSLIT', $s);
				if (strlen($text) != strlen($s))
				{
					// Remove the offending character...
					$s  = $text . '.' . substr($s, strlen($text) + 1);
					$ok = false;
				}
			}
			while (!$ok);
		}
		return $s;
	}
	
}

?>