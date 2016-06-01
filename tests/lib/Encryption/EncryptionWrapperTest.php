<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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


namespace Test\Encryption;


use OC\Encryption\EncryptionWrapper;
use Test\TestCase;

class EncryptionWrapperTest extends TestCase {

	/** @var  EncryptionWrapper */
	private $instance;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | \OCP\ILogger */
	private $logger;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | \OC\Encryption\Manager */
	private $manager;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | \OC\Memcache\ArrayCache */
	private $arrayCache;

	public function setUp() {
		parent::setUp();

		$this->arrayCache = $this->getMock('OC\Memcache\ArrayCache');
		$this->manager = $this->getMockBuilder('OC\Encryption\Manager')
			->disableOriginalConstructor()->getMock();
		$this->logger = $this->getMock('OCP\ILogger');

		$this->instance = new EncryptionWrapper($this->arrayCache, $this->manager, $this->logger);
	}


	/**
	 * @dataProvider provideWrapStorage
	 */
	public function testWrapStorage($expectedWrapped, $wrappedStorages) {
		$storage = $this->getMockBuilder('OC\Files\Storage\Storage')
			->disableOriginalConstructor()
			->getMock();

		foreach ($wrappedStorages as $wrapper) {
			$storage->expects($this->any())
				->method('instanceOfStorage')
				->willReturnMap([
					[$wrapper, true],
				]);
		}

		$mount = $this->getMockBuilder('OCP\Files\Mount\IMountPoint')
			->disableOriginalConstructor()
			->getMock();

		$returnedStorage = $this->instance->wrapStorage('mountPoint', $storage, $mount);

		$this->assertEquals(
			$expectedWrapped,
			$returnedStorage->instanceOfStorage('OC\Files\Storage\Wrapper\Encryption'),
			'Asserted that the storage is (not) wrapped with encryption'
		);
	}

	public function provideWrapStorage() {
		return [
			// Wrap when not wrapped or not wrapped with storage
			[true, []],
			[true, ['OCA\Files_Trashbin\Storage']],

			// Do not wrap shared storages
			[false, ['OC\Files\Storage\Shared']],
			[false, ['OCA\Files_Sharing\External\Storage']],
			[false, ['OC\Files\Storage\OwnCloud']],
			[false, ['OC\Files\Storage\Shared', 'OCA\Files_Sharing\External\Storage']],
			[false, ['OC\Files\Storage\Shared', 'OC\Files\Storage\OwnCloud']],
			[false, ['OCA\Files_Sharing\External\Storage', 'OC\Files\Storage\OwnCloud']],
			[false, ['OC\Files\Storage\Shared', 'OCA\Files_Sharing\External\Storage', 'OC\Files\Storage\OwnCloud']],
		];
	}

}
