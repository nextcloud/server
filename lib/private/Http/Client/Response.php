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

class Response implements IResponse {
	private ResponseInterface $response;

	public function __construct(
		ResponseInterface $response,
		private bool $stream = false,
	) {
		$this->response = $response;
	}

	public function getBody() {
		return $this->stream
			? $this->response->getBody()->detach()
			:$this->response->getBody()->getContents();
	}

	public function getStatusCode(): int {
		return $this->response->getStatusCode();
	}

	public function getHeader(string $key): string {
		$headers = $this->response->getHeader($key);

		if (count($headers) === 0) {
			return '';
		}

		return $headers[0];
	}

	public function getHeaders(): array {
		return $this->response->getHeaders();
	}
}
