<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\encryption\tests;

use OC\Files\Storage\Temporary;
use OC\Files\View;
use OCA\Encryption\KeyManager;
use OCP\Server;
use Test\TestCase;
use Test\Traits\EncryptionTrait;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * A copy re-encrypts the target, so its blocks are signed with the version that
 * follows the version of the file that was overwritten - neither with the source's
 * version nor with version 1. Reading the target back only works if the cache
 * entry holds that same version.
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class EncryptedCopyTest extends TestCase {
	use MountProviderTrait;
	use EncryptionTrait;
	use UserTrait;

	private function setUpView(): View {
		Server::get(KeyManager::class)->validateMasterKey();
		Server::get(KeyManager::class)->validateShareKey();
		$this->createUser('test1', 'test2');
		$this->setupForUser('test1', 'test2');
		$this->registerMount('test1', new Temporary(), '/test1/files/other');
		$this->loginWithEncryption('test1');

		return new View('/test1/files');
	}

	/**
	 * The version the target ends up at depends on the storage: a copy unlinks the
	 * target first, which keeps the cache entry - and with it the version to bump -
	 * on a local storage but drops it on an object store, where the copy therefore
	 * starts over at version 1. Reading the target back is what shows that the
	 * recorded version is the one its blocks were signed with.
	 */
	public function testCopyOverExistingFile(): void {
		$view = $this->setUpView();
		$source = str_repeat('a', 20000);

		$view->file_put_contents('source.bin', $source);
		$view->file_put_contents('target.bin', str_repeat('b', 20000));

		$this->assertTrue($view->copy('source.bin', 'target.bin'));

		$this->assertEquals($source, $view->file_get_contents('target.bin'));
	}

	/**
	 * Every write bumps the version of the source, while the copy of it starts over
	 * at the version of the target.
	 */
	public function testCopyFileWrittenSeveralTimes(): void {
		$view = $this->setUpView();
		$source = str_repeat('c', 20000);

		$view->file_put_contents('source.bin', str_repeat('a', 20000));
		$view->file_put_contents('source.bin', str_repeat('b', 20000));
		$view->file_put_contents('source.bin', $source);

		$this->assertTrue($view->copy('source.bin', 'target.bin'));

		$this->assertEquals($source, $view->file_get_contents('target.bin'));
	}

	public function testCopyOverExistingFileWrittenSeveralTimes(): void {
		$view = $this->setUpView();
		$source = str_repeat('a', 20000);

		$view->file_put_contents('source.bin', $source);
		$view->file_put_contents('target.bin', str_repeat('b', 20000));
		$view->file_put_contents('target.bin', str_repeat('c', 20000));

		$this->assertTrue($view->copy('source.bin', 'target.bin'));

		$this->assertEquals($source, $view->file_get_contents('target.bin'));
	}

	public function testCopyFolderOverExistingFolder(): void {
		$view = $this->setUpView();
		$source = str_repeat('a', 20000);

		$view->mkdir('source');
		$view->file_put_contents('source/file.bin', $source);
		$view->mkdir('target');
		$view->file_put_contents('target/file.bin', str_repeat('b', 20000));

		$this->assertTrue($view->copy('source', 'target'));

		$this->assertEquals($source, $view->file_get_contents('target/file.bin'));
	}

	public function testMoveOverExistingFileOnAnotherStorage(): void {
		$view = $this->setUpView();
		$source = str_repeat('a', 20000);

		$view->file_put_contents('source.bin', $source);
		$view->file_put_contents('other/target.bin', str_repeat('b', 20000));

		$this->assertTrue($view->rename('source.bin', 'other/target.bin'));

		$this->assertEquals($source, $view->file_get_contents('other/target.bin'));
	}

	/**
	 * The target is not unlinked when it lives on another storage, so it keeps its
	 * cache entry and the copy is signed with the version that follows the one of
	 * the file it overwrites.
	 */
	public function testCopyOverExistingFileOnAnotherStorage(): void {
		$view = $this->setUpView();
		$source = str_repeat('a', 20000);

		$view->file_put_contents('source.bin', $source);
		$view->file_put_contents('other/target.bin', str_repeat('b', 20000));

		$this->assertTrue($view->copy('source.bin', 'other/target.bin'));

		$this->assertEquals($source, $view->file_get_contents('other/target.bin'));
		$this->assertEquals(
			2,
			$view->getFileInfo('other/target.bin')->getEncryptedVersion(),
			'the version of the overwritten file was not bumped'
		);
	}
}
