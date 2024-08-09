<?php declare(strict_types=1);

/*
 * @copyright 2024 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2024 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\DAV\Tests\Unit\DAV\Service;

use DateTimeImmutable;
use OCA\DAV\CalDAV\UpcomingEventsService;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\ICalendarQuery;
use OCP\Calendar\IManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpcomingEventsServiceTest extends TestCase {

	private MockObject|IManager $calendarManager;
	private ITimeFactory|MockObject $timeFactory;
	private IUserManager|MockObject $userManager;
	private IAppManager|MockObject $appManager;
	private IURLGenerator|MockObject $urlGenerator;
	private UpcomingEventsService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->calendarManager = $this->createMock(IManager::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->service = new UpcomingEventsService(
			$this->calendarManager,
			$this->timeFactory,
			$this->userManager,
			$this->appManager,
			$this->urlGenerator,
		);
	}

	public function testGetEventsByLocation(): void {
		$now = new DateTimeImmutable('2024-07-08T18:20:20Z');
		$this->timeFactory->method('now')
			->willReturn($now);
		$query = $this->createMock(ICalendarQuery::class);
		$this->calendarManager->method('newQuery')
			->with('principals/users/u1')
			->willReturn($query);
		$query->expects(self::once())
			->method('addSearchProperty')
			->with('LOCATION');
		$query->expects(self::once())
			->method('setSearchPattern')
			->with('https://cloud.example.com/call/123');
		$this->calendarManager->expects(self::once())
			->method('searchForPrincipal')
			->with($query)
			->willReturn([
				[
					'uri' => 'ev1',
					'calendar-key' => '1',
					'objects' => [
						0 => [
							'DTSTART' => [
								new DateTimeImmutable('now'),
							],
						],
					],
				],
			]);

		$events = $this->service->getEvents('user1', 'https://cloud.example.com/call/123');

		self::assertCount(1, $events);
		$event1 = $events[0];
		self::assertEquals('ev1', $event1['uri']);
	}
}
