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

/**
 * Tests for the external Storage class for remote shares.
 *
 * @group DB
 */
class ExternalStorageTest extends \Test\TestCase {
	public function optionsProvider() {
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
		$certificateManager = \OC::$server->getCertificateManager();
		$httpClientService = $this->createMock(IClientService::class);
		$manager = $this->createMock(ExternalShareManager::class);
		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
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
			]
		);
	}

	/**
	 * @dataProvider optionsProvider
	 */
	public function testStorageMountOptions($inputUri, $baseUri): void {
		$storage = $this->getTestStorage($inputUri);
		$this->assertEquals($baseUri, $storage->getBaseUri());
	}

	public function testIfTestReturnsTheValue(): void {
		$storage = $this->getTestStorage('https://remoteserver');
		$result = $storage->test();
		$this->assertSame(true, $result);
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
