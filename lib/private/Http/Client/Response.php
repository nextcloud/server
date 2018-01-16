<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
 *
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
use GuzzleHttp\Message\ResponseInterface as GuzzleResponse;

/**
 * Class Response
 *
 * @package OC\Http
 */
class Response implements IResponse {
	/** @var GuzzleResponse */
	private $response;

	/**
	 * @var bool
	 */
	private $stream;

	/**
	 * @param GuzzleResponse $response
	 * @param bool $stream
	 */
	public function __construct(GuzzleResponse $response, $stream = false) {
		$this->response = $response;
		$this->stream = $stream;
	}

	/**
	 * @return string|resource
	 */
	public function getBody() {
		return $this->stream ?
			$this->response->getBody()->detach():
			$this->response->getBody()->getContents();
	}

	/**
	 * @return int
	 */
	public function getStatusCode(): int {
		return $this->response->getStatusCode();
	}

	/**
	 * @param string $key
	 * @return string
	 */
	public function getHeader(string $key): string {
		return $this->response->getHeader($key);
	}

	/**
	 * @return array
	 */
	public function getHeaders(): array {
		return $this->response->getHeaders();
	}
}
