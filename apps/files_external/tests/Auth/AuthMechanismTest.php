<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Auth;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\StorageConfig;

class AuthMechanismTest extends \Test\TestCase {
	public function testJsonSerialization(): void {
		$mechanism = $this->getMockBuilder(AuthMechanism::class)
			->onlyMethods(['jsonSerializeDefinition'])
			->getMock();
		$mechanism->expects($this->once())
			->method('jsonSerializeDefinition')
			->willReturn(['foo' => 'bar']);

		$mechanism->setScheme('scheme');

		$json = $mechanism->jsonSerialize();
		$this->assertEquals('bar', $json['foo']);
		$this->assertEquals('scheme', $json['scheme']);
	}

	public static function validateStorageProvider(): array {
		return [
			[true, 'scheme', true],
			[false, 'scheme', false],
			[true, 'foobar', true],
			[false, 'barfoo', true],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'validateStorageProvider')]
	public function testValidateStorage(bool $expectedSuccess, string $scheme, bool $definitionSuccess): void {
		$mechanism = $this->getMockBuilder(AuthMechanism::class)
			->onlyMethods(['validateStorageDefinition'])
			->getMock();
		$mechanism->expects($this->atMost(1))
			->method('validateStorageDefinition')
			->willReturn($definitionSuccess);

		$mechanism->setScheme($scheme);

		$backend = $this->createMock(Backend::class);
		$backend->expects($this->once())
			->method('getAuthSchemes')
			->willReturn(['scheme' => true, 'foobar' => true]);

		$storageConfig = $this->createMock(StorageConfig::class);
		$storageConfig->expects($this->once())
			->method('getBackend')
			->willReturn($backend);

		$this->assertEquals($expectedSuccess, $mechanism->validateStorage($storageConfig));
	}
}
