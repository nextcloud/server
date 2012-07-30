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
		$name = $parameters['_name'];
		// Loop through registered actions
		foreach(self::$actions[$name] as $action){
			$app = $action['app'];
			if(is_callable($action['action'])){
				$responses[] = array('app' => $app, 'response' => call_user_func($action['action'], $parameters));
			} else {
				$responses[] = array('app' => $app, 'response' => 501);
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
	}
	
	/**
	 * intelligently merges the different responses
	 * @param array $responses
	 * @return array the final merged response
	 */
	private static function mergeResponses($responses){
		$finalresponse = array();
		$numresponses = count($responses);
		
		foreach($responses as $response){
			if(is_int($response) && empty($finalresponse)){
				$finalresponse = $response;
				continue;
			}
			if(is_array($response)){
				// Shipped apps win
				if(OC_App::isShipped($response['app'])){
					$finalresponse = array_merge_recursive($finalresponse, $response);
				} else {
					$finalresponse = array_merge_recursive($response, $finalresponse);
				}
			}
		}

		return $finalresponse;
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