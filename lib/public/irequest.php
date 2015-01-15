<?php
/**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2013 Thomas Müller deepdiver@owncloud.com
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
 * Request interface
 *
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * This interface provides an immutable object with with accessors to
 * request variables and headers.
 *
 * Access request variables by method and name.
 *
 * Examples:
 *
 * $request->post['myvar']; // Only look for POST variables
 * $request->myvar; or $request->{'myvar'}; or $request->{$myvar}
 * Looks in the combined GET, POST and urlParams array.
 *
 * If you access e.g. ->post but the current HTTP request method
 * is GET a \LogicException will be thrown.
 *
 * NOTE:
 * - When accessing ->put a stream resource is returned and the accessor
 *   will return false on subsequent access to ->put or ->patch.
 * - When accessing ->patch and the Content-Type is either application/json
 *   or application/x-www-form-urlencoded (most cases) it will act like ->get
 *   and ->post and return an array. Otherwise the raw data will be returned.
 *
 * @property-read string[] $server
 * @property-read string[] $urlParams
 */
interface IRequest {

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	function getHeader($name);

	/**
	 * Lets you access post and get parameters by the index
	 * In case of json requests the encoded json body is accessed
	 *
	 * @param string $key the key which you want to access in the URL Parameter
	 *                     placeholder, $_POST or $_GET array.
	 *                     The priority how they're returned is the following:
	 *                     1. URL parameters
	 *                     2. POST parameters
	 *                     3. GET parameters
	 * @param mixed $default If the key is not found, this value will be returned
	 * @return mixed the content of the array
	 */
	public function getParam($key, $default = null);


	/**
	 * Returns all params that were received, be it from the request
	 *
	 * (as GET or POST) or through the URL by the route
	 * @return array the array with all parameters
	 */
	public function getParams();

	/**
	 * Returns the method of the request
	 *
	 * @return string the method of the request (POST, GET, etc)
	 */
	public function getMethod();

	/**
	 * Shortcut for accessing an uploaded file through the $_FILES array
	 *
	 * @param string $key the key that will be taken from the $_FILES array
	 * @return array the file in the $_FILES element
	 */
	public function getUploadedFile($key);


	/**
	 * Shortcut for getting env variables
	 *
	 * @param string $key the key that will be taken from the $_ENV array
	 * @return array the value in the $_ENV element
	 */
	public function getEnv($key);


	/**
	 * Shortcut for getting cookie variables
	 *
	 * @param string $key the key that will be taken from the $_COOKIE array
	 * @return array the value in the $_COOKIE element
	 */
	function getCookie($key);


	/**
	 * Checks if the CSRF check was correct
	 * @return bool true if CSRF check passed
	 */
	public function passesCSRFCheck();
}
