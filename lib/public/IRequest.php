<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
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
 * @since 6.0.0
 */
interface IRequest {
	/**
	 * @since 9.1.0
	 */
	const USER_AGENT_CLIENT_ANDROID = '/^Mozilla\/5\.0 \(Android\) ownCloud\-android.*$/';

	/**
	 * @since 9.1.0
	 */
	const USER_AGENT_CLIENT_DESKTOP = '/^Mozilla\/5\.0 \([A-Za-z ]+\) (mirall|csyncoC)\/.*$/';

	/**
	 * @since 9.1.0
	 */
	const USER_AGENT_CLIENT_IOS = '/^Mozilla\/5\.0 \(iOS\) (ownCloud|Nextcloud)\-iOS.*$/';

	/**
	 * @param string $name
	 *
	 * @return string
	 * @since 6.0.0
	 */
	public function getHeader($name);

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
	 * @since 6.0.0
	 */
	public function getParam($key, $default = null);


	/**
	 * Returns all params that were received, be it from the request
	 *
	 * (as GET or POST) or through the URL by the route
	 *
	 * @return array the array with all parameters
	 * @since 6.0.0
	 */
	public function getParams();

	/**
	 * Returns the method of the request
	 *
	 * @return string the method of the request (POST, GET, etc)
	 * @since 6.0.0
	 */
	public function getMethod();

	/**
	 * Shortcut for accessing an uploaded file through the $_FILES array
	 *
	 * @param string $key the key that will be taken from the $_FILES array
	 * @return array the file in the $_FILES element
	 * @since 6.0.0
	 */
	public function getUploadedFile($key);


	/**
	 * Shortcut for getting env variables
	 *
	 * @param string $key the key that will be taken from the $_ENV array
	 * @return array the value in the $_ENV element
	 * @since 6.0.0
	 */
	public function getEnv($key);


	/**
	 * Shortcut for getting cookie variables
	 *
	 * @param string $key the key that will be taken from the $_COOKIE array
	 * @return string the value in the $_COOKIE element
	 * @since 6.0.0
	 */
	public function getCookie($key);


	/**
	 * Checks if the CSRF check was correct
	 *
	 * @return bool true if CSRF check passed
	 * @since 6.0.0
	 */
	public function passesCSRFCheck();

	/**
	 * Checks if the strict cookie has been sent with the request if the request
	 * is including any cookies.
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function passesStrictCookieCheck();

	/**
	 * Checks if the lax cookie has been sent with the request if the request
	 * is including any cookies.
	 *
	 * @return bool
	 * @since 9.0.0
	 */
	public function passesLaxCookieCheck();

	/**
	 * Returns an ID for the request, value is not guaranteed to be unique and is mostly meant for logging
	 * If `mod_unique_id` is installed this value will be taken.
	 *
	 * @return string
	 * @since 8.1.0
	 */
	public function getId();

	/**
	 * Returns the remote address, if the connection came from a trusted proxy
	 * and `forwarded_for_headers` has been configured then the IP address
	 * specified in this header will be returned instead.
	 * Do always use this instead of $_SERVER['REMOTE_ADDR']
	 *
	 * @return string IP address
	 * @since 8.1.0
	 */
	public function getRemoteAddress();

	/**
	 * Returns the server protocol. It respects reverse proxy servers and load
	 * balancers.
	 *
	 * @return string Server protocol (http or https)
	 * @since 8.1.0
	 */
	public function getServerProtocol();

	/**
	 * Returns the used HTTP protocol.
	 *
	 * @return string HTTP protocol. HTTP/2, HTTP/1.1 or HTTP/1.0.
	 * @since 8.2.0
	 */
	public function getHttpProtocol();

	/**
	 * Returns the request uri, even if the website uses one or more
	 * reverse proxies
	 *
	 * @return string
	 * @since 8.1.0
	 */
	public function getRequestUri();

	/**
	 * Get raw PathInfo from request (not urldecoded)
	 *
	 * @throws \Exception
	 * @return string Path info
	 * @since 8.1.0
	 */
	public function getRawPathInfo();

	/**
	 * Get PathInfo from request
	 *
	 * @throws \Exception
	 * @return string|false Path info or false when not found
	 * @since 8.1.0
	 */
	public function getPathInfo();

	/**
	 * Returns the script name, even if the website uses one or more
	 * reverse proxies
	 *
	 * @return string the script name
	 * @since 8.1.0
	 */
	public function getScriptName();

	/**
	 * Checks whether the user agent matches a given regex
	 *
	 * @param array $agent array of agent names
	 * @return bool true if at least one of the given agent matches, false otherwise
	 * @since 8.1.0
	 */
	public function isUserAgent(array $agent);

	/**
	 * Returns the unverified server host from the headers without checking
	 * whether it is a trusted domain
	 *
	 * @return string Server host
	 * @since 8.1.0
	 */
	public function getInsecureServerHost();

	/**
	 * Returns the server host from the headers, or the first configured
	 * trusted domain if the host isn't in the trusted list
	 *
	 * @return string Server host
	 * @since 8.1.0
	 */
	public function getServerHost();
}
