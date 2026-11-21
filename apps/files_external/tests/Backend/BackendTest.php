<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_External\Tests\Backend;

use OCA\Files_External\Lib\Auth\Password\Password;
use OCA\Files_External\Lib\Auth\Password\SessionCredentials;
use OCA\Files_External\Lib\Backend\AmazonS3;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\Backend\DAV;
use OCA\Files_External\Lib\Backend\FTP;
use OCA\Files_External\Lib\Backend\SMB;
use OCA\Files_External\Lib\Backend\SMB_OC;
use OCA\Files_External\Lib\Backend\Swift;
use OCA\Files_External\Lib\MissingDependency;
use OCA\Files_External\Lib\StorageConfig;
use OCP\IL10N;

class BackendTest extends \Test\TestCase {
	public static function noDependenciesProvider(): array {
		return [
			'DAV' => [DAV::class],
			'Swift' => [Swift::class],
			'S3' => [AmazonS3::class],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'noDependenciesProvider')]
	public function testBackendHasNoMissingDependencies(string $class): void {
		$backend = $this->getMockBuilder($class)->disableOriginalConstructor()->onlyMethods([])->getMock();
		$this->assertSame([], $backend->checkDependencies());
	}

	public function testFtpDependencies(): void {
		$backend = new FTP($this->createMock(IL10N::class), $this->createMock(Password::class));
		$missing = $backend->checkDependencies();

		if (function_exists('ftp_login')) {
			$this->assertSame([], $missing);
		} else {
			$this->assertCount(1, $missing);
			$this->assertSame('ftp', $missing[0]->getDependency());
			$this->assertFalse($missing[0]->isOptional());
		}
	}

	public function testSmbOcDelegatesDependencies(): void {
		$optional = new MissingDependency('php-smbclient');
		$optional->setOptional(true);
		$dependencies = [$optional, new MissingDependency('smbclient')];
		$smb = $this->createMock(SMB::class);
		$smb->expects($this->once())
			->method('checkDependencies')
			->willReturn($dependencies);

		$backend = new SMB_OC($this->createMock(IL10N::class), $this->createMock(SessionCredentials::class), $smb);
		$this->assertSame($dependencies, $backend->checkDependencies());
	}

	public function testJsonSerialization(): void {
		$backend = $this->getMockBuilder(Backend::class)
			->onlyMethods(['jsonSerializeDefinition'])
			->getMock();
		$backend->expects($this->once())
			->method('jsonSerializeDefinition')
			->willReturn(['foo' => 'bar', 'name' => 'abc']);

		$backend->setPriority(57);
		$backend->addAuthScheme('foopass');
		$backend->addAuthScheme('barauth');

		$json = $backend->jsonSerialize();
		$this->assertEquals('bar', $json['foo']);
		$this->assertEquals('abc', $json['name']);
		$this->assertEquals($json['name'], $json['backend']);
		$this->assertEquals(57, $json['priority']);

		$this->assertContains('foopass', array_keys($json['authSchemes']));
		$this->assertContains('barauth', array_keys($json['authSchemes']));
	}

	public static function validateStorageProvider(): array {
		return [
			[true, true],
			[false, false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'validateStorageProvider')]
	public function testValidateStorage(bool $expectedSuccess, bool $definitionSuccess): void {
		$backend = $this->getMockBuilder(Backend::class)
			->onlyMethods(['validateStorageDefinition'])
			->getMock();
		$backend->expects($this->atMost(1))
			->method('validateStorageDefinition')
			->willReturn($definitionSuccess);

		$storageConfig = $this->getMockBuilder(StorageConfig::class)
			->disableOriginalConstructor()
			->getMock();

		$this->assertEquals($expectedSuccess, $backend->validateStorage($storageConfig));
	}

	public function testLegacyAuthMechanismCallback(): void {
		$backend = new Backend();
		$backend->setLegacyAuthMechanismCallback(function (array $params) {
			if (isset($params['ping'])) {
				return 'pong';
			}
			return 'foobar';
		});

		$this->assertEquals('pong', $backend->getLegacyAuthMechanism(['ping' => true]));
		$this->assertEquals('foobar', $backend->getLegacyAuthMechanism(['other' => true]));
		$this->assertEquals('foobar', $backend->getLegacyAuthMechanism());
	}
}
