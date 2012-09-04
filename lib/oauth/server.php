<?php
/**
* ownCloud
*
* @author Tom Needham
* @author Michael Gapczynski
* @copyright 2012 Tom Needham tom@owncloud.com
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
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

require_once(OC::$THIRDPARTYROOT.'/3rdparty/OAuth/OAuth.php');

class OC_OAuth_Server extends OAuthServer {

	/** 
	 * sets up the server object
	 */
	public static function init(){
		$server = new OC_OAuth_Server(new OC_OAuth_Store());
		$server->add_signature_method(new OAuthSignatureMethod_HMAC_SHA1());
		return $server;
	}

	public function get_request_token(&$request){
		// Check the signature
		$token = $this->fetch_request_token($request);
		$scopes = $request->get_parameter('scopes');
		// Add scopes to request token
		$this->saveScopes($token, $scopes);
		
		return $token;
	}
	
	public function saveScopes($token, $scopes){
		$query = OC_DB::prepare("INSERT INTO `*PREFIX*oauth_scopes` (`key`, `scopes`) VALUES (?, ?)");
		$result = $query->execute(array($token->key, $scopes));
	}
	
	
	/**
	 * authorises a request token
	 * @param string $request the request token to authorise
	 * @return What does it return?
	 */
	public function authoriseRequestToken(&$request) {
		$this->get_version($request);
		$consumer = $this->get_consumer($request);
		$this->check_signature($request, $consumer, null);
		$token = $this->get_token($request, $consumer, 'request');
		$this->check_signature($request, $consumer, $token);
		return $this->data_store->authorise_request_token($token, $consumer, OC_User::getUser());
	}
	
	/**
	 * checks if request is authorised
	 * TODO distinguish between failures as one is a 400 error and other is 401
	 * @return string|int
	 */
	public static function isAuthorised($scope) {
		try {
			$request = OAuthRequest::from_request();
			//$this->verify_request(); // TODO cannot use $this in static context
			return true;
		} catch (OAuthException $exception) {
			return false;
		}
		// TODO Get user out of token? May have to write own verify_request()
// 		$run = true;
// 		OC_Hook::emit( "OC_User", "pre_login", array( "run" => &$run, "uid" => $user ));
// 		if(!$run){
// 			return false;
// 		}
// 		OC_User::setUserId($user);
// 		OC_Hook::emit( "OC_User", "post_login", array( "uid" => $user ));
// 		return $user;
	}
	
	/**
	 * registers a consumer with the ownCloud Instance
	 * @param string $name the name of the external app
	 * @param string $url the url to find out more info on the external app
	 * @param string $callbacksuccess the url to redirect to after autorisation success
	 * @param string $callbackfail the url to redirect to if the user does not authorise the application
	 * @return false|OAuthConsumer object
	 */
	static function register_consumer($name, $url, $callbacksuccess=null, $callbackfail=null){
		// TODO validation
		// Check callback url is outside of ownCloud for security
		// Generate key and secret
		$key = sha1(md5(uniqid(rand(), true)));
		$secret = sha1(md5(uniqid(rand(), true)));
		$query = OC_DB::prepare("INSERT INTO `*PREFIX*oauth_consumers` (`key`, `secret`, `name`, `url`, `callback_success`, `callback_fail`) VALUES (?, ?, ?, ?, ?, ?)");
		$result = $query->execute(array($key, $secret, $name, $url, $callbacksuccess, $callbackfail));
		return new OAuthConsumer($key, $secret, $callbacksuccess);
	}
	
}