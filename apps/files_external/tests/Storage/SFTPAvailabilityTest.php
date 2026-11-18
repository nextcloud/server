<?php

declare(strict_types=1);

namespace OCA\Files_External\Tests;

use OCA\Files_External\Lib\Storage\SFTP;
use phpseclib3\Net\SFTP as SftpClient;
use Test\TestCase;

class SFTPAvailabilityTest extends TestCase {
	private const OPTIONS = [
		'host' => 'example.test',
		'user' => 'alice',
		'password' => 'password',
	];

	public static function rootProbeProvider(): array {
		return [
			'default root' => [null, '/', [], true],
			'accessible nested root' => ['nested', '/nested/', [], true],
			'inaccessible nested root' => ['nested', '/nested/', false, false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('rootProbeProvider')]
	public function testProbeListsConfiguredRoot(
		?string $root,
		string $expectedPath,
		array|false $listing,
		bool $expected,
	): void {
		$options = self::OPTIONS;
		if ($root !== null) {
			$options['root'] = $root;
		}

		$client = $this->createMock(SftpClient::class);
		$client->expects($this->once())
			->method('nlist')
			->with($expectedPath)
			->willReturn($listing);

		$storage = $this->getMockBuilder(SFTP::class)
			->setConstructorArgs([$options])
			->onlyMethods(['getConnection'])
			->getMock();
		$storage->method('getConnection')->willReturn($client);

		$this->assertSame($expected, $storage->test());
	}

	public function testFailedHostKeyRetrievalDoesNotAuthenticateOrSaveKey(): void {
		$client = $this->createMock(SftpClient::class);
		$client->method('getServerPublicHostKey')->willReturn(false);
		$client->expects($this->never())->method('login');

		$storage = $this->getMockBuilder(SFTP::class)
			->setConstructorArgs([self::OPTIONS])
			->onlyMethods(['createSftpClient', 'readHostKeys', 'writeHostKeys'])
			->getMock();
		$storage->method('createSftpClient')->willReturn($client);
		$storage->method('readHostKeys')->willReturn([]);
		$storage->expects($this->never())->method('writeHostKeys');

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Unable to retrieve host key');
		$storage->getConnection();
	}

	public function testFailedAuthenticationIsNotCached(): void {
		$failed = $this->createMock(SftpClient::class);
		$failed->method('getServerPublicHostKey')->willReturn('host-key');
		$failed->expects($this->once())
			->method('login')
			->willReturn(false);

		$authenticated = $this->createMock(SftpClient::class);
		$authenticated->method('getServerPublicHostKey')->willReturn('host-key');
		$authenticated->expects($this->once())
			->method('login')
			->willReturn(true);
		$authenticated->method('isConnected')->willReturn(true);
		$authenticated->method('isAuthenticated')->willReturn(true);

		$storage = $this->getMockBuilder(SFTP::class)
			->setConstructorArgs([self::OPTIONS])
			->onlyMethods(['createSftpClient', 'readHostKeys', 'writeHostKeys'])
			->getMock();
		$storage->expects($this->exactly(2))
			->method('createSftpClient')
			->willReturnOnConsecutiveCalls($failed, $authenticated);
		$storage->method('readHostKeys')
			->willReturn(['example.test' => 'host-key']);
		$storage->expects($this->never())->method('writeHostKeys');

		$error = null;
		try {
			$storage->getConnection();
		} catch (\Exception $e) {
			$error = $e;
		}
		$this->assertNotNull($error);
		$this->assertSame('Authentication failed', $error->getMessage());

		$this->assertSame($authenticated, $storage->getConnection());
		$this->assertSame($authenticated, $storage->getConnection());
	}
}
