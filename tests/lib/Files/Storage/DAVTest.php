<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage;

use OC\Files\Storage\BearerAuthAwareSabreClient;
use OC\Files\Storage\DAV;
use PHPUnit\Framework\Attributes\DataProvider;
use Sabre\DAV\Client as SabreClient;
use Test\TestCase;

final class DAVTestStorage extends DAV {
	public function getHttpAuthOptionsForTest(): array {
		return $this->getHttpAuthOptions();
	}
}

class DAVTest extends TestCase {
	/**
	 * @return array<string, array{int|null, array{auth: list<string>, headers?: array<string, string>}}>
	 */
	public static function httpAuthOptionsProvider(): array {
		return [
			'no auth type' => [
				null,
				[
					'auth' => ['user', 'password'],
				],
			],
			'basic auth' => [
				SabreClient::AUTH_BASIC,
				[
					'auth' => ['user', 'password'],
				],
			],
			'digest auth' => [
				SabreClient::AUTH_DIGEST,
				[
					'auth' => ['user', 'password', 'digest'],
				],
			],
			'bearer auth' => [
				BearerAuthAwareSabreClient::AUTH_BEARER,
				[
					'auth' => [],
					'headers' => [
						'Authorization' => 'Bearer access-token',
					],
				],
			],
		];
	}

	/**
	 * @param int|null $authType
	 * @param array{auth: list<string>, headers?: array<string, string>} $expected
	 */
	#[DataProvider('httpAuthOptionsProvider')]
	public function testGetHttpAuthOptions(?int $authType, array $expected): void {
		$storage = $this->createStorageWithoutConstructor();

		$this->setProperty($storage, 'authType', $authType);
		$this->setProperty($storage, 'user', 'user');
		$this->setProperty($storage, 'password', 'password');
		$this->setProperty($storage, 'bearerToken', 'access-token');

		$this->assertSame($expected, $storage->getHttpAuthOptionsForTest());
	}

	private function createStorageWithoutConstructor(): DAVTestStorage {
		$reflection = new \ReflectionClass(DAVTestStorage::class);

		/** @var DAVTestStorage */
		return $reflection->newInstanceWithoutConstructor();
	}

	private function setProperty(object $object, string $property, mixed $value): void {
		$reflection = new \ReflectionProperty(DAV::class, $property);
		$reflection->setValue($object, $value);
	}
}
