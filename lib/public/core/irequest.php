<?php
/**
 * Created by JetBrains PhpStorm.
 * User: deepdiver
 * Date: 20.08.13
 * Time: 16:15
 * To change this template use File | Settings | File Templates.
 */

namespace OCP\Core;


interface IRequest {

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
	 * (as GET or POST) or throuh the URL by the route
	 * @return array the array with all parameters
	 */
	public function getParams();

	/**
	 * Returns the method of the request
	 * @return string the method of the request (POST, GET, etc)
	 */
	public function getMethod();

	/**
	 * Shortcut for accessing an uploaded file through the $_FILES array
	 * @param string $key the key that will be taken from the $_FILES array
	 * @return array the file in the $_FILES element
	 */
	public function getUploadedFile($key);


	/**
	 * Shortcut for getting env variables
	 * @param string $key the key that will be taken from the $_ENV array
	 * @return array the value in the $_ENV element
	 */
	public function getEnv($key);


	/**
	 * Shortcut for getting session variables
	 * @param string $key the key that will be taken from the $_SESSION array
	 * @return array the value in the $_SESSION element
	 */
	function getSession($key);


	/**
	 * Shortcut for getting cookie variables
	 * @param string $key the key that will be taken from the $_COOKIE array
	 * @return array the value in the $_COOKIE element
	 */
	function getCookie($key);


	/**
	 * Returns the request body content.
	 *
	 * @param Boolean $asResource If true, a resource will be returned
	 *
	 * @return string|resource The request body content or a resource to read the body stream.
	 *
	 * @throws \LogicException
	 */
	function getContent($asResource = false);
}
