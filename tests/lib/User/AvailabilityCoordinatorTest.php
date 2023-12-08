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

namespace Test\User;

use OC\User\AvailabilityCoordinator;
use OC\User\OutOfOfficeData;
use OCA\DAV\CalDAV\TimezoneService;
use OCA\DAV\Db\Absence;
use OCA\DAV\Service\AbsenceService;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class AvailabilityCoordinatorTest extends TestCase {
	private AvailabilityCoordinator $availabilityCoordinator;
	private ICacheFactory $cacheFactory;
	private ICache $cache;
	private IConfig|MockObject $config;
	private AbsenceService $absenceService;
	private LoggerInterface $logger;
	private MockObject|TimezoneService $timezoneService;

	protected function setUp(): void {
		parent::setUp();

		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cache = $this->createMock(ICache::class);
		$this->absenceService = $this->createMock(AbsenceService::class);
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->timezoneService = $this->createMock(TimezoneService::class);

		$this->cacheFactory->expects(self::once())
			->method('createLocal')
			->willReturn($this->cache);

		$this->availabilityCoordinator = new AvailabilityCoordinator(
			$this->cacheFactory,
			$this->config,
			$this->absenceService,
			$this->logger,
			$this->timezoneService,
		);
	}

	public function testIsEnabled(): void {
		$this->config->expects(self::once())
			->method('getAppValue')
			->with('dav', 'hide_absence_settings', 'no')
			->willReturn('no');

		$isEnabled = $this->availabilityCoordinator->isEnabled();

		self::assertTrue($isEnabled);
	}

	public function testGetOutOfOfficeDataInEffect(): void {
		$absence = new Absence();
		$absence->setId(420);
		$absence->setUserId('user');
		$absence->setFirstDay('2023-10-01');
		$absence->setLastDay('2023-10-08');
		$absence->setStatus('Vacation');
		$absence->setMessage('On vacation');
		$this->timezoneService->method('getUserTimezone')->with('user')->willReturn('Europe/Berlin');

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$this->cache->expects(self::exactly(2))
			->method('get')
			->willReturnOnConsecutiveCalls(null, null);
		$this->absenceService->expects(self::once())
			->method('getAbsence')
			->with($user->getUID())
			->willReturn($absence);
		$this->cache->expects(self::exactly(2))
			->method('set')
			->withConsecutive([$user->getUID() . '_timezone', 'Europe/Berlin', 3600],
				[$user->getUID(), '{"id":"420","startDate":1696111200,"endDate":1696802340,"shortMessage":"Vacation","message":"On vacation"}', 300]);

		$expected = new OutOfOfficeData(
			'420',
			$user,
			1696111200,
			1696802340,
			'Vacation',
			'On vacation',
		);
		$actual = $this->availabilityCoordinator->getCurrentOutOfOfficeData($user);
		self::assertEquals($expected, $actual);
	}

	public function testGetOutOfOfficeDataCachedAll(): void {
		$absence = new Absence();
		$absence->setId(420);
		$absence->setUserId('user');
		$absence->setFirstDay('2023-10-01');
		$absence->setLastDay('2023-10-08');
		$absence->setStatus('Vacation');
		$absence->setMessage('On vacation');

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$this->cache->expects(self::exactly(2))
			->method('get')
			->willReturnOnConsecutiveCalls('UTC', '{"id":"420","startDate":1696118400,"endDate":1696809540,"shortMessage":"Vacation","message":"On vacation"}');
		$this->absenceService->expects(self::never())
			->method('getAbsence');
		$this->cache->expects(self::exactly(1))
			->method('set');

		$expected = new OutOfOfficeData(
			'420',
			$user,
			1696118400,
			1696809540,
			'Vacation',
			'On vacation',
		);
		$actual = $this->availabilityCoordinator->getCurrentOutOfOfficeData($user);
		self::assertEquals($expected, $actual);
	}

	public function testGetOutOfOfficeDataNoData(): void {
		$absence = new Absence();
		$absence->setId(420);
		$absence->setUserId('user');
		$absence->setFirstDay('2023-10-01');
		$absence->setLastDay('2023-10-08');
		$absence->setStatus('Vacation');
		$absence->setMessage('On vacation');

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$this->cache->expects(self::exactly(2))
			->method('get')
			->willReturnOnConsecutiveCalls('UTC', null);
		$this->absenceService->expects(self::once())
			->method('getAbsence')
			->willReturn(null);
		$this->cache->expects(self::never())
			->method('set');

		$actual = $this->availabilityCoordinator->getCurrentOutOfOfficeData($user);
		self::assertNull($actual);
	}

	public function testGetOutOfOfficeDataWithInvalidCachedData(): void {
		$absence = new Absence();
		$absence->setId(420);
		$absence->setUserId('user');
		$absence->setFirstDay('2023-10-01');
		$absence->setLastDay('2023-10-08');
		$absence->setStatus('Vacation');
		$absence->setMessage('On vacation');
		$this->timezoneService->method('getUserTimezone')->with('user')->willReturn('Europe/Berlin');

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$this->cache->expects(self::exactly(2))
			->method('get')
			->willReturnOnConsecutiveCalls('UTC', '{"id":"420",}');
		$this->absenceService->expects(self::once())
			->method('getAbsence')
			->with('user')
			->willReturn($absence);
		$this->cache->expects(self::once())
			->method('set')
			->with('user', '{"id":"420","startDate":1696118400,"endDate":1696809540,"shortMessage":"Vacation","message":"On vacation"}', 300);

		$expected = new OutOfOfficeData(
			'420',
			$user,
			1696118400,
			1696809540,
			'Vacation',
			'On vacation',
		);
		$actual = $this->availabilityCoordinator->getCurrentOutOfOfficeData($user);
		self::assertEquals($expected, $actual);
	}
}
