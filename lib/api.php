<?php
/**
* ownCloud
*
* @author Tom Needham
* @author Michael Gapczynski
* @author Bart Visscher
* @copyright 2012 Tom Needham tom@owncloud.com
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
* @copyright 2012 Bart Visscher bartv@thisnet.nl
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

class OC_API {

	/**
	 * API authentication levels
	 */
	const GUEST_AUTH = 0;
	const USER_AUTH = 1;
	const SUBADMIN_AUTH = 2;
	const ADMIN_AUTH = 3;

	private static $server;

	/**
	 * initialises the OAuth store and server
	 */
	private static function init() {
		self::$server = new OC_OAuth_Server(new OC_OAuth_Store());
	}
	
	/**
	* api actions
	*/
	protected static $actions = array();
	
	/**
	* registers an api call
	* @param string $method the http method
	* @param string $url the url to match
	* @param callable $action the function to run
	* @param string $app the id of the app registering the call
	* @param int $authlevel the level of authentication required for the call
	* @param array $defaults
	* @param array $requirements
	*/
	public static function register($method, $url, $action, $app, 
				$authlevel = OC_API::USER_AUTH,
				$defaults = array(),
				$requirements = array()){
		$name = strtolower($method).$url;
		$name = str_replace(array('/', '{', '}'), '_', $name);
		if(!isset(self::$actions[$name])){
			OC::getRouter()->useCollection('ocs');
			OC::getRouter()->create($name, $url.'.{_format}')
				->method($method)
				->defaults(array('_format' => 'xml') + $defaults)
				->requirements(array('_format' => 'xml|json') + $requirements)
				->action('OC_API', 'call');
			self::$actions[$name] = array();
		}
		self::$actions[$name][] = array('app' => $app, 'action' => $action, 'authlevel' => $authlevel);
	}
	
	/**
	* handles an api call
	* @param array $parameters
	*/
	public static function call($parameters){
		$name = $parameters['_route'];
		// Loop through registered actions
		foreach(self::$actions[$name] as $action){
			$app = $action['app'];
			// Authorsie this call
			if(self::isAuthorised($action)){
				if(is_callable($action['action'])){
					$responses[] = array('app' => $app, 'response' => call_user_func($action['action'], $parameters));
				} else {
					$responses[] = array('app' => $app, 'response' => 501);
				}
			} else {
				$responses[] = array('app' => $app, 'response' => 401);
			}
			
		}
		// Merge the responses
		$response = self::mergeResponses($responses);
		// Send the response
		if(isset($parameters['_format'])){
			self::respond($response, $parameters['_format']);
		} else {
			self::respond($response);
		}
		// logout the user to be stateless
		OC_User::logout();
	}
	
	/**
	 * authenticate the api call
	 * @param array $action the action details as supplied to OC_API::register()
	 * @return bool
	 */
	private static function isAuthorised($action){
		$level = $action['authlevel'];
		switch($level){
			case OC_API::GUEST_AUTH:
				// Anyone can access
				return true;
				break;
			case OC_API::USER_AUTH:
				// User required
				return self::loginUser();
				break;
			case OC_API::SUBADMIN_AUTH:
				// Check for subadmin
				$user = self::loginUser();
				if(!$user){
					return false;
				} else {
					$subadmin = OC_SubAdmin::isSubAdmin($user);
					$admin = OC_Group::inGroup($user, 'admin');
					if($subadmin || $admin){
						return true;
					} else {
						return false;
					}
				}
				break;
			case OC_API::ADMIN_AUTH:
				// Check for admin
				$user = self::loginUser();
				if(!$user){
					return false;
				} else {
					return OC_Group::inGroup($user, 'admin');
				}
				break;
			default:
				// oops looks like invalid level supplied
				return false;
				break;
		}
	} 
	
	/**
	 * http basic auth
	 * @return string|false (username, or false on failure)
	 */
	private static function loginUser(){
		$authuser = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
		$authpw = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
		return OC_User::login($authuser, $authpw) ? $authuser : false;
	}
	
	/**
	 * intelligently merges the different responses
	 * @param array $responses
	 * @return array the final merged response
	 */
	private static function mergeResponses($responses){
		$finalresponse = array(
			'meta' => array(
				'statuscode' => '',
				),
			'data' => array(),
			);
		$numresponses = count($responses);
		
		foreach($responses as $response){
			if(is_int($response['response']) && empty($finalresponse['meta']['statuscode'])){
				$finalresponse['meta']['statuscode'] = $response['response'];
				continue;
			}
			if(is_array($response['response'])){
				// Shipped apps win
				if(OC_App::isShipped($response['app'])){
					$finalresponse['data'] = array_merge_recursive($finalresponse['data'], $response['response']);
				} else {
					$finalresponse['data'] = array_merge_recursive($response['response'], $finalresponse['data']);
				}
				$finalresponse['meta']['statuscode'] = 100;
			}
		}
		//Some tidying up
		if($finalresponse['meta']['statuscode']=='100'){
			$finalresponse['meta']['status'] = 'ok';
		} else {
			$finalresponse['meta']['status'] = 'failure';
		}
		if(empty($finalresponse['data'])){
			unset($finalresponse['data']);
		}
		return array('ocs' => $finalresponse);
	}
	
	/**
	* respond to a call
	* @param int|array $response the response
	* @param string $format the format xml|json
	*/
	private static function respond($response, $format='json'){
		if ($format == 'json') {
			OC_JSON::encodedPrint($response);
		} else if ($format == 'xml') {
			header('Content-type: text/xml; charset=UTF-8');
			$writer = new XMLWriter();
			$writer->openMemory();
			$writer->setIndent( true );
			$writer->startDocument();
			self::toXML($response, $writer);
			$writer->endDocument();
			echo $writer->outputMemory(true);
		} else {
			var_dump($format, $response);
		}
	}

	private static function toXML($array, $writer){
		foreach($array as $k => $v) {
			if (is_numeric($k)) {
				$k = 'element';
			}
			if (is_array($v)) {
				$writer->startElement($k);
				self::toXML($v, $writer);
				$writer->endElement();
			} else {
				$writer->writeElement($k, $v);
			}
		}
	}
	
}
