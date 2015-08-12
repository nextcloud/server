<?php
/**
 * @author Robin McCorkell <rmccorkell@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_External\Tests;

class FrontendDefinitionTraitTest extends \Test\TestCase {

	public function testJsonSerialization() {
		$param = $this->getMockBuilder('\OCA\Files_External\Lib\DefinitionParameter')
			->disableOriginalConstructor()
			->getMock();
		$param->method('getName')->willReturn('foo');

		$trait = $this->getMockForTrait('\OCA\Files_External\Lib\FrontendDefinitionTrait');
		$trait->setText('test');
		$trait->addParameters([$param]);
		$trait->setCustomJs('foo/bar.js');

		$json = $trait->jsonSerializeDefinition();

		$this->assertEquals('test', $json['name']);
		$this->assertEquals('foo/bar.js', $json['custom']);

		$configuration = $json['configuration'];
		$this->assertArrayHasKey('foo', $configuration);
	}

	public function validateStorageProvider() {
		return [
			[true, ['foo' => true, 'bar' => true, 'baz' => true]],
			[false, ['foo' => true, 'bar' => false]]
		];
	}

	/**
	 * @dataProvider validateStorageProvider
	 */
	public function testValidateStorage($expectedSuccess, $params) {
		$backendParams = [];
		foreach ($params as $name => $valid) {
			$param = $this->getMockBuilder('\OCA\Files_External\Lib\DefinitionParameter')
				->disableOriginalConstructor()
				->getMock();
			$param->method('getName')
				->willReturn($name);
			$param->expects($this->once())
				->method('validateValue')
				->willReturn($valid);
			$backendParams[] = $param;
		}

		$storageConfig = $this->getMockBuilder('\OCA\Files_External\Lib\StorageConfig')
			->disableOriginalConstructor()
			->getMock();
		$storageConfig->expects($this->once())
			->method('getBackendOptions')
			->willReturn([]);

		$trait = $this->getMockForTrait('\OCA\Files_External\Lib\FrontendDefinitionTrait');
		$trait->setText('test');
		$trait->addParameters($backendParams);

		$this->assertEquals($expectedSuccess, $trait->validateStorageDefinition($storageConfig));
	}
}
