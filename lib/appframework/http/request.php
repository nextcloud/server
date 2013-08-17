<?php
/**
 * ownCloud - Request
 *
 * @author Thomas Tanghus
 * @copyright 2013 Thomas Tanghus (thomas@tanghus.net)
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

/**
 * Class for accessing variables in the request.
 * This class provides an immutable object with request variables.
 */

class Request implements \ArrayAccess, \Countable {

	protected $items = array();
	protected $allowedKeys = array(
		'get', 
		'post', 
		'files', 
		'server', 
		'env', 
		'session', 
		'cookies', 
		'urlParams', 
		'params', 
		'parameters', 
		'method'
	);

	/**
	 * @param array $vars An associative array with the following optional values:
	 * @param array 'params' the parsed json array
	 * @param array 'urlParams' the parameters which were matched from the URL
	 * @param array 'get' the $_GET array
	 * @param array 'post' the $_POST array
	 * @param array 'files' the $_FILES array
	 * @param array 'server' the $_SERVER array
	 * @param array 'env' the $_ENV array
	 * @param array 'session' the $_SESSION array
	 * @param array 'cookies' the $_COOKIE array
	 * @param string 'method' the request method (GET, POST etc)
	 * @see http://www.php.net/manual/en/reserved.variables.php
	 */
	public function __construct(array $vars=array()) {

		foreach($this->allowedKeys as $name) {
			$this->items[$name] = isset($vars[$name]) 
				? $vars[$name] 
				: array();
		}

		$this->items['parameters'] = array_merge(
			$this->items['params'],
			$this->items['get'],
			$this->items['post'],
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
	* @return string|null
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
	* if($request->method !== 'POST') {
	* 	throw new Exception('This function can only be invoked using POST');
	* }
	*
	* @param string $name The key to look for.
	* @return mixed|null
	*/
	public function __get($name) {
		switch($name) {
			case 'get':
			case 'post':
			case 'files':
			case 'server':
			case 'env':
			case 'session':
			case 'cookies':
			case 'parameters':
			case 'params':
			case 'urlParams':
				return isset($this->items[$name])
					? $this->items[$name]
					: null;
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

}
