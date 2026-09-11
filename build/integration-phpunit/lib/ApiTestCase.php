<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NextcloudIntegration;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Base class for API integration tests running against a live Nextcloud instance.
 */
abstract class ApiTestCase extends TestCase {
	protected const ADMIN_USER = 'admin';
	protected const ADMIN_PASSWORD = 'admin';

	private static ?ApiClient $guest = null;
	private static ?Occ $occ = null;
	private static ?Users $users = null;

	/**
	 * Server root of the instance under test, without a trailing slash.
	 *
	 * NEXTCLOUD_BASE_URL takes precedence; TEST_SERVER_URL is accepted as well
	 * so the suite can run inside the environment set up by
	 * build/integration/run.sh, which points it at the OCS entry point.
	 */
	protected static function baseUrl(): string {
		$baseUrl = getenv('NEXTCLOUD_BASE_URL');
		if ($baseUrl === false || $baseUrl === '') {
			$baseUrl = getenv('TEST_SERVER_URL') ?: 'http://localhost:8080';
		}

		$baseUrl = rtrim($baseUrl, '/');
		if (str_ends_with($baseUrl, '/ocs')) {
			$baseUrl = substr($baseUrl, 0, -strlen('/ocs'));
		}

		return $baseUrl;
	}

	protected static function serverRoot(): string {
		return dirname(__DIR__, 3);
	}

	protected static function guest(): ApiClient {
		return self::$guest ??= new ApiClient(self::baseUrl());
	}

	protected static function user(string $userId, string $password = Users::DEFAULT_PASSWORD): ApiClient {
		return self::guest()->asUser($userId, $password);
	}

	protected static function admin(): ApiClient {
		return self::user(self::ADMIN_USER, self::ADMIN_PASSWORD);
	}

	protected static function occ(): Occ {
		return self::$occ ??= new Occ(self::serverRoot(), self::guest());
	}

	protected static function users(): Users {
		return self::$users ??= new Users(self::admin());
	}

	/**
	 * Asserts the HTTP status of a response and reports the body when it differs,
	 * which is usually where the reason for an unexpected status is.
	 */
	protected static function assertStatus(int $expectedStatus, ResponseInterface $response, string $message = ''): void {
		$actualStatus = $response->getStatusCode();
		if ($actualStatus !== $expectedStatus) {
			$body = trim((string)$response->getBody());
			$message = trim($message . "\nResponse body: " . ($body === '' ? '<empty>' : mb_substr($body, 0, 1000)));
		}

		self::assertSame($expectedStatus, $actualStatus, $message);
	}
}
