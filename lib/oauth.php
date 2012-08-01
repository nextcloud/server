<?php
/**
* ownCloud
*
* @author Tom Needham 
* @copyright 2012 Tom Needham tom@owncloud.com 
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* You should have received a copy of the GNU Affero General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

class OC_OAuth {
	
	/**
	 * the oauth-php server object
	 */
	private static $server;
	
	/**
	 * the oauth-php oauthstore object
	 */
	private static $store;
	
	/**
	 * initialises the OAuth store and server
	 */
	private static function init(){
		// Include the libraries
		require_once(OC::$THIRDPARTYROOT.'/3rdparty/oauth-php/library/OAuthServer.php');
		require_once(OC::$THIRDPARTYROOT.'/3rdparty/oauth-php/library/OAuthStore.php');
		require_once(OC::$THIRDPARTYROOT.'/3rdparty/oauth-php/library/OAuthRequestVerifier.php');
		// Initialise the OAuth store
		self::$store = OAuthStore::instance('Session');
		// Create the server object
		self::$server = new OAuthServer();
	}
	
	/**
	 * gets a request token
	 * TODO save the scopes in the database with this token
	 */
	public static function getRequestToken(){
		self::init();
		self::$server->requestToken();
	}
	
	/**
	 * get the scopes requested by this token
	 * @param string $requesttoken
	 * @return array scopes
	 */
	public static function getScopes($requesttoken){
		// TODO
	}
	
	/**
	 * exchanges authorised request token for access token
	 */
	public static function getAccessToken(){
		self::init();
		self::$server->accessToken();
	}
	
	/**
	 * registers a new consumer
	 * @param array $details consumer details, keys requester_name and requester_email required
	 * @param string $user the owncloud user adding the consumer
	 * @return array the consumers details including secret and key
	 */
	public static function registerConsumer($details, $user){
		self::init();
		$consumer = self::$store->updateConsumer($details, $user, OC_Group::inGroup($user, 'admin'));
		return $consumer;	
	}
	
	/**
	 * gets a list of consumers
	 * @param string $user
	 */
	public static function getConsumers($user=null){
		$user = is_null($user) ? OC_User::getUser() : $user;
		return self::$store->listConsumers($user);
	}
	
	/**
	 * authorises a request token - redirects to callback
	 * @param string $user
	 * @param bool $authorised
	 */
	public static function authoriseToken($user=null){
		$user = is_null($user) ? OC_User::getUser() : $user;
		self::$server->authorizeVerify();
		self::$server->authorize($authorised, $user);
	}
	
	/**
	 * checks if request is authorised
	 * TODO distinguish between failures as one is a 400 error and other is 401
	 * @return string|int
	 */
	public static function isAuthorised(){
		self::init();
		if(OAuthRequestVerifier::requestIsSigned()){
			try{
				$req = new OAuthRequestVerifier();
				$user = $req->verify();
				$run = true;
				OC_Hook::emit( "OC_User", "pre_login", array( "run" => &$run, "uid" => $user ));
				if(!$run){
					return false;
				}
				OC_User::setUserId($user);
				OC_Hook::emit( "OC_User", "post_login", array( "uid" => $user ));
				return $user;
			} catch(OAuthException $e) {
				// 401 Unauthorised
				return false;
			}
		} else {
			// Bad request
			return false;
		}
	}
	
}