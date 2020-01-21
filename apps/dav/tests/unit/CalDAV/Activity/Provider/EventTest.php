<?php
/**
 * @copyright Copyright (c) 2020 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Tests\unit\CalDAV\Activity\Provider;

use InvalidArgumentException;
use OCA\DAV\CalDAV\Activity\Provider\Base;
use OCA\DAV\CalDAV\Activity\Provider\Event;
use OCP\Activity\IEventMerger;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\App\IAppManager;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;
use TypeError;

class EventTest extends TestCase {

	/** @var IUserManager|MockObject */
	protected $userManager;

	/** @var IGroupManager|MockObject */
	protected $groupManager;

	/** @var IURLGenerator|MockObject */
	protected $url;

	/** @var IProvider|Base|MockObject */
	protected $provider;

	/** @var IAppManager|MockObject */
	protected $appManager;

	/** @var IFactory|MockObject */
	protected $i10nFactory;

	/** @var IManager|MockObject */
	protected $activityManager;

	/** @var IEventMerger|MockObject */
	protected $eventMerger;

	protected function setUp(): void {
		parent::setUp();
		$this->i10nFactory = $this->createMock(IFactory::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->activityManager = $this->createMock(IManager::class);
		$this->url = $this->createMock(IURLGenerator::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->eventMerger = $this->createMock(IEventMerger::class);
		$this->provider = $this->getMockBuilder(Event::class)
			->setConstructorArgs([
				$this->i10nFactory,
				$this->url,
				$this->activityManager,
				$this->userManager,
				$this->groupManager,
				$this->eventMerger,
				$this->appManager
			])
			->setMethods(['parse'])
			->getMock();
	}

	public function dataGenerateObjectParameter() {
		$link = [
			'object_uri' => 'someuuid.ics',
			'calendar_uri' => 'personal',
			'owner' => 'someuser'
		];

		return [
			[23, 'c1', $link, true],
			[23, 'c1', $link, false],
			[42, 'c2', null],
		];
	}

	/**
	 * @dataProvider dataGenerateObjectParameter
	 * @param int $id
	 * @param string $name
	 * @param array|null $link
	 * @param bool $calendarAppEnabled
	 */
	public function testGenerateObjectParameter(int $id, string $name, ?array $link, bool $calendarAppEnabled = true) {
		if ($link) {
			$generatedLink = [
				'view' => 'dayGridMonth',
				'timeRange' => 'now',
				'mode' => 'sidebar',
				'objectId' => base64_encode('/remote.php/dav/calendars/' . $link['owner'] . '/' . $link['calendar_uri'] . '/' . $link['object_uri']),
				'recurrenceId' => 'next'
			];
			$this->appManager->expects($this->once())
				->method('isEnabledForUser')
				->with('calendar')
				->willReturn($calendarAppEnabled);
			if ($calendarAppEnabled) {
				$this->url->expects($this->once())
					->method('linkToRouteAbsolute')
					->with('calendar.view.indexview.timerange.edit', $generatedLink)
					->willReturn('fullLink');
			}
		}
		$objectParameter = ['id' => $id, 'name' => $name];
		if ($link) {
			$objectParameter['link'] = $link;
		}
		$result = [
			'type' => 'calendar-event',
			'id' => $id,
			'name' => $name,
		];
		if ($link && $calendarAppEnabled) {
			$result['link'] = 'fullLink';
		}
		$this->assertEquals($result, $this->invokePrivate($this->provider, 'generateObjectParameter', [$objectParameter]));
	}

	public function dataGenerateObjectParameterThrows() {
		return [
			['event', TypeError::class],
			[['name' => 'event']],
			[['id' => 42]],
		];
	}

	/**
	 * @dataProvider dataGenerateObjectParameterThrows
	 * @param mixed $eventData
	 * @param string $exception
	 */
	public function testGenerateObjectParameterThrows($eventData, string $exception = InvalidArgumentException::class) {
		$this->expectException($exception);

		$this->invokePrivate($this->provider, 'generateObjectParameter', [$eventData]);
	}
}
