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
 * The size of an encrypted file is kept in `unencrypted_size`, which every reader
 * prefers over `size`. A copy has to carry it over, otherwise the copy is reported
 * as empty.
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class CopySizeTest extends TestCase {
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

	public function testCopyKeepsTheSize(): void {
		$view = $this->setUpView();

		$view->file_put_contents('source.bin', str_repeat('a', 20000));
		$this->assertTrue($view->copy('source.bin', 'target.bin'));

		$this->assertEquals(20000, $view->getFileInfo('target.bin')->getSize());
	}

	public function testCopyToAnotherStorageKeepsTheSize(): void {
		$view = $this->setUpView();

		$view->file_put_contents('source.bin', str_repeat('a', 20000));
		$this->assertTrue($view->copy('source.bin', 'other/target.bin'));

		$this->assertEquals(20000, $view->getFileInfo('other/target.bin')->getSize());
	}

	public function testCopyOfAFolderKeepsTheSizes(): void {
		$view = $this->setUpView();

		$view->mkdir('source');
		$view->file_put_contents('source/file.bin', str_repeat('a', 20000));
		$this->assertTrue($view->copy('source', 'target'));

		$this->assertEquals(20000, $view->getFileInfo('target/file.bin')->getSize());
	}
}
