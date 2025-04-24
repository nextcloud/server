<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Activity\Provider;

use InvalidArgumentException;
use OCA\DAV\CalDAV\Activity\Provider\Event;
use OCP\Activity\IEventMerger;
use OCP\Activity\IManager;
use OCP\App\IAppManager;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;
use TypeError;

class EventTest extends TestCase {
	protected IUserManager&MockObject $userManager;
	protected IGroupManager&MockObject $groupManager;
	protected IURLGenerator&MockObject $url;
	protected IAppManager&MockObject $appManager;
	protected IFactory&MockObject $i10nFactory;
	protected IManager&MockObject $activityManager;
	protected IEventMerger&MockObject $eventMerger;
	protected Event&MockObject $provider;

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
			->onlyMethods(['parse'])
			->getMock();
	}

	public static function dataGenerateObjectParameter(): array {
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
	 */
	public function testGenerateObjectParameter(int $id, string $name, ?array $link, bool $calendarAppEnabled = true): void {
		$affectedUser = 'otheruser';
		if ($link) {
			$affectedUser = $link['owner'];
			$generatedLink = [
				'objectId' => base64_encode('/remote.php/dav/calendars/' . $link['owner'] . '/' . $link['calendar_uri'] . '/' . $link['object_uri']),
			];
			$this->appManager->expects($this->once())
				->method('isEnabledForUser')
				->with('calendar')
				->willReturn($calendarAppEnabled);
			if ($calendarAppEnabled) {
				$this->url->expects($this->once())
					->method('getWebroot');
				$this->url->expects($this->once())
					->method('linkToRouteAbsolute')
					->with('calendar.view.indexdirect.edit', $generatedLink)
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
		$this->assertEquals($result, $this->invokePrivate($this->provider, 'generateObjectParameter', [$objectParameter, $affectedUser]));
	}

	public static function generateObjectParameterLinkEncodingDataProvider(): array {
		return [
			[ // Shared calendar
				[
					'object_uri' => 'someuuid.ics',
					'calendar_uri' => 'personal',
					'owner' => 'sharer'
				],
				base64_encode('/remote.php/dav/calendars/sharee/personal_shared_by_sharer/someuuid.ics'),
			],
			[ // Shared calendar with umlauts
				[
					'object_uri' => 'someuuid.ics',
					'calendar_uri' => 'umlaut_äüöß',
					'owner' => 'sharer'
				],
				base64_encode('/remote.php/dav/calendars/sharee/umlaut_%c3%a4%c3%bc%c3%b6%c3%9f_shared_by_sharer/someuuid.ics'),
			],
			[ // Shared calendar with umlauts and mixed casing
				[
					'object_uri' => 'someuuid.ics',
					'calendar_uri' => 'Umlaut_äüöß',
					'owner' => 'sharer'
				],
				base64_encode('/remote.php/dav/calendars/sharee/Umlaut_%c3%a4%c3%bc%c3%b6%c3%9f_shared_by_sharer/someuuid.ics'),
			],
			[ // Owned calendar with umlauts
				[
					'object_uri' => 'someuuid.ics',
					'calendar_uri' => 'umlaut_äüöß',
					'owner' => 'sharee'
				],
				base64_encode('/remote.php/dav/calendars/sharee/umlaut_%c3%a4%c3%bc%c3%b6%c3%9f/someuuid.ics'),
			],
			[ // Owned calendar with umlauts and mixed casing
				[
					'object_uri' => 'someuuid.ics',
					'calendar_uri' => 'Umlaut_äüöß',
					'owner' => 'sharee'
				],
				base64_encode('/remote.php/dav/calendars/sharee/Umlaut_%c3%a4%c3%bc%c3%b6%c3%9f/someuuid.ics'),
			],
		];
	}

	/**
	 * @dataProvider generateObjectParameterLinkEncodingDataProvider
	 */
	public function testGenerateObjectParameterLinkEncoding(array $link, string $objectId): void {
		$generatedLink = [
			'objectId' => $objectId,
		];
		$this->appManager->expects($this->once())
			->method('isEnabledForUser')
			->with('calendar')
			->willReturn(true);
		$this->url->expects($this->once())
			->method('getWebroot');
		$this->url->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('calendar.view.indexdirect.edit', $generatedLink)
			->willReturn('fullLink');
		$objectParameter = ['id' => 42, 'name' => 'calendar', 'link' => $link];
		$result = [
			'type' => 'calendar-event',
			'id' => 42,
			'name' => 'calendar',
			'link' => 'fullLink',
		];
		$this->assertEquals($result, $this->invokePrivate($this->provider, 'generateObjectParameter', [$objectParameter, 'sharee']));
	}

	public static function dataGenerateObjectParameterThrows(): array {
		return [
			['event', TypeError::class],
			[['name' => 'event']],
			[['id' => 42]],
		];
	}

	/**
	 * @dataProvider dataGenerateObjectParameterThrows
	 */
	public function testGenerateObjectParameterThrows(string|array $eventData, string $exception = InvalidArgumentException::class): void {
		$this->expectException($exception);

		$this->invokePrivate($this->provider, 'generateObjectParameter', [$eventData, 'no_user']);
	}
}
