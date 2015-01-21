<?php
namespace OCA\Files_sharing\Tests\External;

use OCA\Files_sharing\Tests\TestCase;

/**
 * ownCloud
 *
 * @author Vincent Petry
 * @copyright 2015 Vincent Petry <pvince81@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
class Cache extends TestCase {

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
		$this->cache->clear();
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
