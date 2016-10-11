<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

/**
 * Public interface of ownCloud for apps to use.
 * JSON Class
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * This class provides convenient functions to generate and send JSON data. Useful for Ajax calls
 * @deprecated 8.1.0 Use a AppFramework JSONResponse instead
 */
class JSON {
	/**
	 * Encode and print $data in JSON format
	 * @param array $data The data to use
	 * @param bool $setContentType the optional content type
	 * @deprecated 8.1.0 Use a AppFramework JSONResponse instead
	 */
	public static function encodedPrint( $data, $setContentType=true ) {
		\OC_JSON::encodedPrint($data, $setContentType);
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
	 * @deprecated 8.1.0 Use annotation based ACLs from the AppFramework instead
	 */
	public static function checkLoggedIn() {
		\OC_JSON::checkLoggedIn();
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
	 * @deprecated 8.1.0 Use annotation based CSRF checks from the AppFramework instead
	 */
	public static function callCheck() {
		\OC_JSON::callCheck();
	}

	/**
	 * Send json success msg
	 *
	 * Return a json success message with optional extra data.
	 * @see OCP\JSON::error()		for the format to use.
	 *
	 * @param array $data The data to use
	 * @return string json formatted string.
	 * @deprecated 8.1.0 Use a AppFramework JSONResponse instead
	 */
	public static function success( $data = array() ) {
		\OC_JSON::success($data);
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
	 * @deprecated 8.1.0 Use a AppFramework JSONResponse instead
	 */
	public static function error( $data = array() ) {
		\OC_JSON::error( $data );
	}

	/**
	 * Set Content-Type header to jsonrequest
	 * @param string $type The content type header
	 * @deprecated 8.1.0 Use a AppFramework JSONResponse instead
	 */
	public static function setContentTypeHeader( $type='application/json' ) {
		\OC_JSON::setContentTypeHeader($type);
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
	 * @deprecated 8.1.0 Use the AppFramework instead. It will automatically check if the app is enabled.
	 */
	public static function checkAppEnabled( $app ) {
		\OC_JSON::checkAppEnabled($app);
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
	 * @deprecated 8.1.0 Use annotation based ACLs from the AppFramework instead
	 */
	public static function checkAdminUser() {
		\OC_JSON::checkAdminUser();
	}

	/**
	 * Encode JSON
	 * @param array $data
	 * @return string
	 * @deprecated 8.1.0 Use a AppFramework JSONResponse instead
	 */
	public static function encode($data) {
		return \OC_JSON::encode($data);
	}

	/**
	 * Check is a given user exists - send json error msg if not
	 * @param string $user
	 * @deprecated 8.1.0 Use a AppFramework JSONResponse instead
	 */
	public static function checkUserExists($user) {
		\OC_JSON::checkUserExists($user);
	}
}
