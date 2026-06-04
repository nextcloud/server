<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests\External;

use OC\Federation\CloudIdManager;
use OC\Files\Storage\Storage;
use OCA\Files_Sharing\External\Cache;
use OCA\Files_Sharing\Tests\TestCase;
use OCP\Contacts\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudIdManager;
use OCP\Files\Cache\ICacheEntry;
use OCP\ICacheFactory;
use OCP\IURLGenerator;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class Cache
 *
 *
 * @package OCA\Files_Sharing\Tests\External
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class CacheTest extends TestCase {
	protected IManager&MockObject $contactsManager;
	private Storage&MockObject $storage;
	private Cache $cache;
	private string $remoteUser;
	private ICloudIdManager $cloudIdManager;

	protected function setUp(): void {
		parent::setUp();

		$this->contactsManager = $this->createMock(IManager::class);

		$this->cloudIdManager = new CloudIdManager(
			$this->createMock(ICacheFactory::class),
			$this->createMock(IEventDispatcher::class),
			$this->contactsManager,
			$this->createMock(IURLGenerator::class),
			$this->createMock(IUserManager::class),
		);
		$this->remoteUser = $this->getUniqueID('remoteuser');

		$this->storage = $this->getMockBuilder(\OCA\Files_Sharing\External\Storage::class)
			->disableOriginalConstructor()
			->getMock();
		$this->storage
			->expects($this->any())
			->method('getId')
			->willReturn('dummystorage::');

		$this->contactsManager->expects($this->any())
			->method('search')
			->willReturn([]);

		$this->cache = new Cache(
			$this->storage,
			$this->cloudIdManager->getCloudId($this->remoteUser, 'http://example.com/owncloud')
		);
		$this->cache->insert('', ['size' => 0, 'mtime' => 0, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$this->cache->put(
			'test.txt',
			[
				'mimetype' => 'text/plain',
				'size' => 5,
				'mtime' => 123,
			]
		);
	}

	protected function tearDown(): void {
		if ($this->cache) {
			$this->cache->clear();
		}
		parent::tearDown();
	}

	public function testGetInjectsOwnerDisplayName(): void {
		$info = $this->cache->get('test.txt');
		$this->assertEquals(
			$this->remoteUser . '@example.com/owncloud',
			$info['displayname_owner']
		);
	}

	public function testGetReturnsFalseIfNotFound(): void {
		$info = $this->cache->get('unexisting-entry.txt');
		$this->assertFalse($info);
	}

	public function testGetFolderPopulatesOwner(): void {
		$dirId = $this->cache->put(
			'subdir',
			[
				'mimetype' => 'httpd/unix-directory',
				'size' => 5,
				'mtime' => 123,
			]
		);
		$this->cache->put(
			'subdir/contents.txt',
			[
				'mimetype' => 'text/plain',
				'size' => 5,
				'mtime' => 123,
			]
		);

		$results = $this->cache->getFolderContentsById($dirId);
		$this->assertEquals(1, count($results));
		$this->assertEquals(
			$this->remoteUser . '@example.com/owncloud',
			$results[0]['displayname_owner']
		);
	}
}
