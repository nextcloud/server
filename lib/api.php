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
	
	/**
	 * API Response Codes
	 */
	const RESPOND_UNAUTHORISED = 997;
	const RESPOND_SERVER_ERROR = 996;
	const RESPOND_NOT_FOUND = 998;
	const RESPOND_UNKNOWN_ERROR = 999;
	
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
	 * @param int $authLevel the level of authentication required for the call
	 * @param array $defaults
	 * @param array $requirements
	 */
	public static function register($method, $url, $action, $app, 
				$authLevel = OC_API::USER_AUTH,
				$defaults = array(),
				$requirements = array()) {
		$name = strtolower($method).$url;
		$name = str_replace(array('/', '{', '}'), '_', $name);
		if(!isset(self::$actions[$name])) {
			OC::getRouter()->useCollection('ocs');
			OC::getRouter()->create($name, $url)
				->method($method)
				->action('OC_API', 'call');
			self::$actions[$name] = array();
		}
		self::$actions[$name][] = array('app' => $app, 'action' => $action, 'authlevel' => $authLevel);
	}
	
	/**
	 * handles an api call
	 * @param array $parameters
	 */
	public static function call($parameters) {
		// Prepare the request variables
		if($_SERVER['REQUEST_METHOD'] == 'PUT') {
			parse_str(file_get_contents("php://input"), $parameters['_put']);
		} else if($_SERVER['REQUEST_METHOD'] == 'DELETE') {
			parse_str(file_get_contents("php://input"), $parameters['_delete']);
		}
		$name = $parameters['_route'];
		// Foreach registered action
		$responses = array();
		foreach(self::$actions[$name] as $action) {
			// Check authentication and availability
			if(!self::isAuthorised(self::$actions[$name])) {
				$responses[] = array(
					'app' => $action['app'],
					'response' => new OC_OCS_Result(null, OC_API::RESPOND_UNAUTHORISED, 'Unauthorised'),
					);
				continue;
			}
			if(!is_callable($action['action'])) {
				$responses[] = array(
					'app' => $action['app'],
					'response' => new OC_OCS_Result(null, OC_API::RESPOND_NOT_FOUND, 'Api method not found'),
					);
				continue;
			}
			// Run the action
			$responses[] = array(
				'app' => $action['app'],
				'response' => call_user_func($action['action'], $parameters),
				);
		}
		$response = self::mergeResponses($responses);
		$formats = array('json', 'xml');
		$format = !empty($_GET['format']) && in_array($_GET['format'], $formats) ? $_GET['format'] : 'xml';
		self::respond($response);
		OC_User::logout();
	}
	
	/**
	 * merge the returned result objects into one response
	 * @param array $responses
	 */
	private static function mergeResponses($responses) {
		$response = array();
		// Sort into shipped and thirdparty
		$shipped = array(
			'succeeded' => array(),
			'failed' => array(),
			);
		$thirdparty = array(
			'succeeded' => array(),
			'failed' => array(),
			);

		foreach($responses as $response) {
			if(OC_App::isShipped($response['app']) || ($response['app'] === 'core')) {
				if($response['response']->succeeded()) {
					$shipped['succeeded'][$response['app']] = $response['response'];
				} else {
					$shipped['failed'][$response['app']] = $response['response'];
				}
			} else {
				if($response['response']->succeeded()) {
					$thirdparty['succeeded'][$response['app']] = $response['response'];
				} else {
					$thirdparty['failed'][$response['app']] = $response['response'];
				}
			}
		}
		// Remove any error responses if there is one shipped response that succeeded
		if(!empty($shipped['succeeded'])) {
			$responses = array_merge($shipped['succeeded'], $thirdparty['succeeded']);
		} else if(!empty($shipped['failed'])) {
			// Which shipped response do we use if they all failed?
			// They may have failed for different reasons (different status codes)
			// Which reponse code should we return?
			// Maybe any that are not OC_API::RESPOND_SERVER_ERROR
			$response = $shipped['failed'][0];
			return $response;
		} else {
			// Return the third party failure result
			$response = $thirdparty['failed'][0];
			return $response;
		}
		// Merge the successful responses
		$meta = array();
		$data = array();
		foreach($responses as $app => $response) {
			if(OC_App::isShipped($app)) {
				$data = array_merge_recursive($response->getData(), $data);
			} else {
				$data = array_merge_recursive($data, $response->getData());
			}
		}
		$result = new OC_OCS_Result($data, 100);
		return $result;
	}
	
	/**
	 * authenticate the api call
	 * @param array $action the action details as supplied to OC_API::register()
	 * @return bool
	 */
	private static function isAuthorised($action) {
		$level = $action['authlevel'];
		switch($level) {
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
				if(!$user) {
					return false;
				} else {
					$subAdmin = OC_SubAdmin::isSubAdmin($user);
					$admin = OC_User::isAdminUser($user);
					if($subAdmin || $admin) {
						return true;
					} else {
						return false;
					}
				}
				break;
			case OC_API::ADMIN_AUTH:
				// Check for admin
				$user = self::loginUser();
				if(!$user) {
					return false;
				} else {
					return OC_User::isAdminUser($user);
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
		$authUser = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
		$authPw = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
		return OC_User::login($authUser, $authPw) ? $authUser : false;
	}
	
	/**
	 * respond to a call
	 * @param OC_OCS_Result $result
	 * @param string $format the format xml|json
	 */
	private static function respond($result, $format='xml') {
		// Send 401 headers if unauthorised
		if($result->getStatusCode() === self::RESPOND_UNAUTHORISED) {
			header('WWW-Authenticate: Basic realm="Authorisation Required"');
			header('HTTP/1.0 401 Unauthorized');
		}
		$response = array(
			'ocs' => array(
				'meta' => $result->getMeta(),
				'data' => $result->getData(),
				),
			);
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
		}
	}

	private static function toXML($array, $writer) {
		foreach($array as $k => $v) {
			if ($k[0] === '@') {
				$writer->writeAttribute(substr($k, 1), $v);
				continue;
			} else if (is_numeric($k)) {
				$k = 'element';
			}
			if(is_array($v)) {
				$writer->startElement($k);
				self::toXML($v, $writer);
				$writer->endElement();
			} else {
				$writer->writeElement($k, $v);
			}
		}
	}
	
}
