<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Node;

use OC\Files\Node\Root;
use OC\Files\Storage\Temporary;
use OC\Files\View;
use OC\Memcache\ArrayCache;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Mount\IMountManager;
use OCP\ICacheFactory;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Test\Traits\UserTrait;

/**
 * Class IntegrationTest
 *
 * @group DB
 *
 * @package Test\Files\Node
 */
class IntegrationTest extends \Test\TestCase {
	use UserTrait;

	/**
	 * @var \OC\Files\Node\Root $root
	 */
	private $root;

	/**
	 * @var \OC\Files\Storage\Storage[]
	 */
	private $storages;

	/**
	 * @var \OC\Files\View $view
	 */
	private $view;

	protected function setUp(): void {
		parent::setUp();

		$manager = Server::get(IMountManager::class);

		\OC_Hook::clear('OC_Filesystem');

		$user = $this->createUser($this->getUniqueID('user'), '');
		$this->loginAsUser($user->getUID());
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createLocal')
			->willReturnCallback(function () {
				return new ArrayCache();
			});

		$this->view = new View();
		$this->root = new Root(
			$manager,
			$this->view,
			$user,
			Server::get(IUserMountCache::class),
			$this->createMock(LoggerInterface::class),
			$this->createMock(IUserManager::class),
			$this->createMock(IEventDispatcher::class),
			$cacheFactory,
		);
		$storage = new Temporary([]);
		$subStorage = new Temporary([]);
		$this->storages[] = $storage;
		$this->storages[] = $subStorage;
		$this->root->mount($storage, '/');
		$this->root->mount($subStorage, '/substorage/');
		$manager->removeMount('/' . $user->getUID());
	}

	protected function tearDown(): void {
		foreach ($this->storages as $storage) {
			$storage->getCache()->clear();
		}

		$this->logout();
		parent::tearDown();
	}

	public function testBasicFile(): void {
		$file = $this->root->newFile('/foo.txt');
		$this->assertCount(2, $this->root->getDirectoryListing());
		$this->assertTrue($this->root->nodeExists('/foo.txt'));
		$id = $file->getId();
		$this->assertInstanceOf('\OC\Files\Node\File', $file);
		$file->putContent('qwerty');
		$this->assertEquals('text/plain', $file->getMimeType());
		$this->assertEquals('qwerty', $file->getContent());
		$this->assertFalse($this->root->nodeExists('/bar.txt'));
		$target = $file->move('/bar.txt');
		$this->assertEquals($id, $target->getId());
		$this->assertEquals($id, $file->getId());
		$this->assertFalse($this->root->nodeExists('/foo.txt'));
		$this->assertTrue($this->root->nodeExists('/bar.txt'));
		$this->assertEquals('bar.txt', $file->getName());
		$this->assertEquals('bar.txt', $file->getInternalPath());

		$file->move('/substorage/bar.txt');
		$this->assertEquals($id, $file->getId());
		$this->assertEquals('qwerty', $file->getContent());
	}

	public function testBasicFolder(): void {
		$folder = $this->root->newFolder('/foo');
		$this->assertTrue($this->root->nodeExists('/foo'));
		$file = $folder->newFile('/bar');
		$this->assertTrue($this->root->nodeExists('/foo/bar'));
		$file->putContent('qwerty');

		$listing = $folder->getDirectoryListing();
		$this->assertEquals(1, count($listing));
		$this->assertEquals($file->getId(), $listing[0]->getId());
		$this->assertEquals($file->getStorage(), $listing[0]->getStorage());


		$rootListing = $this->root->getDirectoryListing();
		$this->assertEquals(2, count($rootListing));

		$folder->move('/asd');
		/**
		 * @var \OC\Files\Node\File $file
		 */
		$file = $folder->get('/bar');
		$this->assertInstanceOf('\OC\Files\Node\File', $file);
		$this->assertFalse($this->root->nodeExists('/foo/bar'));
		$this->assertTrue($this->root->nodeExists('/asd/bar'));
		$this->assertEquals('qwerty', $file->getContent());
		$folder->move('/substorage/foo');
		/**
		 * @var \OC\Files\Node\File $file
		 */
		$file = $folder->get('/bar');
		$this->assertInstanceOf('\OC\Files\Node\File', $file);
		$this->assertTrue($this->root->nodeExists('/substorage/foo/bar'));
		$this->assertEquals('qwerty', $file->getContent());
	}
}
