<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Sharing\Tests;

use OC\Federation\CloudId;
use OCA\Files_Sharing\External\Manager as ExternalShareManager;
use OCA\Files_Sharing\External\Storage;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\ICertificateManager;
use OCP\IConfig;
use OCP\IUser;
use OCP\OCM\IOCMDiscoveryService;
use OCP\OCM\IOCMProvider;
use OCP\Server;

/**
 * Tests for the external Storage class for remote shares.
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class ExternalStorageTest extends \Test\TestCase {
	public static function optionsProvider(): array {
		return [
			[
				'http://remoteserver:8080/owncloud',
				'http://remoteserver:8080/owncloud/public.php/webdav/',
			],
			// extra slash
			[
				'http://remoteserver:8080/owncloud/',
				'http://remoteserver:8080/owncloud/public.php/webdav/',
			],
			// extra path
			[
				'http://remoteserver:8080/myservices/owncloud/',
				'http://remoteserver:8080/myservices/owncloud/public.php/webdav/',
			],
			// root path
			[
				'http://remoteserver:8080/',
				'http://remoteserver:8080/public.php/webdav/',
			],
			// without port
			[
				'http://remoteserver/oc.test',
				'http://remoteserver/oc.test/public.php/webdav/',
			],
			// https
			[
				'https://remoteserver/',
				'https://remoteserver/public.php/webdav/',
			],
		];
	}

	private function getTestStorage($uri) {
		$certificateManager = Server::get(ICertificateManager::class);
		$httpClientService = $this->createMock(IClientService::class);
		$manager = $this->createMock(ExternalShareManager::class);
		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
		$discoveryService = $this->createMock(IOCMDiscoveryService::class);
		$ocmProvider = $this->createMock(IOCMProvider::class);

		$client
			->expects($this->any())
			->method('get')
			->willReturn($response);
		$client
			->expects($this->any())
			->method('post')
			->willReturn($response);
		$httpClientService
			->expects($this->any())
			->method('newClient')
			->willReturn($client);
		$discoveryService->method('discover')
			->willReturn($ocmProvider);
		$ocmProvider->method('extractProtocolEntry')
			->willReturn('/public.php/webdav');
		$ocmProvider->method('getEndPoint')
			->willReturn($uri);
		$user = $this->createMock(IUser::class);

		return new TestSharingExternalStorage(
			[
				'cloudId' => new CloudId('testOwner@' . $uri, 'testOwner', $uri),
				'remote' => $uri,
				'owner' => 'testOwner',
				'mountpoint' => 'remoteshare',
				'token' => 'abcdef',
				'password' => '',
				'manager' => $manager,
				'certificateManager' => $certificateManager,
				'HttpClientService' => $httpClientService,
				'discoveryService' => $discoveryService,
				'recipient' => $user,
			]
		);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'optionsProvider')]
	public function testStorageMountOptions($inputUri, $baseUri): void {
		$storage = $this->getTestStorage($inputUri);
		$this->assertEquals($baseUri, $storage->getBaseUri());
	}

	public function testIfTestReturnsTheValue(): void {
		$storage = $this->getTestStorage('https://remoteserver');
		$result = $storage->test();
		$this->assertSame(true, $result);
	}

	public static function requestTimeoutProvider(): array {
		return [
			'default' => [null, 10],
			'configured' => [25, 25],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'requestTimeoutProvider')]
	public function testGetShareInfoUsesConfiguredRequestTimeout(?int $configured, int $expected): void {
		if ($configured !== null) {
			$realConfig = Server::get(IConfig::class);
			$config = $this->createMock(IConfig::class);
			$config->method('getSystemValueInt')
				->willReturnCallback(fn (string $key, int $default = 0): int => $key === 'sharing.federation.request_timeout'
					? $configured
					: $realConfig->getSystemValueInt($key, $default));
			$config->method('getSystemValueBool')
				->willReturnCallback(fn (string $key, bool $default = false): bool => $realConfig->getSystemValueBool($key, $default));
			$config->method('getSystemValue')
				->willReturnCallback(fn (string $key, mixed $default = ''): mixed => $realConfig->getSystemValue($key, $default));
			$this->overwriteService(IConfig::class, $config);
		}

		$response = $this->createMock(IResponse::class);
		$response->method('getBody')
			->willReturn('{"id":1}');
		$client = $this->createMock(IClient::class);
		$client->expects($this->once())
			->method('post')
			->with(
				'https://remoteserver/index.php/apps/files_sharing/shareinfo?t=abcdef',
				$this->callback(function (array $options) use ($expected): bool {
					$this->assertSame($expected, $options['timeout']);
					$this->assertSame($expected, $options['connect_timeout']);
					return true;
				}),
			)
			->willReturn($response);
		$clientService = $this->createMock(IClientService::class);
		$clientService->method('newClient')
			->willReturn($client);
		$this->overwriteService(IClientService::class, $clientService);

		$uri = 'https://remoteserver';
		$discoveryService = $this->createMock(IOCMDiscoveryService::class);
		$discoveryService->method('discover')
			->willReturn($this->createMock(IOCMProvider::class));
		$storage = $this->getMockBuilder(Storage::class)
			->setConstructorArgs([[
				'cloudId' => new CloudId('testOwner@' . $uri, 'testOwner', $uri),
				'remote' => $uri,
				'owner' => 'testOwner',
				'mountpoint' => 'remoteshare',
				'token' => 'abcdef',
				'password' => '',
				'manager' => $this->createMock(ExternalShareManager::class),
				'certificateManager' => Server::get(ICertificateManager::class),
				'HttpClientService' => $clientService,
				'discoveryService' => $discoveryService,
				'recipient' => $this->createMock(IUser::class),
			]])
			->onlyMethods(['remoteIsOwnCloud'])
			->getMock();
		$storage->method('remoteIsOwnCloud')
			->willReturn(true);

		$this->assertSame(['id' => 1], $storage->getShareInfo());
	}
}

/**
 * Dummy subclass to make it possible to access private members
 */
class TestSharingExternalStorage extends Storage {
	public function getBaseUri() {
		return $this->createBaseUri();
	}

	public function stat(string $path): array|false {
		if ($path === '') {
			return ['key' => 'value'];
		}
		return parent::stat($path);
	}
}
