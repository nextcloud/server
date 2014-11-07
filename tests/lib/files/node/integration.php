<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Node;

use OC\Files\Cache\Cache;
use OC\Files\Node\Root;
use OC\Files\Storage\Temporary;
use OC\Files\View;
use OC\User\User;

class IntegrationTests extends \Test\TestCase {
	/**
	 * @var \OC\Files\Node\Root $root
	 */
	private $root;

	/** @var \OC\Files\Storage\Storage */
	private $originalStorage;

	/**
	 * @var \OC\Files\Storage\Storage[]
	 */
	private $storages;

	/**
	 * @var \OC\Files\View $view
	 */
	private $view;

	protected function setUp() {
		parent::setUp();

		$this->originalStorage = \OC\Files\Filesystem::getStorage('/');
		\OC\Files\Filesystem::init('', '');
		\OC\Files\Filesystem::clearMounts();
		$manager = \OC\Files\Filesystem::getMountManager();

		\OC_Hook::clear('OC_Filesystem');

		\OC_Hook::connect('OC_Filesystem', 'post_write', '\OC\Files\Cache\Updater', 'writeHook');
		\OC_Hook::connect('OC_Filesystem', 'post_delete', '\OC\Files\Cache\Updater', 'deleteHook');
		\OC_Hook::connect('OC_Filesystem', 'post_rename', '\OC\Files\Cache\Updater', 'renameHook');
		\OC_Hook::connect('OC_Filesystem', 'post_touch', '\OC\Files\Cache\Updater', 'touchHook');

		$user = new User(uniqid('user'), new \OC_User_Dummy);
		\OC_User::setUserId($user->getUID());
		$this->view = new View();
		$this->root = new Root($manager, $this->view, $user);
		$storage = new Temporary(array());
		$subStorage = new Temporary(array());
		$this->storages[] = $storage;
		$this->storages[] = $subStorage;
		$this->root->mount($storage, '/');
		$this->root->mount($subStorage, '/substorage/');
	}

	protected function tearDown() {
		foreach ($this->storages as $storage) {
			$storage->getCache()->clear();
		}
		\OC\Files\Filesystem::clearMounts();
		\OC\Files\Filesystem::mount($this->originalStorage, array(), '/');

		parent::tearDown();
	}

	public function testBasicFile() {
		$file = $this->root->newFile('/foo.txt');
		$this->assertCount(2, $this->root->getDirectoryListing());
		$this->assertTrue($this->root->nodeExists('/foo.txt'));
		$id = $file->getId();
		$this->assertInstanceOf('\OC\Files\Node\File', $file);
		$file->putContent('qwerty');
		$this->assertEquals('text/plain', $file->getMimeType());
		$this->assertEquals('qwerty', $file->getContent());
		$this->assertFalse($this->root->nodeExists('/bar.txt'));
		$file->move('/bar.txt');
		$this->assertFalse($this->root->nodeExists('/foo.txt'));
		$this->assertTrue($this->root->nodeExists('/bar.txt'));
		$this->assertEquals('bar.txt', $file->getName());
		$this->assertEquals('bar.txt', $file->getInternalPath());

		$file->move('/substorage/bar.txt');
		$this->assertNotEquals($id, $file->getId());
		$this->assertEquals('qwerty', $file->getContent());
	}

	public function testBasicFolder() {
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
