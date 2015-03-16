<?php
/**
 * Copyright (c) 2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Http\Client;

/**
 * Interface IResponse
 *
 * @package OCP\Http
 */
interface IResponse {
	/**
	 * @return string
	 */
	public function getBody();

	/**
	 * @return int
	 */
	public function getStatusCode();

	/**
	 * @param $key
	 * @return string
	 */
	public function getHeader($key);

	/**
	 * @return array
	 */
	public function getHeaders();
}
