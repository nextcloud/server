<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Listener;

use OC\Core\Listener\AvatarVersionListener;
use OCP\Accounts\UserUpdatedEvent;
use OCP\Config\IUserConfig;
use OCP\EventDispatcher\Event;
use OCP\IUser;
use OCP\User\Events\UserChangedEvent;

class AvatarVersionListenerTest extends \Test\TestCase {
	private AvatarVersionListener $listener;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->listener = $this->createInstanceWithMocks(AvatarVersionListener::class);
	}

	public function testBumpsTheVersionWhenTheAccountChanges(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$this->mocks[IUserConfig::class]->expects($this->once())->method('setValueInt')
			->with('alice', 'avatar', 'version', 1);

		$this->listener->handle(new UserUpdatedEvent($user, []));
	}

	public function testBumpsTheVersionWhenTheAccountIsDisabled(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$this->mocks[IUserConfig::class]->expects($this->once())->method('setValueInt')
			->with('alice', 'avatar', 'version', 1);

		$this->listener->handle(new UserChangedEvent($user, 'enabled', false, true));
	}

	public function testIgnoresUnrelatedUserChanges(): void {
		$user = $this->createMock(IUser::class);

		$this->mocks[IUserConfig::class]->expects($this->never())->method('setValueInt');

		$this->listener->handle(new UserChangedEvent($user, 'quota', '1 GB', '2 GB'));
	}

	public function testIgnoresOtherEvents(): void {
		$this->mocks[IUserConfig::class]->expects($this->never())->method('setValueInt');

		$this->listener->handle(new Event());
	}
}
