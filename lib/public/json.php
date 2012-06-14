<?php
/**
* ownCloud
*
* @author Frank Karlitschek
* @copyright 2010 Frank Karlitschek karlitschek@kde.org
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

/**
 * Public interface of ownCloud for apps to use.
 * JSON Class
 *
 */

// use OCP namespace for all classes that are considered public. 
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * This class provides convinient functions to generate and send JSON data. Usefull for Ajax calls
 */
class JSON {


	/**
	* @brief Encode and print $data in JSON format
	* @param array $data The data to use
	* @param string $setContentType the optional content type
	*/
	public static function encodedPrint( $data, $setContentType=true ){
		return(\OC_JSON::encodedPrint( $data, $setContentType ));
	}


	/**
	* @brief Check if the user is logged in, send json error msg if not
	*/
	public static function checkLoggedIn(){
		return(\OC_JSON::checkLoggedIn());
	}

	/**
	 * @brief Check an ajax get/post call if the request token is valid.
	 * @return json Error msg if not valid.
	 */
	public static function callCheck(){
		return(\OC_JSON::callCheck());
	}

	/**
	* @brief Send json success msg
	* @param array $data The data to use
	*/
	public static function success( $data = array() ){
		return(\OC_JSON::success( $data ));
	}


	/**
	* @brief Send json error msg
	* @param array $data The data to use
	*/
	public static function error( $data = array() ){
		return(\OC_JSON::error( $data ));
	}


	/**
	 * @brief set Content-Type header to jsonrequest
	 * @param array $type The contwnt type header
	 */
	public static function setContentTypeHeader( $type='application/json' ){
		return(\OC_JSON::setContentTypeHeader( $type ));
	}


	/**
	 * @brief Check if the App is enabled and send JSON error message instead
	 * @param string $app The app to check
	 */
	public static function checkAppEnabled( $app ){
		return(\OC_JSON::checkAppEnabled( $app ));
	}


	/**
	* @brief Check if the user is a admin, send json error msg if not
	*/
	public static function checkAdminUser(){
		return(\OC_JSON::checkAdminUser());
	}

}

?>
