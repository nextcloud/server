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

class JSON {



	/**
	* Encode and print $data in JSON format
	*/
	public static function encodedPrint($data,$setContentType=true){
		return(\OC_JSON::encodedPrint($data,$setContentType));
	}

	/**
	* Check if the user is logged in, send json error msg if not
	*/
	public static function checkLoggedIn(){
		return(\OC_JSON::checkLoggedIn());
	}



	/**
	* Send json success msg
	*/
	public static function success($data = array()){
		return(\OC_JSON::success($data));
	}


	/**
	* Send json error msg
	*/
	public static function error($data = array()){
		return(\OC_JSON::error($data));
	}


	/**
	 * set Content-Type header to jsonrequest
	 */
	public static function setContentTypeHeader($type='application/json'){
		return(\OC_JSON::setContentTypeHeader($type));
	}


	/**
	* Check if the app is enabled, send json error msg if not
	*/
	public static function checkAppEnabled($app){
		return(\OC_JSON::checkAppEnabled($app));
	}


	/**
	* Check if the user is a admin, send json error msg if not
	*/
	public static function checkAdminUser(){
		return(\OC_JSON::checkAdminUser());
	}

}

?>
