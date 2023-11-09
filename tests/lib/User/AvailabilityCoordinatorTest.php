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
use OCA\DAV\Db\Absence;
use OCA\DAV\Db\AbsenceMapper;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IUser;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class AvailabilityCoordinatorTest extends TestCase {
	private AvailabilityCoordinator $availabilityCoordinator;
	private ICacheFactory $cacheFactory;
	private ICache $cache;
	private AbsenceMapper $absenceMapper;
	private LoggerInterface $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cache = $this->createMock(ICache::class);
		$this->absenceMapper = $this->createMock(AbsenceMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->cacheFactory->expects(self::once())
			->method('createLocal')
			->willReturn($this->cache);

		$this->availabilityCoordinator = new AvailabilityCoordinator(
			$this->cacheFactory,
			$this->absenceMapper,
			$this->logger,
		);
	}

	public function testGetOutOfOfficeData(): void {
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

		$this->cache->expects(self::once())
			->method('get')
			->with('user')
			->willReturn(null);
		$this->absenceMapper->expects(self::once())
			->method('findByUserId')
			->with('user')
			->willReturn($absence);
		$this->cache->expects(self::once())
			->method('set')
			->with('user', '{"id":"420","startDate":1696118400,"endDate":1696723200,"shortMessage":"Vacation","message":"On vacation"}', 300);

		$expected = new OutOfOfficeData(
			'420',
			$user,
			1696118400,
			1696723200,
			'Vacation',
			'On vacation',
		);
		$actual = $this->availabilityCoordinator->getCurrentOutOfOfficeData($user);
		self::assertEquals($expected, $actual);
	}

	public function testGetOutOfOfficeDataWithCachedData(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('user');

		$this->cache->expects(self::once())
			->method('get')
			->with('user')
			->willReturn('{"id":"420","startDate":1696118400,"endDate":1696723200,"shortMessage":"Vacation","message":"On vacation"}');
		$this->absenceMapper->expects(self::never())
			->method('findByUserId');
		$this->cache->expects(self::never())
			->method('set');

		$expected = new OutOfOfficeData(
			'420',
			$user,
			1696118400,
			1696723200,
			'Vacation',
			'On vacation',
		);
		$actual = $this->availabilityCoordinator->getCurrentOutOfOfficeData($user);
		self::assertEquals($expected, $actual);
	}

	public function testGetOutOfOfficeDataWithInvalidCachedData(): void {
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

		$this->cache->expects(self::once())
			->method('get')
			->with('user')
			->willReturn('{"id":"420",}');
		$this->absenceMapper->expects(self::once())
			->method('findByUserId')
			->with('user')
			->willReturn($absence);
		$this->cache->expects(self::once())
			->method('set')
			->with('user', '{"id":"420","startDate":1696118400,"endDate":1696723200,"shortMessage":"Vacation","message":"On vacation"}', 300);

		$expected = new OutOfOfficeData(
			'420',
			$user,
			1696118400,
			1696723200,
			'Vacation',
			'On vacation',
		);
		$actual = $this->availabilityCoordinator->getCurrentOutOfOfficeData($user);
		self::assertEquals($expected, $actual);
	}
}
