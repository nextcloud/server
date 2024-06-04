<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Lockdown;

use OC\Authentication\Token\PublicKeyToken;
use OC\Lockdown\LockdownManager;
use OCP\ISession;
use Test\TestCase;

class LockdownManagerTest extends TestCase {
	private $sessionCallback;

	protected function setUp(): void {
		parent::setUp();

		$this->sessionCallback = function () {
			return $this->createMock(ISession::class);
		};
	}

	public function testCanAccessFilesystemDisabled() {
		$manager = new LockdownManager($this->sessionCallback);
		$this->assertTrue($manager->canAccessFilesystem());
	}

	public function testCanAccessFilesystemAllowed() {
		$token = new PublicKeyToken();
		$token->setScope(['filesystem' => true]);
		$manager = new LockdownManager($this->sessionCallback);
		$manager->setToken($token);
		$this->assertTrue($manager->canAccessFilesystem());
	}

	public function testCanAccessFilesystemNotAllowed() {
		$token = new PublicKeyToken();
		$token->setScope(['filesystem' => false]);
		$manager = new LockdownManager($this->sessionCallback);
		$manager->setToken($token);
		$this->assertFalse($manager->canAccessFilesystem());
	}
}
