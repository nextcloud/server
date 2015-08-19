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

namespace OCA\Files_External\Tests\Auth;

class AuthMechanismTest extends \Test\TestCase {

	public function testJsonSerialization() {
		$mechanism = $this->getMockBuilder('\OCA\Files_External\Lib\Auth\AuthMechanism')
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
	public function testValidateStorage($expectedSuccess, $scheme, $definitionSuccess) {
		$mechanism = $this->getMockBuilder('\OCA\Files_External\Lib\Auth\AuthMechanism')
			->setMethods(['validateStorageDefinition'])
			->getMock();
		$mechanism->expects($this->atMost(1))
			->method('validateStorageDefinition')
			->willReturn($definitionSuccess);

		$mechanism->setScheme($scheme);

		$backend = $this->getMockBuilder('\OCA\Files_External\Lib\Backend\Backend')
			->disableOriginalConstructor()
			->getMock();
		$backend->expects($this->once())
			->method('getAuthSchemes')
			->willReturn(['scheme' => true, 'foobar' => true]);

		$storageConfig = $this->getMockBuilder('\OCA\Files_External\Lib\StorageConfig')
			->disableOriginalConstructor()
			->getMock();
		$storageConfig->expects($this->once())
			->method('getBackend')
			->willReturn($backend);

		$this->assertEquals($expectedSuccess, $mechanism->validateStorage($storageConfig));
	}

}
