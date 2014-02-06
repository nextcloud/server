<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
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
	* Encode and print $data in JSON format
	* @param array $data The data to use
	* @param string $setContentType the optional content type
	* @return string json formatted string.
	*/
	public static function encodedPrint( $data, $setContentType=true ) {
		return(\OC_JSON::encodedPrint( $data, $setContentType ));
	}

	/**
	* Check if the user is logged in, send json error msg if not.
	*
	* This method checks if a user is logged in. If not, a json error
	* response will be return and the method will exit from execution
	* of the script.
	* The returned json will be in the format:
	*
	*     {"status":"error","data":{"message":"Authentication error."}}
	*
	* Add this call to the start of all ajax method files that requires
	* an authenticated user.
	*
	* @return string json formatted error string if not authenticated.
	*/
	public static function checkLoggedIn() {
		return(\OC_JSON::checkLoggedIn());
	}

	/**
	* Check an ajax get/post call if the request token is valid.
	*
	* This method checks for a valid variable 'requesttoken' in $_GET,
	* $_POST and $_SERVER. If a valid token is not found, a json error
	* response will be return and the method will exit from execution
	* of the script.
	* The returned json will be in the format:
	*
	*     {"status":"error","data":{"message":"Token expired. Please reload page."}}
	*
	* Add this call to the start of all ajax method files that creates,
	* updates or deletes anything.
	* In cases where you e.g. use an ajax call to load a dialog containing
	* a submittable form, you will need to add the requesttoken first as a
	* parameter to the ajax call, then assign it to the template and finally
	* add a hidden input field also named 'requesttoken' containing the value.
	*
	* @return \json|null json formatted error string if not valid.
	*/
	public static function callCheck() {
		return(\OC_JSON::callCheck());
	}

	/**
	* Send json success msg
	*
	* Return a json success message with optional extra data.
	* @see OCP\JSON::error()		for the format to use.
	*
	* @param array $data The data to use
	* @return string json formatted string.
	*/
	public static function success( $data = array() ) {
		return(\OC_JSON::success( $data ));
	}

	/**
	* Send json error msg
	*
	* Return a json error message with optional extra data for
	* error message or app specific data.
	*
	* Example use:
	*
	*     $id = [some value]
	*     OCP\JSON::error(array('data':array('message':'An error happened', 'id': $id)));
	*
	* Will return the json formatted string:
	*
	*     {"status":"error","data":{"message":"An error happened", "id":[some value]}}
	*
	* @param array $data The data to use
	* @return string json formatted error string.
	*/
	public static function error( $data = array() ) {
		return(\OC_JSON::error( $data ));
	}

	/**
	* Set Content-Type header to jsonrequest
	* @param array $type The contwnt type header
	* @return string json formatted string.
	*/
	public static function setContentTypeHeader( $type='application/json' ) {
		return(\OC_JSON::setContentTypeHeader( $type ));
	}

	/**
	* Check if the App is enabled and send JSON error message instead
	*
	* This method checks if a specific app is enabled. If not, a json error
	* response will be return and the method will exit from execution
	* of the script.
	* The returned json will be in the format:
	*
	*     {"status":"error","data":{"message":"Application is not enabled."}}
	*
	* Add this call to the start of all ajax method files that requires
	* a specific app to be enabled.
	*
	* @param string $app The app to check
	* @return string json formatted string if not enabled.
	*/
	public static function checkAppEnabled( $app ) {
		return(\OC_JSON::checkAppEnabled( $app ));
	}

	/**
	* Check if the user is a admin, send json error msg if not
	*
	* This method checks if the current user has admin rights. If not, a json error
	* response will be return and the method will exit from execution
	* of the script.
	* The returned json will be in the format:
	*
	*     {"status":"error","data":{"message":"Authentication error."}}
	*
	* Add this call to the start of all ajax method files that requires
	* administrative rights.
	*
	* @return string json formatted string if not admin user.
	*/
	public static function checkAdminUser() {
		\OC_JSON::checkAdminUser();
	}

	/**
	 * Encode JSON
	 * @param array $data
	 */
	public static function encode($data) {
		return(\OC_JSON::encode($data));
	}

	/**
	 * Check is a given user exists - send json error msg if not
	 * @param string $user
	 */
	public static function checkUserExists($user) {
		\OC_JSON::checkUserExists($user);
	}
}
