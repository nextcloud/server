<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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
namespace OCA\Files_Sharing\Tests\External;

use OCA\Files_Sharing\Tests\TestCase;

/**
 * Class Cache
 *
 * @group DB
 *
 * @package OCA\Files_Sharing\Tests\External
 */
class CacheTest extends TestCase {

	/**
	 * @var \OC\Files\Storage\Storage
	 **/
	private $storage;

	/**
	 * @var \OCA\Files_Sharing\External\Cache
	 */
	private $cache;

	/**
	 * @var string
	 */
	private $remoteUser;

	protected function setUp() {
		parent::setUp();

		$this->remoteUser = $this->getUniqueID('remoteuser');

		$this->storage = $this->getMockBuilder('\OCA\Files_Sharing\External\Storage')
			->disableOriginalConstructor()
			->getMock();
		$this->storage
			->expects($this->any())
			->method('getId')
			->will($this->returnValue('dummystorage::'));
		$this->cache = new \OCA\Files_Sharing\External\Cache(
			$this->storage,
			'http://example.com/owncloud',
			$this->remoteUser
		);
		$this->cache->put(
			'test.txt',
			array(
				'mimetype' => 'text/plain',
				'size' => 5,
				'mtime' => 123,
			)
		);
	}

	protected function tearDown() {
		if ($this->cache) {
			$this->cache->clear();
		}
		parent::tearDown();
	}

	public function testGetInjectsOwnerDisplayName() {
		$info = $this->cache->get('test.txt');
		$this->assertEquals(
			$this->remoteUser . '@example.com/owncloud',
			$info['displayname_owner']
		);
	}

	public function testGetReturnsFalseIfNotFound() {
		$info = $this->cache->get('unexisting-entry.txt');
		$this->assertFalse($info);
	}

	public function testGetFolderPopulatesOwner() {
		$dirId = $this->cache->put(
			'subdir',
			array(
				'mimetype' => 'httpd/unix-directory',
				'size' => 5,
				'mtime' => 123,
			)
		);
		$this->cache->put(
			'subdir/contents.txt',
			array(
				'mimetype' => 'text/plain',
				'size' => 5,
				'mtime' => 123,
			)
		);

		$results = $this->cache->getFolderContentsById($dirId);
		$this->assertEquals(1, count($results));
		$this->assertEquals(
			$this->remoteUser . '@example.com/owncloud',
			$results[0]['displayname_owner']
		);
	}

}
