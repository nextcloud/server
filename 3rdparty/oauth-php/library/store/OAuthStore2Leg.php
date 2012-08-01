<?php

/**
 * OAuthStore implementation for 2 legged OAuth. This 'store' just saves the 
 * consumer_token and consumer_secret.
 * 
 * @version $Id$
 * @author Ben Hesketh <ben.hesketh@compassengine.com>
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

require_once dirname(__FILE__) . '/OAuthStoreAbstract.class.php';

class OAuthStore2Leg extends OAuthStoreAbstract
{
	protected $consumer_key;
	protected $consumer_secret;
	protected $signature_method = array('HMAC-SHA1');
	protected $token_type = false;
	
	/*
	 * Takes two options: consumer_key and consumer_secret
	 */
	public function __construct( $options = array() )
	{
		if(isset($options['consumer_key']) && isset($options['consumer_secret']))
		{
			$this->consumer_key = $options['consumer_key'];
			$this->consumer_secret = $options['consumer_secret'];
		}
		else
		{
			throw new OAuthException2("OAuthStore2Leg needs consumer_token and consumer_secret");
		}
	}

	public function getSecretsForVerify ( $consumer_key, $token, $token_type = 'access' ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function getSecretsForSignature ( $uri, $user_id ) 
	{
		return array(
			'consumer_key' => $this->consumer_key,
			'consumer_secret' => $this->consumer_secret,
			'signature_methods' => $this->signature_method,
			'token' => $this->token_type
		);	
	}
	public function getServerTokenSecrets ( $consumer_key, $token, $token_type, $user_id, $name = '' ) 	{ throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function addServerToken ( $consumer_key, $token_type, $token, $token_secret, $user_id, $options = array() ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }

	public function deleteServer ( $consumer_key, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function getServer( $consumer_key, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function getServerForUri ( $uri, $user_id ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function listServerTokens ( $user_id ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function countServerTokens ( $consumer_key ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function getServerToken ( $consumer_key, $token, $user_id ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function deleteServerToken ( $consumer_key, $token, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function setServerTokenTtl ( $consumer_key, $token, $token_ttl )
	{
		//This method just needs to exist. It doesn't have to do anything!
	}
	
	public function listServers ( $q = '', $user_id ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function updateServer ( $server, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }

	public function updateConsumer ( $consumer, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function deleteConsumer ( $consumer_key, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function getConsumer ( $consumer_key, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function getConsumerStatic () { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }

	public function addConsumerRequestToken ( $consumer_key, $options = array() ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function getConsumerRequestToken ( $token ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function deleteConsumerRequestToken ( $token ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function authorizeConsumerRequestToken ( $token, $user_id, $referrer_host = '' ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function countConsumerAccessTokens ( $consumer_key ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function exchangeConsumerRequestForAccessToken ( $token, $options = array() ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function getConsumerAccessToken ( $token, $user_id ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function deleteConsumerAccessToken ( $token, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function setConsumerAccessTokenTtl ( $token, $ttl ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	
	public function listConsumers ( $user_id ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function listConsumerApplications( $begin = 0, $total = 25 )  { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function listConsumerTokens ( $user_id ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }

	public function checkServerNonce ( $consumer_key, $token, $timestamp, $nonce ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	
	public function addLog ( $keys, $received, $sent, $base_string, $notes, $user_id = null ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	public function listLog ( $options, $user_id ) { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }
	
	public function install () { throw new OAuthException2("OAuthStore2Leg doesn't support " . __METHOD__); }		
}

?>