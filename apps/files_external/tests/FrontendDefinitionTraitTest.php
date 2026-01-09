<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests;

use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\FrontendDefinitionTrait;
use OCA\Files_External\Lib\StorageConfig;

class FrontendDefinitionTraitTest extends \Test\TestCase {
	public function testJsonSerialization(): void {
		$param = $this->getMockBuilder(DefinitionParameter::class)
			->disableOriginalConstructor()
			->getMock();
		$param->method('getName')->willReturn('foo');

		/** @var FrontendDefinitionTrait $trait */
		$trait = $this->getMockForTrait(FrontendDefinitionTrait::class);
		$trait->setText('test');
		$trait->addParameters([$param]);
		$trait->addCustomJs('foo/bar.js');
		$trait->addCustomJs('bar/foo.js');

		$json = $trait->jsonSerializeDefinition();

		$this->assertEquals('test', $json['name']);
		$this->assertContains('foo/bar.js', $json['custom']);
		$this->assertContains('bar/foo.js', $json['custom']);

		$configuration = $json['configuration'];
		$this->assertArrayHasKey('foo', $configuration);
	}

	public static function validateStorageProvider(): array {
		return [
			[true, ['foo' => true, 'bar' => true, 'baz' => true]],
			[false, ['foo' => true, 'bar' => false]]
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'validateStorageProvider')]
	public function testValidateStorage(bool $expectedSuccess, array $params): void {
		$backendParams = [];
		foreach ($params as $name => $valid) {
			$param = $this->getMockBuilder(DefinitionParameter::class)
				->disableOriginalConstructor()
				->getMock();
			$param->method('getName')
				->willReturn($name);
			$param->method('isOptional')
				->willReturn(false);
			$param->expects($this->once())
				->method('validateValue')
				->willReturn($valid);
			$backendParams[] = $param;
		}

		$storageConfig = $this->getMockBuilder(StorageConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$storageConfig->expects($this->any())
			->method('getBackendOption')
			->willReturn(null);
		$storageConfig->expects($this->any())
			->method('setBackendOption');

		/** @var FrontendDefinitionTrait $trait */
		$trait = $this->getMockForTrait(FrontendDefinitionTrait::class);
		$trait->setText('test');
		$trait->addParameters($backendParams);

		$this->assertEquals($expectedSuccess, $trait->validateStorageDefinition($storageConfig));
	}

	public function testValidateStorageSet(): void {
		$param = $this->getMockBuilder(DefinitionParameter::class)
			->disableOriginalConstructor()
			->getMock();
		$param->method('getName')
			->willReturn('param');
		$param->expects($this->once())
			->method('validateValue')
			->willReturnCallback(function (&$value) {
				$value = 'foobar';
				return true;
			});

		$storageConfig = $this->getMockBuilder(StorageConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$storageConfig->expects($this->once())
			->method('getBackendOption')
			->with('param')
			->willReturn('barfoo');
		$storageConfig->expects($this->once())
			->method('setBackendOption')
			->with('param', 'foobar');

		/** @var FrontendDefinitionTrait $trait */
		$trait = $this->getMockForTrait(FrontendDefinitionTrait::class);
		$trait->setText('test');
		$trait->addParameter($param);

		$this->assertEquals(true, $trait->validateStorageDefinition($storageConfig));
	}
}
