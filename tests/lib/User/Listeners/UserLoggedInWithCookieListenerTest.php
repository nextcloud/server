<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\User\Listeners;

use OC\User\LastInteractiveLogin;
use OC\User\Listeners\UserLoggedInWithCookieListener;
use OCP\IUser;
use OCP\User\Events\UserLoggedInEvent;
use OCP\User\Events\UserLoggedInWithCookieEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UserLoggedInWithCookieListenerTest extends TestCase {
	/** @var LastInteractiveLogin|MockObject */
	private $lastInteractiveLogin;
	private UserLoggedInWithCookieListener $listener;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->lastInteractiveLogin = $this->createMock(LastInteractiveLogin::class);
		$this->listener = new UserLoggedInWithCookieListener($this->lastInteractiveLogin);
	}

	public function testRecordsRememberMeLogin(): void {
		$user = $this->createMock(IUser::class);
		$this->lastInteractiveLogin->expects($this->once())
			->method('record')
			->with($user);

		$this->listener->handle(new UserLoggedInWithCookieEvent($user, null));
	}

	public function testIgnoresUnrelatedEvent(): void {
		$user = $this->createMock(IUser::class);
		$this->lastInteractiveLogin->expects($this->never())
			->method('record');

		$this->listener->handle(new UserLoggedInEvent($user, 'user', null, false));
	}
}
