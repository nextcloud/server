<?php
/**
 * Copyright (c) 2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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
