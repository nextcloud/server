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
	/** @var string */
	private $webRoot;

	/**
	 * @param $webRoot
	 */
	public function __construct($webRoot) {
		$this->webRoot = $webRoot;
	}

	public function setOutput($out) {
		print($out);
	}

	public function setReadfile($path) {
		if (is_resource($path)) {
			$output = fopen('php://output', 'w');
			return stream_copy_to_stream($path, $output) > 0;
		} else {
			return @readfile($path);
		}
	}

	public function setHeader($header) {
		header($header);
	}

	public function setHttpResponseCode($code) {
		http_response_code($code);
	}

	public function getHttpResponseCode() {
		return http_response_code();
	}

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
