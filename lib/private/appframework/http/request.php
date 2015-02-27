<?php
/**
 * ownCloud - Request
 *
 * @author Thomas Tanghus
 * @author Bernhard Posselt
 * @copyright 2013 Thomas Tanghus (thomas@tanghus.net)
 * @copyright 2014 Bernhard Posselt <dev@bernhard-posselt.com>
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

namespace OC\AppFramework\Http;

use OCP\IRequest;

/**
 * Class for accessing variables in the request.
 * This class provides an immutable object with request variables.
 */

class Request implements \ArrayAccess, \Countable, IRequest {

	protected $inputStream;
	protected $content;
	protected $items = array();
	protected $allowedKeys = array(
		'get',
		'post',
		'files',
		'server',
		'env',
		'cookies',
		'urlParams',
		'parameters',
		'method',
		'requesttoken',
	);
	protected $streamReadInitialized = false;

	/**
	 * @param array $vars An associative array with the following optional values:
	 * @param array 'urlParams' the parameters which were matched from the URL
	 * @param array 'get' the $_GET array
	 * @param array|string 'post' the $_POST array or JSON string
	 * @param array 'files' the $_FILES array
	 * @param array 'server' the $_SERVER array
	 * @param array 'env' the $_ENV array
	 * @param array 'cookies' the $_COOKIE array
	 * @param string 'method' the request method (GET, POST etc)
	 * @param string|false 'requesttoken' the requesttoken or false when not available
	 * @see http://www.php.net/manual/en/reserved.variables.php
	 */
	public function __construct(array $vars=array(), $stream='php://input') {

		$this->inputStream = $stream;
		$this->items['params'] = array();

		if(!array_key_exists('method', $vars)) {
			$vars['method'] = 'GET';
		}

		foreach($this->allowedKeys as $name) {
			$this->items[$name] = isset($vars[$name])
				? $vars[$name]
				: array();
		}

		// 'application/json' must be decoded manually.
		if (strpos($this->getHeader('Content-Type'), 'application/json') !== false) {
			$params = json_decode(file_get_contents($this->inputStream), true);
			if(count($params) > 0) {
				$this->items['params'] = $params;
				if($vars['method'] === 'POST') {
					$this->items['post'] = $params;
				}
			}
		}

		$this->items['parameters'] = array_merge(
			$this->items['get'],
			$this->items['post'],
			$this->items['urlParams'],
			$this->items['params']
		);

	}

	public function setUrlParameters($parameters) {
		$this->items['urlParams'] = $parameters;
		$this->items['parameters'] = array_merge(
			$this->items['parameters'],
			$this->items['urlParams']
		);
	}

	// Countable method.
	public function count() {
		return count(array_keys($this->items['parameters']));
	}

	/**
	* ArrayAccess methods
	*
	* Gives access to the combined GET, POST and urlParams arrays
	*
	* Examples:
	*
	* $var = $request['myvar'];
	*
	* or
	*
	* if(!isset($request['myvar']) {
	* 	// Do something
	* }
	*
	* $request['myvar'] = 'something'; // This throws an exception.
	*
	* @param string $offset The key to lookup
	* @return boolean
	*/
	public function offsetExists($offset) {
		return isset($this->items['parameters'][$offset]);
	}

	/**
	* @see offsetExists
	*/
	public function offsetGet($offset) {
		return isset($this->items['parameters'][$offset])
			? $this->items['parameters'][$offset]
			: null;
	}

	/**
	* @see offsetExists
	*/
	public function offsetSet($offset, $value) {
		throw new \RuntimeException('You cannot change the contents of the request object');
	}

	/**
	* @see offsetExists
	*/
	public function offsetUnset($offset) {
		throw new \RuntimeException('You cannot change the contents of the request object');
	}

	// Magic property accessors
	public function __set($name, $value) {
		throw new \RuntimeException('You cannot change the contents of the request object');
	}

	/**
	* Access request variables by method and name.
	* Examples:
	*
	* $request->post['myvar']; // Only look for POST variables
	* $request->myvar; or $request->{'myvar'}; or $request->{$myvar}
	* Looks in the combined GET, POST and urlParams array.
	*
	* If you access e.g. ->post but the current HTTP request method
	* is GET a \LogicException will be thrown.
	*
	* @param string $name The key to look for.
	* @throws \LogicException
	* @return mixed|null
	*/
	public function __get($name) {
		switch($name) {
			case 'put':
			case 'patch':
			case 'get':
			case 'post':
				if($this->method !== strtoupper($name)) {
					throw new \LogicException(sprintf('%s cannot be accessed in a %s request.', $name, $this->method));
				}
			case 'files':
			case 'server':
			case 'env':
			case 'cookies':
			case 'parameters':
			case 'params':
			case 'urlParams':
				if(in_array($name, array('put', 'patch'))) {
					return $this->getContent($name);
				} else {
					return isset($this->items[$name])
						? $this->items[$name]
						: null;
				}
				break;
			case 'method':
				return $this->items['method'];
				break;
			default;
				return isset($this[$name])
					? $this[$name]
					: null;
				break;
		}
	}


