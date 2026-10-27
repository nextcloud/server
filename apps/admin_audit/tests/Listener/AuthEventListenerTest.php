<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Listener;

use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\AuthEventListener;
use OCP\Authentication\Events\AnyLoginFailedEvent;
use OCP\IUser;
use OCP\User\Events\BeforeUserLoggedInEvent;
use OCP\User\Events\BeforeUserLoggedOutEvent;
use OCP\User\Events\UserLoggedInEvent;
use OCP\User\Events\UserLoggedInWithCookieEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AuthEventListenerTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private AuthEventListener $listener;
	private IUser&MockObject $user;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->listener = new AuthEventListener($this->logger);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('alice');
	}

	private function expectInfo(string $message, string $operation): void {
		$this->logger->expects($this->once())
			->method('info')
			->with($message, ['app' => 'admin_audit', 'operation' => $operation]);
	}

	public function testLoginAttempt(): void {
		$this->expectInfo('Login attempt: "alice"', 'auth.login.attempted');
		$this->listener->handle(new BeforeUserLoggedInEvent('alice', 'password'));
	}

	public function testLoginSucceeded(): void {
		$this->expectInfo('Login successful: "alice"', 'auth.login.succeeded');
		$this->listener->handle(new UserLoggedInEvent($this->user, 'alice', 'password', false));
	}

	public function testLoginWithCookieSucceeded(): void {
		$this->expectInfo('Login successful: "alice"', 'auth.login.succeeded');
		$this->listener->handle(new UserLoggedInWithCookieEvent($this->user, null));
	}

	public function testLogout(): void {
		$this->expectInfo('Logout occurred', 'auth.logout.performed');
		$this->listener->handle(new BeforeUserLoggedOutEvent($this->user));
	}

	public function testLoginFailed(): void {
		$this->expectInfo('Login failed: "alice"', 'auth.login.failed');
		$this->listener->handle(new AnyLoginFailedEvent('alice', 'password'));
	}
}
