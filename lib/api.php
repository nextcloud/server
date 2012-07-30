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
	* api actions
	*/
	protected static $actions = array();
	
	/**
	* registers an api call
	* @param string $method the http method
	* @param string $url the url to match
	* @param callable $action the function to run
	* @param string $app the id of the app registering the call
	*/
	public static function register($method, $url, $action, $app){
		$name = strtolower($method).$url;
		if(!isset(self::$actions[$name])){
			OC_Router::create($name, $url.'.{format}')
				->action('OC_API', 'call');
			self::$actions[$name] = array();
		}
		self::$actions[$name][] = array('app' => $app, 'action' => $action);
	}
	
	/**
	* handles an api call
	* @param array $parameters
	*/
	public static function call($parameters){
		
		// Get the routes
		self::loadRoutes();
		
		$name = $parameters['_name'];
		$response = array();
		// Loop through registered actions
		foreach(self::$actions[$name] as $action){
			if(is_callable($action['action'])){
				$action_response = call_user_func($action['action'], $parameters);
				if(is_array($action_response)){
					// Merge with previous
					$response = array_merge($response, $action_response);
				} else {
					// TODO - Something failed, do we return an error code, depends on other action responses
				}
			} else {
				// Action not callable
				// log
				// TODO - Depending on other action responses, do we return a 501?
			}
		}
		// Send the response
		if(isset($parameters['_format'])){
			self::respond($response, $parameters['_format']);
		} else {
			self::respond($response);
		}
	}
	
	/**
	 * loads the api routes
	 */
	private static function loadRoutes(){
		// TODO cache
		foreach(OC_APP::getEnabledApps() as $app){
			$file = OC_App::getAppPath($app).'/appinfo/routes.php';
			if(file_exists($file)){
				require_once($file);
			}
		}
		// include core routes
		require_once(OC::$SERVERROOT.'ocs/routes.php');
	}
	
	/**
	* respond to a call
	* @param int|array $response the response
	* @param string $format the format xml|json
	*/
	private function respond($response, $format='json'){
		// TODO respond in the correct format
	}
	
	}