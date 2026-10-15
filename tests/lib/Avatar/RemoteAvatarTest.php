<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Avatar;

use OC\Avatar\RemoteAvatar;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class RemoteAvatarTest extends TestCase {
	private const string CLOUD_ID = 'user@https://remote.example.com';

	private ISimpleFolder&MockObject $folder;
	private IConfig&MockObject $config;
	private LoggerInterface&MockObject $logger;
	private ICloudIdManager&MockObject $cloudIdManager;
	private IClientService&MockObject $clientService;
	private RemoteAvatar $avatar;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getUser')->willReturn('user');
		$cloudId->method('getRemote')->willReturn('https://remote.example.com');
		$cloudId->method('getDisplayId')->willReturn('user@remote.example.com');

		$this->cloudIdManager = $this->createMock(ICloudIdManager::class);
		$this->cloudIdManager->method('resolveCloudId')
			->with(self::CLOUD_ID)
			->willReturn($cloudId);
		$this->overwriteService(ICloudIdManager::class, $this->cloudIdManager);

		$this->clientService = $this->createMock(IClientService::class);
		$this->overwriteService(IClientService::class, $this->clientService);

		$this->folder = $this->createMock(ISimpleFolder::class);
		$this->avatar = new RemoteAvatar($this->folder, self::CLOUD_ID, $this->config, $this->logger);
	}

	/**
	 * Stubs the client returned by IClientService::newClient() to respond to
	 * a single GET request, optionally asserting the requested URL/options.
	 *
	 * @param string|resource|false $body
	 */
	private function mockRemoteClient(string $contentType, string $body, ?string $expectedUrl = null, ?array $expectedOptions = null): void {
		$response = $this->createMock(IResponse::class);
		$response->method('getHeader')->with('Content-Type')->willReturn($contentType);
		$response->method('getBody')->willReturn($body);

		$client = $this->createMock(IClient::class);
		$matcher = $client->expects($this->once())->method('get');
		if ($expectedUrl !== null) {
			$matcher->with($expectedUrl, $expectedOptions ?? $this->anything());
		}
		$matcher->willReturn($response);

		$this->clientService->method('newClient')->willReturn($client);
	}

	public function testExists(): void {
		$this->assertTrue($this->avatar->exists());
	}

	public function testGetDisplayName(): void {
		$this->assertSame('user@remote.example.com', $this->avatar->getDisplayName());
	}

	public function testGetFileFetchesAvatarFromRemoteInstance(): void {
		$this->config->method('getSystemValueBool')
			->with('sharing.federation.allowSelfSignedCertificates', false)
			->willReturn(false);

		$fileContents = 'png-bytes';

		$this->mockRemoteClient(
			'image/png',
			$fileContents,
			'https://remote.example.com/index.php/avatar/user/64',
			['verify' => true],
		);

		$expectedFile = $this->createMock(ISimpleFile::class);
		$expectedFile->expects($this->once())->method('getContent')->willReturn($fileContents);

		$this->folder->expects($this->once())->method('newFile')->willReturn($expectedFile);

		$file = $this->avatar->getFile(64);
		$this->assertInstanceOf(ISimpleFile::class, $file);
		$this->assertSame($fileContents, $file->getContent());
	}

	public function testGetFileFetchesAvatarFromCache(): void {
		$this->config->method('getSystemValueBool')
			->with('sharing.federation.allowSelfSignedCertificates', false)
			->willReturn(false);

		$fileContents = 'png-bytes';

		$expectedFile = $this->createMock(ISimpleFile::class);
		$expectedFile->expects($this->once())->method('getContent')->willReturn($fileContents);
		$expectedFile->expects($this->once())->method('getMTime')->willReturn(time() + (60 * 60 * 24));
		$expectedFile->expects($this->once())->method('getName')->willReturn('avatar.64.png');
		$this->folder->expects($this->once())->method('getDirectoryListing')->willReturn([$expectedFile]);

		$this->clientService->expects($this->never())->method('newClient');

		$file = $this->avatar->getFile(64);
		$this->assertInstanceOf(ISimpleFile::class, $file);
		$this->assertSame($fileContents, $file->getContent());
	}

	public function testGetFileThrowsOnUnexpectedContentType(): void {
		$this->mockRemoteClient('text/html', '<html></html>');

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Unknown filetype');

		$this->avatar->getFile(64);
	}

	public function testIsCustomAvatar(): void {
		$this->assertTrue($this->avatar->isCustomAvatar());
	}
}
