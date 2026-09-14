<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Listener;

use OC\Avatar\AvatarVersion;
use OC\Core\Listener\AvatarVersionListener;
use OCP\Accounts\UserUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\IUser;
use OCP\User\Events\UserChangedEvent;
use PHPUnit\Framework\MockObject\MockObject;

class AvatarVersionListenerTest extends \Test\TestCase {
	private AvatarVersion&MockObject $avatarVersion;
	private AvatarVersionListener $listener;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->avatarVersion = $this->createMock(AvatarVersion::class);
		$this->listener = new AvatarVersionListener($this->avatarVersion);
	}

	public function testBumpsTheVersionWhenTheAccountChanges(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$this->avatarVersion->expects($this->once())->method('bump')->with('alice');

		$this->listener->handle(new UserUpdatedEvent($user, []));
	}

	public function testBumpsTheVersionWhenTheAccountIsDisabled(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$this->avatarVersion->expects($this->once())->method('bump')->with('alice');

		$this->listener->handle(new UserChangedEvent($user, 'enabled', false, true));
	}

	public function testIgnoresUnrelatedUserChanges(): void {
		$user = $this->createMock(IUser::class);

		$this->avatarVersion->expects($this->never())->method('bump');

		$this->listener->handle(new UserChangedEvent($user, 'quota', '1 GB', '2 GB'));
	}

	public function testIgnoresOtherEvents(): void {
		$this->avatarVersion->expects($this->never())->method('bump');

		$this->listener->handle(new Event());
	}
}
