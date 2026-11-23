<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Http\Client;

use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\StreamWrapper;
use OCP\Http\Client\IResponse;
use Psr\Http\Message\ResponseInterface;

class Response implements IResponse {
	public function __construct(
		private ResponseInterface $response,
		private bool $stream = false,
	) {
	}

	#[\Override]
	public function getBody() {
		$body = $this->response->getBody();
		if (!$this->stream) {
			return $body->getContents();
		}
		if (!$body instanceof Stream) {
			$resource = StreamWrapper::getResource($body);
			stream_set_chunk_size($resource, 64 * 1024);
			// Only the resource keeps the body, so closing it ends a streamed transfer
			$body = new Stream($resource);
			$this->response = $this->response->withBody($body);
		}
		return $body->detach();
	}

	#[\Override]
	public function getStatusCode(): int {
		return $this->response->getStatusCode();
	}

	#[\Override]
	public function getHeader(string $key): string {
		$headers = $this->response->getHeader($key);

		if (count($headers) === 0) {
			return '';
		}

		return $headers[0];
	}

	#[\Override]
	public function getHeaders(): array {
		return $this->response->getHeaders();
	}
}
