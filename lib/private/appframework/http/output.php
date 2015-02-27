<?php
/**
 * @author Bernhard Posselt
 * @copyright 2015 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\AppFramework\Http;

use OCP\AppFramework\Http\IOutput;

/**
 * Very thin wrapper class to make output testable
 */
class Output implements IOutput {

	/**
	 * @param string $out
	 */
	public function setOutput($out) {
		print($out);
	}

	/**
	 * @param string $path
	 *
	 * @return bool false if an error occured
	 */
	public function setReadfile($path) {
		return @readfile($path);
	}

	/**
	 * @param string $header
	 */
	public function setHeader($header) {
		header($header);
	}

	/**
	 * @param int $code sets the http status code
	 */
	public function setHttpResponseCode($code) {
		http_response_code($code);
	}

	/**
	 * @return int returns the current http response code
	 */
	public function getHttpResponseCode() {
		return http_response_code();
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param int $expire
	 * @param string $path
	 * @param string $domain
	 * @param bool $secure
	 * @param bool $httponly
	 */
	public function setCookie($name, $value, $expire, $path, $domain, $secure, $httponly) {
		setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
	}

}
