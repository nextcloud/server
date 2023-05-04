<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
