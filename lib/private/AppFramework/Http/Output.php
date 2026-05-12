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
		$maxLen = 7800;
		if (strlen($header) > $maxLen) {
			foreach (['Content-Security-Policy:', 'Feature-Policy:'] as $prefix) {
				if (strncmp($header, $prefix, strlen($prefix)) === 0) {
					$value = ltrim(substr($header, strlen($prefix)));
					$directives = array_filter(array_map('trim', explode(';', $value)));
					$segment = '';
					$first = true;
					foreach ($directives as $directive) {
						$candidate = $segment === '' ? $directive : $segment . ';' . $directive;
						if (strlen($prefix . ' ' . $candidate . ';') > $maxLen && $segment !== '') {
							header($prefix . ' ' . $segment . ';', $first);
							$first = false;
							$segment = $directive;
						} else {
							$segment = $candidate;
						}
					}
					if ($segment !== '') {
						header($prefix . ' ' . $segment . ';', $first);
					}
					return;
				}
			}
		}
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
}
