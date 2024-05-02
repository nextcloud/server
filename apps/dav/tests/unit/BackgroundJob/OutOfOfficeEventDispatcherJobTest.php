<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Tests\unit\BackgroundJob;

use OCA\DAV\BackgroundJob\OutOfOfficeEventDispatcherJob;
use OCA\DAV\CalDAV\TimezoneService;
use OCA\DAV\Db\Absence;
use OCA\DAV\Db\AbsenceMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\IUserManager;
use OCP\User\Events\OutOfOfficeEndedEvent;
use OCP\User\Events\OutOfOfficeStartedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class OutOfOfficeEventDispatcherJobTest extends TestCase {
	private OutOfOfficeEventDispatcherJob $job;

	/** @var MockObject|ITimeFactory */
	private $timeFactory;

	/** @var MockObject|AbsenceMapper */
	private $absenceMapper;

	/** @var MockObject|LoggerInterface */
	private $logger;

	/** @var MockObject|IEventDispatcher */
	private $eventDispatcher;

	/** @var MockObject|IUserManager */
	private $userManager;
	private MockObject|TimezoneService $timezoneService;

	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->absenceMapper = $this->createMock(AbsenceMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->timezoneService = $this->createMock(TimezoneService::class);

		$this->job = new OutOfOfficeEventDispatcherJob(
			$this->timeFactory,
			$this->absenceMapper,
			$this->logger,
			$this->eventDispatcher,
			$this->userManager,
			$this->timezoneService,
		);
	}

	public function testDispatchStartEvent() {
		$this->timezoneService->method('getUserTimezone')->with('user')->willReturn('Europe/Berlin');

		$absence = new Absence();
		$absence->setId(200);
		$absence->setUserId('user');

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$this->absenceMapper->expects(self::once())
			->method('findById')
			->with(1)
			->willReturn($absence);
		$this->userManager->expects(self::once())
			->method('get')
			->with('user')
			->willReturn($user);
		$this->eventDispatcher->expects(self::once())
			->method('dispatchTyped')
			->with(self::callback(static function ($event): bool {
				self::assertInstanceOf(OutOfOfficeStartedEvent::class, $event);
				return true;
			}));

		$this->job->run([
			'id' => 1,
			'event' => OutOfOfficeEventDispatcherJob::EVENT_START,
		]);
	}

	public function testDispatchStopEvent() {
		$this->timezoneService->method('getUserTimezone')->with('user')->willReturn('Europe/Berlin');

		$absence = new Absence();
		$absence->setId(200);
		$absence->setUserId('user');

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$this->absenceMapper->expects(self::once())
			->method('findById')
			->with(1)
			->willReturn($absence);
		$this->userManager->expects(self::once())
			->method('get')
			->with('user')
			->willReturn($user);
		$this->eventDispatcher->expects(self::once())
			->method('dispatchTyped')
			->with(self::callback(static function ($event): bool {
				self::assertInstanceOf(OutOfOfficeEndedEvent::class, $event);
				return true;
			}));

		$this->job->run([
			'id' => 1,
			'event' => OutOfOfficeEventDispatcherJob::EVENT_END,
		]);
	}

	public function testDoesntDispatchUnknownEvent() {
		$this->timezoneService->method('getUserTimezone')->with('user')->willReturn('Europe/Berlin');

		$absence = new Absence();
		$absence->setId(100);
		$absence->setUserId('user');

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$this->absenceMapper->expects(self::once())
			->method('findById')
			->with(1)
			->willReturn($absence);
		$this->userManager->expects(self::once())
			->method('get')
			->with('user')
			->willReturn($user);
		$this->eventDispatcher->expects(self::never())
			->method('dispatchTyped');
		$this->logger->expects(self::once())
			->method('error');

		$this->job->run([
			'id' => 1,
			'event' => 'foobar',
		]);
	}
}
