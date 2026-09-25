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
	#[\Override]
	public function setOutput($out) {
		print($out);
	}

	/**
	 * @param string|resource $path or file handle
	 *
	 * @return bool false if an error occurred
	 */
	#[\Override]
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
	#[\Override]
	public function setHeader($header) {
		header($header);
	}

	/**
	 * @param int $code sets the http status code
	 */
	#[\Override]
	public function setHttpResponseCode($code) {
		http_response_code($code);
	}

	/**
	 * @return int returns the current http response code
	 */
	#[\Override]
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
	#[\Override]
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

	#[\Override]
	public function finishRequest(): bool {
		// php-fpm, and the SAPIs aliasing it (recent LiteSpeed, FrankenPHP)
		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
			return true;
		}

		if (function_exists('litespeed_finish_request')) {
			litespeed_finish_request();
			return true;
		}

		// mod_php, cgi, cli, … cannot give the connection back to the web server,
		// so only push out what we have. ob_flush() instead of ob_end_flush() keeps
		// the buffer around for error handling, and is silenced because output
		// handlers are allowed to be non-flushable.
		if (ob_get_level() > 0) {
			@ob_flush();
		}
		flush();

		return false;
	}
}
