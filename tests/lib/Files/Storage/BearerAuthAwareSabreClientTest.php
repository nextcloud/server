<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage;

use OC\Files\Storage\BearerAuthAwareSabreClient;
use PHPUnit\Framework\Attributes\CoversClass;
use Test\TestCase;

/**
 * @package Test\Files\Storage
 */
#[CoversClass(BearerAuthAwareSabreClient::class)]
class BearerAuthAwareSabreClientTest extends TestCase {
	private function getCurlSetting(BearerAuthAwareSabreClient $client, int $key): mixed {
		$reflection = new \ReflectionObject($client);
		$property = $reflection->getProperty('curlSettings');
		$property->setAccessible(true);
		$settings = $property->getValue($client);
		return $settings[$key] ?? null;
	}

	public function testBearerTokenIsUsedForAuthentication(): void {
		$client = new BearerAuthAwareSabreClient([
			'baseUri' => 'https://example.com/',
			// The user name holds the long-lived share secret, never the bearer.
			'userName' => 'refresh-secret',
			'password' => '',
			'authType' => BearerAuthAwareSabreClient::AUTH_BEARER,
			'bearerToken' => 'the-access-jwt',
		]);

		$this->assertSame('the-access-jwt', $this->getCurlSetting($client, CURLOPT_XOAUTH2_BEARER));
	}

	public function testShareSecretIsNotUsedAsBearer(): void {
		$client = new BearerAuthAwareSabreClient([
			'baseUri' => 'https://example.com/',
			'userName' => 'refresh-secret',
			'password' => '',
			'authType' => BearerAuthAwareSabreClient::AUTH_BEARER,
			'bearerToken' => 'the-access-jwt',
		]);

		$this->assertNotSame('refresh-secret', $this->getCurlSetting($client, CURLOPT_XOAUTH2_BEARER));
	}

	public function testNoBearerAppliedWithoutBearerAuthType(): void {
		$client = new BearerAuthAwareSabreClient([
			'baseUri' => 'https://example.com/',
			'userName' => 'user',
			'password' => 'pass',
			'authType' => \Sabre\DAV\Client::AUTH_BASIC,
			'bearerToken' => 'the-access-jwt',
		]);

		$this->assertNull($this->getCurlSetting($client, CURLOPT_XOAUTH2_BEARER));
	}

	public function testNoBearerAppliedWithoutBearerToken(): void {
		$client = new BearerAuthAwareSabreClient([
			'baseUri' => 'https://example.com/',
			'userName' => 'refresh-secret',
			'password' => '',
			'authType' => BearerAuthAwareSabreClient::AUTH_BEARER,
		]);

		$this->assertNull($this->getCurlSetting($client, CURLOPT_XOAUTH2_BEARER));
	}
}
