<?php

/**
 * OAuthSession is a really *dirty* storage. It's useful for testing and may 
 * be enough for some very simple applications, but it's not recommended for
 * production use.
 * 
 * @version $Id: OAuthStoreSession.php 153 2010-08-30 21:25:58Z brunobg@corollarium.com $
 * @author BBG
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

class OAuthStoreSession extends OAuthStoreAbstract
{
	private $session; 

	/*
	 * Takes two options: consumer_key and consumer_secret
	 */
	public function __construct( $options = array() )
	{
		if (!session_id()) {
			session_start();
		}
		if(isset($options['consumer_key']) && isset($options['consumer_secret']))
		{
			$this->session = &$_SESSION['oauth_' . $options['consumer_key']];
			$this->session['consumer_key'] = $options['consumer_key'];
			$this->session['consumer_secret'] = $options['consumer_secret'];
			$this->session['signature_methods'] = array('HMAC-SHA1');
			$this->session['server_uri'] = $options['server_uri']; 
			$this->session['request_token_uri'] = $options['request_token_uri'];
			$this->session['authorize_uri'] = $options['authorize_uri'];
			$this->session['access_token_uri'] = $options['access_token_uri']; 
			
		}
		else
		{
			throw new OAuthException2("OAuthStoreSession needs consumer_token and consumer_secret");
		}
	}

	public function getSecretsForVerify ( $consumer_key, $token, $token_type = 'access' ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function getSecretsForSignature ( $uri, $user_id ) 
	{
		return $this->session;
	}

	public function getServerTokenSecrets ( $consumer_key, $token, $token_type, $user_id, $name = '') 	
	{ 
		if ($consumer_key != $this->session['consumer_key']) {
			return array();
		} 
		return array(
			'consumer_key' => $consumer_key,
			'consumer_secret' => $this->session['consumer_secret'],
			'token' => $token,
			'token_secret' => $this->session['token_secret'],
			'token_name' => $name,
			'signature_methods' => $this->session['signature_methods'],
			'server_uri' => $this->session['server_uri'],
			'request_token_uri' => $this->session['request_token_uri'],
			'authorize_uri' => $this->session['authorize_uri'],
			'access_token_uri' => $this->session['access_token_uri'],
			'token_ttl' => 3600,
		);
	}
	
	public function addServerToken ( $consumer_key, $token_type, $token, $token_secret, $user_id, $options = array() ) 
	{
		$this->session['token_type'] = $token_type;
		$this->session['token'] = $token;
		$this->session['token_secret'] = $token_secret;
	}

	public function deleteServer ( $consumer_key, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function getServer( $consumer_key, $user_id, $user_is_admin = false ) { 
		return array( 
			'id' => 0,
			'user_id' => $user_id,
			'consumer_key' => $this->session['consumer_key'],
			'consumer_secret' => $this->session['consumer_secret'],
			'signature_methods' => $this->session['signature_methods'],
			'server_uri' => $this->session['server_uri'],
			'request_token_uri' => $this->session['request_token_uri'],
			'authorize_uri' => $this->session['authorize_uri'],
			'access_token_uri' => $this->session['access_token_uri'],
		);
	}
	
	public function getServerForUri ( $uri, $user_id ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function listServerTokens ( $user_id ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function countServerTokens ( $consumer_key ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function getServerToken ( $consumer_key, $token, $user_id ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function deleteServerToken ( $consumer_key, $token, $user_id, $user_is_admin = false ) {
		// TODO 
	}

	public function setServerTokenTtl ( $consumer_key, $token, $token_ttl )
	{
		//This method just needs to exist. It doesn't have to do anything!
	}
	
	public function listServers ( $q = '', $user_id ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function updateServer ( $server, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }

	public function updateConsumer ( $consumer, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function deleteConsumer ( $consumer_key, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function getConsumer ( $consumer_key, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function getConsumerStatic () { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }

	public function addConsumerRequestToken ( $consumer_key, $options = array() ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function getConsumerRequestToken ( $token ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function deleteConsumerRequestToken ( $token ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function authorizeConsumerRequestToken ( $token, $user_id, $referrer_host = '' ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function countConsumerAccessTokens ( $consumer_key ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function exchangeConsumerRequestForAccessToken ( $token, $options = array() ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function getConsumerAccessToken ( $token, $user_id ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function deleteConsumerAccessToken ( $token, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function setConsumerAccessTokenTtl ( $token, $ttl ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	
	public function listConsumers ( $user_id ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function listConsumerApplications( $begin = 0, $total = 25 )  { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function listConsumerTokens ( $user_id ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }

	public function checkServerNonce ( $consumer_key, $token, $timestamp, $nonce ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	
	public function addLog ( $keys, $received, $sent, $base_string, $notes, $user_id = null ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	public function listLog ( $options, $user_id ) { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }
	
	public function install () { throw new OAuthException2("OAuthStoreSession doesn't support " . __METHOD__); }		
}

?>