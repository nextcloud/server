<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Lockdown\Filesystem;

use OC\Authentication\Token\PublicKeyToken;
use OC\Files\Filesystem;
use OC\Lockdown\Filesystem\NullStorage;
use OCP\Authentication\Token\IToken;
use OCP\Files\ISetupManager;
use OCP\Lockdown\ILockdownManager;
use OCP\Server;
use Test\Traits\UserTrait;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class NoFSTest extends \Test\TestCase {
	use UserTrait;

	#[\Override]
	protected function tearDown(): void {
		$token = new PublicKeyToken();
		$token->setScope([
			IToken::SCOPE_FILESYSTEM => true
		]);
		Server::get(ILockdownManager::class)->setToken($token);
		parent::tearDown();
	}

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$token = new PublicKeyToken();
		$token->setScope([
			IToken::SCOPE_FILESYSTEM => false
		]);

		Server::get(ILockdownManager::class)->setToken($token);
	}

	public function testSetupFS(): void {
		$user = $this->createUser('foo', 'var');
		Server::get(ISetupManager::class)->tearDown();
		Server::get(ISetupManager::class)->setupForUser($user);

		$this->assertInstanceOf(NullStorage::class, Filesystem::getStorage('/foo/files'));
	}
}
