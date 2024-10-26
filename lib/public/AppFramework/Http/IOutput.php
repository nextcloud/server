<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework\Http;

/**
 * Very thin wrapper class to make output testable
 * @since 8.1.0
 */
interface IOutput {
	/**
	 * @param string $out
	 * @since 8.1.0
	 */
	public function setOutput($out);

	/**
	 * @param string|resource $path or file handle
	 *
	 * @return bool false if an error occurred
	 * @since 8.1.0
	 */
	public function setReadfile($path);

	/**
	 * @param string $header
	 * @since 8.1.0
	 */
	public function setHeader($header);

	/**
	 * @return int returns the current http response code
	 * @since 8.1.0
	 */
	public function getHttpResponseCode();

	/**
	 * @param int $code sets the http status code
	 * @since 8.1.0
	 */
	public function setHttpResponseCode($code);

	/**
	 * @param string $name
	 * @param string $value
	 * @param int $expire
	 * @param string $path
	 * @param string $domain
	 * @param bool $secure
	 * @param bool $httpOnly
	 * @param string $sameSite (added in 20)
	 * @since 8.1.0
	 */
	public function setCookie($name, $value, $expire, $path, $domain, $secure, $httpOnly, $sameSite = 'Lax');
}