	public function __isset($name) {
		return isset($this->items['parameters'][$name]);
	}


	public function __unset($id) {
		throw new \RunTimeException('You cannot change the contents of the request object');
	}

	/**
	 * Returns the value for a specific http header.
	 *
	 * This method returns null if the header did not exist.
	 *
	 * @param string $name
	 * @return string
	 */
	public function getHeader($name) {

		$name = strtoupper(str_replace(array('-'),array('_'),$name));
		if (isset($this->server['HTTP_' . $name])) {
			return $this->server['HTTP_' . $name];
		}

		// There's a few headers that seem to end up in the top-level
		// server array.
		switch($name) {
			case 'CONTENT_TYPE' :
			case 'CONTENT_LENGTH' :
				if (isset($this->server[$name])) {
					return $this->server[$name];
				}
				break;

		}

		return null;
	}

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
	public function getParam($key, $default = null) {
		$this->initializeStreamParams();
		return isset($this->parameters[$key])
			? $this->parameters[$key]
			: $default;
	}

	/**
	 * Returns all params that were received, be it from the request
	 * (as GET or POST) or throuh the URL by the route
	 * @return array the array with all parameters
	 */
	public function getParams() {
		$this->initializeStreamParams();
		return $this->parameters;
	}

	/**
	 * Workaround for ownCloud 8 to only read the stream-input when parameters
	 * are requested. For the next master release this is removed and implemented
	 * using a different approach.
	 */
	protected function initializeStreamParams() {
		if(
			$this->streamReadInitialized === false &&
			$this->getMethod() !== 'GET' &&
			$this->getMethod() !== 'POST' &&
			strpos($this->getHeader('Content-Type'), 'application/x-www-form-urlencoded') !== false
		) {
			$params = [];
			parse_str(file_get_contents($this->inputStream), $params);
			if(!empty($params)) {
				$this->items['params'] = $params;
				$this->items['parameters'] = array_merge(
					$this->items['parameters'],
					$this->items['params']
				);
			}
		}
		$this->streamReadInitialized = true;
	}

	/**
	 * Returns the method of the request
	 * @return string the method of the request (POST, GET, etc)
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Shortcut for accessing an uploaded file through the $_FILES array
	 * @param string $key the key that will be taken from the $_FILES array
	 * @return array the file in the $_FILES element
	 */
	public function getUploadedFile($key) {
		return isset($this->files[$key]) ? $this->files[$key] : null;
	}

	/**
	 * Shortcut for getting env variables
	 * @param string $key the key that will be taken from the $_ENV array
	 * @return array the value in the $_ENV element
	 */
	public function getEnv($key) {
		return isset($this->env[$key]) ? $this->env[$key] : null;
	}

	/**
	 * Shortcut for getting cookie variables
	 * @param string $key the key that will be taken from the $_COOKIE array
	 * @return array the value in the $_COOKIE element
	 */
	function getCookie($key) {
		return isset($this->cookies[$key]) ? $this->cookies[$key] : null;
	}

	/**
	 * Returns the request body content.
	 *
	 * If the HTTP request method is PUT and the body
	 * not application/x-www-form-urlencoded or application/json a stream
	 * resource is returned, otherwise an array.
	 *
	 * @return array|string|resource The request body content or a resource to read the body stream.
	 *
	 * @throws \LogicException
	 */
	protected function getContent() {
		$this->initializeStreamParams();
		// If the content can't be parsed into an array then return a stream resource.
		if ($this->method === 'PUT'
			&& strpos($this->getHeader('Content-Type'), 'application/x-www-form-urlencoded') === false
			&& strpos($this->getHeader('Content-Type'), 'application/json') === false
		) {
			if ($this->content === false) {
				throw new \LogicException(
					'"put" can only be accessed once if not '
					. 'application/x-www-form-urlencoded or application/json.'
				);
			}
			$this->content = false;
			return fopen($this->inputStream, 'rb');
		} else {
			return $this->parameters;
		}
	}

	/**
	 * Checks if the CSRF check was correct
	 * @return bool true if CSRF check passed
	 * @see OC_Util::callRegister()
	 */
	public function passesCSRFCheck() {
		if($this->items['requesttoken'] === false) {
			return false;
		}

		if (isset($this->items['get']['requesttoken'])) {
			$token = $this->items['get']['requesttoken'];
		} elseif (isset($this->items['post']['requesttoken'])) {
			$token = $this->items['post']['requesttoken'];
		} elseif (isset($this->items['server']['HTTP_REQUESTTOKEN'])) {
			$token = $this->items['server']['HTTP_REQUESTTOKEN'];
		} else {
			//no token found.
			return false;
		}

		// Check if the token is valid
		if($token !== $this->items['requesttoken']) {
			// Not valid
			return false;
		} else {
			// Valid token
			return true;
		}
	}}
