<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Http\Client;

use OCP\Http\Client\IResponse;
use GuzzleHttp\Message\Response as GuzzleResponse;

/**
 * Class Response
 *
 * @package OC\Http
 */
class Response implements IResponse {
	/** @var GuzzleResponse */
	private $response;

	/**
	 * @param GuzzleResponse $response
	 */
	public function __construct(GuzzleResponse $response) {
		$this->response = $response;
	}

	/**
	 * @return string
	 */
	public function getBody() {
		return $this->response->getBody()->getContents();
	}

	/**
	 * @return int
	 */
	public function getStatusCode() {
		return $this->response->getStatusCode();
	}

	/**
	 * @param $key
	 * @return string
	 */
	public function getHeader($key) {
		return $this->response->getHeader($key);
	}

	/**
	 * @return array
	 */
	public function getHeaders() {
		return $this->response->getHeaders();
	}
}
