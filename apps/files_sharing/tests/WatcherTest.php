<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\Files\Cache\Cache;
use OC\Files\Storage\Storage;
use OC\Files\View;
use OCP\Constants;
use OCP\Share\IShare;

/**
 * Class WatcherTest
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class WatcherTest extends TestCase {

	/** @var Storage */
	private $ownerStorage;

	/** @var Cache */
	private $ownerCache;

	/** @var Storage */
	private $sharedStorage;

	/** @var Cache */
	private $sharedCache;

	/** @var IShare */
	private $_share;

	protected function setUp(): void {
		parent::setUp();

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		// prepare user1's dir structure
		$this->view->mkdir('container');
		$this->view->mkdir('container/shareddir');
		$this->view->mkdir('container/shareddir/subdir');

		[$this->ownerStorage, $internalPath] = $this->view->resolvePath('');
		$this->ownerCache = $this->ownerStorage->getCache();
		$this->ownerStorage->getScanner()->scan('');

		// share "shareddir" with user2
		$this->_share = $this->share(
			IShare::TYPE_USER,
			'container/shareddir',
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL
		);

		$this->_share->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($this->_share);

		// login as user2
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// retrieve the shared storage
		$secondView = new View('/' . self::TEST_FILES_SHARING_API_USER2);
		[$this->sharedStorage, $internalPath] = $secondView->resolvePath('files/shareddir');
		$this->sharedCache = $this->sharedStorage->getCache();
	}

	protected function tearDown(): void {
		if ($this->sharedCache) {
			$this->sharedCache->clear();
		}

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		if ($this->view) {
			$this->shareManager->deleteShare($this->_share);

			$this->view->deleteAll('container');

			$this->ownerCache->clear();
		}

		parent::tearDown();
	}

	/**
	 * Tests that writing a file using the shared storage will propagate the file
	 * size to the owner's parent folders.
	 */
	public function testFolderSizePropagationToOwnerStorage(): void {
		$initialSizes = self::getOwnerDirSizes('files/container/shareddir');

		$textData = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$dataLen = strlen($textData);
		$this->sharedCache->put('bar.txt', ['mtime' => 10, 'storage_mtime' => 10, 'size' => $dataLen, 'mimetype' => 'text/plain']);
		$this->sharedStorage->file_put_contents('bar.txt', $textData);
		$this->sharedCache->put('', ['mtime' => 10, 'storage_mtime' => 10, 'size' => '-1', 'mimetype' => 'httpd/unix-directory']);

		// run the propagation code
		$this->sharedStorage->getWatcher()->checkUpdate('');
		$this->sharedStorage->getCache()->correctFolderSize('');

		// the owner's parent dirs must have increase size
		$newSizes = self::getOwnerDirSizes('files/container/shareddir');
		$this->assertEquals($initialSizes[''] + $dataLen, $newSizes['']);
		$this->assertEquals($initialSizes['files'] + $dataLen, $newSizes['files']);
		$this->assertEquals($initialSizes['files/container'] + $dataLen, $newSizes['files/container']);
		$this->assertEquals($initialSizes['files/container/shareddir'] + $dataLen, $newSizes['files/container/shareddir']);

		// no more updates
		$result = $this->sharedStorage->getWatcher()->checkUpdate('');

		$this->assertFalse($result);
	}

	/**
	 * Tests that writing a file using the shared storage will propagate the file
	 * size to the owner's parent folders.
	 */
	public function testSubFolderSizePropagationToOwnerStorage(): void {
		$initialSizes = self::getOwnerDirSizes('files/container/shareddir/subdir');

		$textData = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$dataLen = strlen($textData);
		$this->sharedCache->put('subdir/bar.txt', ['mtime' => 10, 'storage_mtime' => 10, 'size' => $dataLen, 'mimetype' => 'text/plain']);
		$this->sharedStorage->file_put_contents('subdir/bar.txt', $textData);
		$this->sharedCache->put('subdir', ['mtime' => 10, 'storage_mtime' => 10, 'size' => $dataLen, 'mimetype' => 'text/plain']);

		// run the propagation code
		$this->sharedStorage->getWatcher()->checkUpdate('subdir');
		$this->sharedStorage->getCache()->correctFolderSize('subdir');

		// the owner's parent dirs must have increase size
		$newSizes = self::getOwnerDirSizes('files/container/shareddir/subdir');
		$this->assertEquals($initialSizes[''] + $dataLen, $newSizes['']);
		$this->assertEquals($initialSizes['files'] + $dataLen, $newSizes['files']);
		$this->assertEquals($initialSizes['files/container'] + $dataLen, $newSizes['files/container']);
		$this->assertEquals($initialSizes['files/container/shareddir'] + $dataLen, $newSizes['files/container/shareddir']);
		$this->assertEquals($initialSizes['files/container/shareddir/subdir'] + $dataLen, $newSizes['files/container/shareddir/subdir']);

		// no more updates
		$result = $this->sharedStorage->getWatcher()->checkUpdate('subdir');

		$this->assertFalse($result);
	}

	/**
	 * Returns the sizes of the path and its parent dirs in a hash
	 * where the key is the path and the value is the size.
	 * @param string $path
	 */
	public function getOwnerDirSizes($path) {
		$result = [];

		while ($path != '' && $path != '' && $path != '.') {
			$cachedData = $this->ownerCache->get($path);
			$result[$path] = $cachedData['size'];
			$path = dirname($path);
		}
		$cachedData = $this->ownerCache->get('');
		$result[''] = $cachedData['size'];
		return $result;
	}
}
