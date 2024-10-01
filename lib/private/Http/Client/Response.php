<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Http\Client;

use OCP\Http\Client\IResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Response
 *
 * @package OC\Http
 */
class Response implements IResponse {
	/** @var ResponseInterface */
	private $response;

	/**
	 * @var bool
	 */
	private $stream;

	/**
	 * @param ResponseInterface $response
	 * @param bool $stream
	 */
	public function __construct(ResponseInterface $response, $stream = false) {
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
		$headers = $this->response->getHeader($key);

		if (count($headers) === 0) {
			return '';
		}

		return $headers[0];
	}

	/**
	 * @return array
	 */
	public function getHeaders(): array {
		return $this->response->getHeaders();
	}
}
