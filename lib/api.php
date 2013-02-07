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
		self::$actions[$name] = array('app' => $app, 'action' => $action, 'authlevel' => $authLevel);
	}

	/**
	 * handles an api call
	 * @param array $parameters
	 */
	public static function call($parameters) {
		// Prepare the request variables
		if($_SERVER['REQUEST_METHOD'] == 'PUT') {
			parse_str(file_get_contents("php://input"), $parameters['_put']);
		} else if($_SERVER['REQUEST_METHOD'] == 'DELETE'){
			parse_str(file_get_contents("php://input"), $parameters['_delete']);
		}
		$name = $parameters['_route'];
		// Check authentication and availability
		if(self::isAuthorised(self::$actions[$name])) {
			if(is_callable(self::$actions[$name]['action'])) {
				$response = call_user_func(self::$actions[$name]['action'], $parameters);
				if(!($response instanceof OC_OCS_Result)) {
					$response = new OC_OCS_Result(null, 996, 'Internal Server Error');
				}
			} else {
				$response = new OC_OCS_Result(null, 998, 'Api method not found');
			}
		} else {
			header('WWW-Authenticate: Basic realm="Authorization Required"');
			header('HTTP/1.0 401 Unauthorized');
			$response = new OC_OCS_Result(null, 997, 'Unauthorised');
		}
		// Send the response
		$formats = array('json', 'xml');
		$format = !empty($_GET['format']) && in_array($_GET['format'], $formats) ? $_GET['format'] : 'xml';
		self::respond($response, $format);
		// logout the user to be stateless
		OC_User::logout();
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
					if($subAdmin) {
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
	 * @param int|array $result the result from the api method
	 * @param string $format the format xml|json
	 */
	private static function respond($result, $format='xml') {
		$response = array('ocs' => $result->getResult());
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
