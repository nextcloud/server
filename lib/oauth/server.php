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

	public function fetch_request_token(&$request) {
		$this->get_version($request);
		$consumer = $this->get_consumer($request);
		$this->check_signature($request, $consumer, null);
		$callback = $request->get_parameter('oauth_callback');
		$scope = $request->get_parameter('scope');
		// TODO Validate scopes
		return $this->data_store->new_request_token($consumer, $scope, $callback);
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
	
}