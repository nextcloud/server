<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Tests\Listener;

use OCA\UserStatus\Listener\UserDeletedListener;
use OCA\UserStatus\Service\StatusService;
use OCP\EventDispatcher\GenericEvent;
use OCP\IUser;
use OCP\User\Events\UserDeletedEvent;
use Test\TestCase;

class UserDeletedListenerTest extends TestCase {

	/** @var StatusService|\PHPUnit\Framework\MockObject\MockObject */
	private $service;

	/** @var UserDeletedListener */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->service = $this->createMock(StatusService::class);
		$this->listener = new UserDeletedListener($this->service);
	}

	public function testHandleWithCorrectEvent(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('john.doe');

		$this->service->expects($this->once())
			->method('removeUserStatus')
			->with('john.doe');

		$event = new UserDeletedEvent($user);
		$this->listener->handle($event);
	}

	public function testHandleWithWrongEvent(): void {
		$this->service->expects($this->never())
			->method('removeUserStatus');

		$event = new GenericEvent();
		$this->listener->handle($event);
	}
}
