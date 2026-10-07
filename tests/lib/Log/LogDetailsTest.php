<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Log;

use OC\Log\LogDetails;
use OC\SystemConfig;
use OCP\ISession;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class LogDetailsTest extends TestCase {
	private LogDetails $logDetails;
	private IUserManager&MockObject $userManager;
	private bool $incognitoMode;

	protected function setUp(): void {
		parent::setUp();

		// Incognito mode hides the session user, e.g. while an upgrade is pending
		$this->incognitoMode = \OC_User::isIncognitoMode();
		\OC_User::setIncognitoMode(false);

		$config = $this->createMock(SystemConfig::class);
		$config->method('getValue')
			->willReturnCallback(static fn (string $key, mixed $default = '') => $key === 'installed' ? true : $default);
		$this->logDetails = new class($config) extends LogDetails {
		};

		$this->userManager = $this->createMock(IUserManager::class);
	}

	protected function tearDown(): void {
		\OC_User::setIncognitoMode($this->incognitoMode);
		$this->restoreAllServices();
		parent::tearDown();
	}

	private function setSessionUser(?string $userId): void {
		$session = $this->createMock(ISession::class);
		$session->method('get')->willReturnCallback(static fn (string $key) => $key === 'user_id' ? $userId : null);
		$this->overwriteService(ISession::class, $session);
	}

	public function testDisplayNameIsAddedNextToUser(): void {
		$this->setSessionUser('a3f9c2e1');
		$this->userManager->method('getDisplayName')->with('a3f9c2e1')->willReturn('Alice Martin');
		$this->overwriteService(IUserManager::class, $this->userManager);

		$entry = $this->logDetails->logDetails('admin_audit', 'File deleted', 1);

		$this->assertSame('a3f9c2e1', $entry['user']);
		$this->assertSame('Alice Martin', $entry['userDisplayName']);
		$keys = array_keys($entry);
		$this->assertSame(array_search('user', $keys, true) + 1, array_search('userDisplayName', $keys, true));
	}

	public function testDisplayNameIsOmittedWithoutLoggedInUser(): void {
		$this->setSessionUser(null);
		$this->userManager->expects($this->never())->method('getDisplayName');
		$this->overwriteService(IUserManager::class, $this->userManager);

		$entry = $this->logDetails->logDetails('admin_audit', 'File deleted', 1);

		$this->assertSame('--', $entry['user']);
		$this->assertArrayNotHasKey('userDisplayName', $entry);
	}

	public function testDisplayNameIsOmittedWhenUnknown(): void {
		$this->setSessionUser('a3f9c2e1');
		$this->userManager->method('getDisplayName')->willReturn(null);
		$this->overwriteService(IUserManager::class, $this->userManager);

		$entry = $this->logDetails->logDetails('admin_audit', 'File deleted', 1);

		$this->assertArrayNotHasKey('userDisplayName', $entry);
	}

	public function testDisplayNameIsOmittedWhenLookupFails(): void {
		$this->setSessionUser('a3f9c2e1');
		$this->userManager->method('getDisplayName')->willThrowException(new \RuntimeException('LDAP unavailable'));
		$this->overwriteService(IUserManager::class, $this->userManager);

		$entry = $this->logDetails->logDetails('admin_audit', 'File deleted', 1);

		$this->assertSame('a3f9c2e1', $entry['user']);
		$this->assertArrayNotHasKey('userDisplayName', $entry);
	}

	public function testDisplayNameLookupDoesNotRecurse(): void {
		$this->setSessionUser('a3f9c2e1');
		$innerEntry = null;
		$this->userManager->expects($this->once())
			->method('getDisplayName')
			->willReturnCallback(function () use (&$innerEntry): string {
				$innerEntry = $this->logDetails->logDetails('user_ldap', 'Looking up display name', 0);
				return 'Alice Martin';
			});
		$this->overwriteService(IUserManager::class, $this->userManager);

		$entry = $this->logDetails->logDetails('admin_audit', 'File deleted', 1);

		$this->assertSame('Alice Martin', $entry['userDisplayName']);
		$this->assertIsArray($innerEntry);
		$this->assertArrayNotHasKey('userDisplayName', $innerEntry);
	}
}
