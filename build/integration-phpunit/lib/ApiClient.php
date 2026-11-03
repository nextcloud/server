<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NextcloudIntegration;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP client for the Nextcloud instance under test.
 *
 * A response is returned for every status code: 4xx and 5xx do not raise an
 * exception, so tests assert on the status instead of catching one.
 *
 * Instances are immutable; {@see self::asUser()} returns a new client rather
 * than changing the identity of an existing one.
 */
final class ApiClient {
	private readonly Client $client;

	/**
	 * @param string $baseUrl Server root without a trailing slash, e.g. "http://localhost:8080"
	 * @param ?array{0: string, 1: string} $auth Basic auth credentials, or null for an anonymous client
	 */
	public function __construct(
		private readonly string $baseUrl,
		private readonly ?array $auth = null,
	) {
		$this->client = new Client();
	}

	public function asUser(string $userId, string $password): self {
		return new self($this->baseUrl, [$userId, $password]);
	}

	/**
	 * @param string $path Path relative to the server root, e.g. "/index.php/apps/testing/anonProtected"
	 * @param array<string, mixed> $options Guzzle request options
	 */
	public function request(string $method, string $path, array $options = []): ResponseInterface {
		$options['http_errors'] = false;
		$options['headers']['OCS-APIREQUEST'] = 'true';
		if ($this->auth !== null) {
			$options['auth'] = $this->auth;
		}

		return $this->client->request($method, $this->baseUrl . $path, $options);
	}

	/**
	 * @param string $path Path below the OCS entry point, e.g. "/cloud/users"
	 * @param array<string, mixed> $options Guzzle request options
	 * @param int $version OCS API version, defaults to 2 because it maps OCS statuses onto HTTP statuses
	 */
	public function ocs(string $method, string $path, array $options = [], int $version = 2): ResponseInterface {
		return $this->request($method, "/ocs/v{$version}.php" . $path, $options);
	}
}
