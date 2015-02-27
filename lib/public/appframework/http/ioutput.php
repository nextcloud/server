<?php
/**
 * @author Bernhard Posselt
 * @copyright 2015 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\AppFramework\Http;


/**
 * Very thin wrapper class to make output testable
 */
interface IOutput {

	/**
	 * @param string $out
	 */
	public function setOutput($out);

	/**
	 * @param string $path
	 *
	 * @return bool false if an error occured
	 */
	public function setReadfile($path);

	/**
	 * @param string $header
	 */
	public function setHeader($header);

	/**
	 * @return int returns the current http response code
	 */
	public function getHttpResponseCode();

	/**
	 * @param int $code sets the http status code
	 */
	public function setHttpResponseCode($code);

	/**
	 * @param string $name
	 * @param string $value
	 * @param int $expire
	 * @param string $path
	 * @param string $domain
	 * @param bool $secure
	 * @param bool $httponly
	 */
	public function setCookie($name, $value, $expire, $path, $domain, $secure, $httponly);

}
