<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Http;

use OCP\AppFramework\Http\IOutput;

/**
 * Very thin wrapper class to make output testable
 */
class Output implements IOutput {
	public function __construct(
		private string $webRoot,
	) {
	}

	/**
	 * @param string $out
	 */
	public function setOutput($out) {
		print($out);
	}

	/**
	 * @param string|resource $path or file handle
	 *
	 * @return bool false if an error occurred
	 */
	public function setReadfile($path) {
		if (is_resource($path)) {
			$output = fopen('php://output', 'w');
			return stream_copy_to_stream($path, $output) > 0;
		} else {
			return @readfile($path);
		}
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
	 * @param bool $httpOnly
	 */
	public function setCookie($name, $value, $expire, $path, $domain, $secure, $httpOnly, $sameSite = 'Lax') {
		$path = $this->webRoot ? : '/';

		setcookie($name, $value, [
			'expires' => $expire,
			'path' => $path,
			'domain' => $domain,
			'secure' => $secure,
			'httponly' => $httpOnly,
			'samesite' => $sameSite
		]);
	}
}
