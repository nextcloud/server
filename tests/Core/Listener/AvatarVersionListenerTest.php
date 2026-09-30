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
use PHPUnit\Framework\MockObject\MockObject;

class AvatarVersionListenerTest extends \Test\TestCase {
	private IUserConfig&MockObject $userConfig;
	private AvatarVersionListener $listener;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->userConfig = $this->createMock(IUserConfig::class);
		$this->listener = new AvatarVersionListener($this->userConfig);
	}

	public function testBumpsTheVersionWhenTheAccountChanges(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$this->userConfig->expects($this->once())->method('setValueInt')
			->with('alice', 'avatar', 'version', 1);

		$this->listener->handle(new UserUpdatedEvent($user, []));
	}

	public function testBumpsTheVersionWhenTheAccountIsDisabled(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$this->userConfig->expects($this->once())->method('setValueInt')
			->with('alice', 'avatar', 'version', 1);

		$this->listener->handle(new UserChangedEvent($user, 'enabled', false, true));
	}

	public function testIgnoresUnrelatedUserChanges(): void {
		$user = $this->createMock(IUser::class);

		$this->userConfig->expects($this->never())->method('setValueInt');

		$this->listener->handle(new UserChangedEvent($user, 'quota', '1 GB', '2 GB'));
	}

	public function testIgnoresOtherEvents(): void {
		$this->userConfig->expects($this->never())->method('setValueInt');

		$this->listener->handle(new Event());
	}
}
