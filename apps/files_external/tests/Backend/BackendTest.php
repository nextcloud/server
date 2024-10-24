<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Backend;

use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\StorageConfig;

class BackendTest extends \Test\TestCase {
	public function testJsonSerialization(): void {
		$backend = $this->getMockBuilder(Backend::class)
			->setMethods(['jsonSerializeDefinition'])
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

	public function validateStorageProvider() {
		return [
			[true, true],
			[false, false],
		];
	}

	/**
	 * @dataProvider validateStorageProvider
	 */
	public function testValidateStorage($expectedSuccess, $definitionSuccess): void {
		$backend = $this->getMockBuilder(Backend::class)
			->setMethods(['validateStorageDefinition'])
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
