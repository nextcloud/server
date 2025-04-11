<?php
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
			->setMethods(['jsonSerializeDefinition'])
			->getMock();
		$mechanism->expects($this->once())
			->method('jsonSerializeDefinition')
			->willReturn(['foo' => 'bar']);

		$mechanism->setScheme('scheme');

		$json = $mechanism->jsonSerialize();
		$this->assertEquals('bar', $json['foo']);
		$this->assertEquals('scheme', $json['scheme']);
	}

	public function validateStorageProvider() {
		return [
			[true, 'scheme', true],
			[false, 'scheme', false],
			[true, 'foobar', true],
			[false, 'barfoo', true],
		];
	}

	/**
	 * @dataProvider validateStorageProvider
	 */
	public function testValidateStorage($expectedSuccess, $scheme, $definitionSuccess): void {
		$mechanism = $this->getMockBuilder(AuthMechanism::class)
			->setMethods(['validateStorageDefinition'])
			->getMock();
		$mechanism->expects($this->atMost(1))
			->method('validateStorageDefinition')
			->willReturn($definitionSuccess);

		$mechanism->setScheme($scheme);

		$backend = $this->getMockBuilder(Backend::class)
			->disableOriginalConstructor()
			->getMock();
		$backend->expects($this->once())
			->method('getAuthSchemes')
			->willReturn(['scheme' => true, 'foobar' => true]);

		$storageConfig = $this->getMockBuilder(StorageConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$storageConfig->expects($this->once())
			->method('getBackend')
			->willReturn($backend);

		$this->assertEquals($expectedSuccess, $mechanism->validateStorage($storageConfig));
	}
}
