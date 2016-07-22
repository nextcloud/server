<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 *
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

namespace OCA\Files_External\Tests\Backend;

use \OCA\Files_External\Lib\Backend\Backend;

class BackendTest extends \Test\TestCase {

	public function testJsonSerialization() {
		$backend = $this->getMockBuilder('\OCA\Files_External\Lib\Backend\Backend')
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

		$this->assertContains('foopass', $json['authSchemes']);
		$this->assertContains('barauth', $json['authSchemes']);
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
	public function testValidateStorage($expectedSuccess, $definitionSuccess) {
		$backend = $this->getMockBuilder('\OCA\Files_External\Lib\Backend\Backend')
			->setMethods(['validateStorageDefinition'])
			->getMock();
		$backend->expects($this->atMost(1))
			->method('validateStorageDefinition')
			->willReturn($definitionSuccess);

		$storageConfig = $this->getMockBuilder('\OCA\Files_External\Lib\StorageConfig')
			->disableOriginalConstructor()
			->getMock();

		$this->assertEquals($expectedSuccess, $backend->validateStorage($storageConfig));
	}

	public function testLegacyAuthMechanismCallback() {
		$backend = new Backend();
		$backend->setLegacyAuthMechanismCallback(function(array $params) {
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
