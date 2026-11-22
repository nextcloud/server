<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage;

use OC\Files\Storage\DAV;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\ITempManager;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class DAVTest extends TestCase {
	private IClient&MockObject $client;

	protected function setUp(): void {
		parent::setUp();

		$this->client = $this->createMock(IClient::class);
		$clientService = $this->createMock(IClientService::class);
		$clientService->method('newClient')->willReturn($this->client);
		$this->overwriteService(IClientService::class, $clientService);
	}

	protected function tearDown(): void {
		Server::get(IConfig::class)->deleteSystemValue('davstorage.allow_local_address');
		$this->restoreService(IClientService::class);
		parent::tearDown();
	}

	private function createStorage(): TestDAVStorage {
		return new TestDAVStorage([
			'host' => 'nas.local',
			'user' => 'user',
			'password' => 'password',
			'root' => '/',
			'secure' => false,
		]);
	}

	public static function allowLocalAddressProvider(): array {
		return [
			'not configured' => [null, false],
			'disabled' => [false, false],
			'enabled' => [true, true],
		];
	}

	#[DataProvider(methodName: 'allowLocalAddressProvider')]
	public function testFopenReadPassesAllowLocalAddress(?bool $configValue, bool $expected): void {
		if ($configValue !== null) {
			Server::get(IConfig::class)->setSystemValue('davstorage.allow_local_address', $configValue);
		}

		$response = $this->createMock(IResponse::class);
		$response->method('getStatusCode')->willReturn(200);
		$response->method('getBody')->willReturn(fopen('php://memory', 'r'));

		$this->client->expects($this->once())
			->method('get')
			->with(
				'http://nas.local/file.txt',
				$this->callback(fn (array $options): bool => $options['nextcloud']['allow_local_address'] === $expected),
			)
			->willReturn($response);

		$this->assertIsResource($this->createStorage()->fopen('file.txt', 'r'));
	}

	#[DataProvider(methodName: 'allowLocalAddressProvider')]
	public function testUploadFilePassesAllowLocalAddress(?bool $configValue, bool $expected): void {
		if ($configValue !== null) {
			Server::get(IConfig::class)->setSystemValue('davstorage.allow_local_address', $configValue);
		}

		$this->client->expects($this->once())
			->method('put')
			->with(
				'http://nas.local/file.txt',
				$this->callback(fn (array $options): bool => $options['nextcloud']['allow_local_address'] === $expected),
			);

		$source = Server::get(ITempManager::class)->getTemporaryFile();
		$this->createStorage()->callUploadFile($source, 'file.txt');
	}
}

class TestDAVStorage extends DAV {
	public function callUploadFile(string $path, string $target): void {
		$this->uploadFile($path, $target);
	}

	#[\Override]
	protected function removeCachedFile(string $path): void {
	}
}
