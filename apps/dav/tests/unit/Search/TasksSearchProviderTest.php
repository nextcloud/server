<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\Search;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Search\TasksSearchProvider;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Reader;
use Test\TestCase;

class TasksSearchProviderTest extends TestCase {
	private IAppManager&MockObject $appManager;
	private IL10N&MockObject $l10n;
	private IURLGenerator&MockObject $urlGenerator;
	private CalDavBackend&MockObject $backend;
	private TasksSearchProvider $provider;

	// NO DUE NOR COMPLETED NOR SUMMARY
	private static string $vTodo0 = 'BEGIN:VCALENDAR' . PHP_EOL .
		'PRODID:TEST' . PHP_EOL .
		'VERSION:2.0' . PHP_EOL .
		'BEGIN:VTODO' . PHP_EOL .
		'UID:20070313T123432Z-456553@example.com' . PHP_EOL .
		'DTSTAMP:20070313T123432Z' . PHP_EOL .
		'STATUS:NEEDS-ACTION' . PHP_EOL .
		'END:VTODO' . PHP_EOL .
		'END:VCALENDAR';

	// DUE AND COMPLETED
	private static string $vTodo1 = 'BEGIN:VCALENDAR' . PHP_EOL .
		'PRODID:TEST' . PHP_EOL .
		'VERSION:2.0' . PHP_EOL .
		'BEGIN:VTODO' . PHP_EOL .
		'UID:20070313T123432Z-456553@example.com' . PHP_EOL .
		'DTSTAMP:20070313T123432Z' . PHP_EOL .
		'COMPLETED:20070707T100000Z' . PHP_EOL .
		'DUE;VALUE=DATE:20070501' . PHP_EOL .
		'SUMMARY:Task title' . PHP_EOL .
		'STATUS:NEEDS-ACTION' . PHP_EOL .
		'END:VTODO' . PHP_EOL .
		'END:VCALENDAR';

	// COMPLETED ONLY
	private static string $vTodo2 = 'BEGIN:VCALENDAR' . PHP_EOL .
		'PRODID:TEST' . PHP_EOL .
		'VERSION:2.0' . PHP_EOL .
		'BEGIN:VTODO' . PHP_EOL .
		'UID:20070313T123432Z-456553@example.com' . PHP_EOL .
		'DTSTAMP:20070313T123432Z' . PHP_EOL .
		'COMPLETED:20070707T100000Z' . PHP_EOL .
		'SUMMARY:Task title' . PHP_EOL .
		'STATUS:NEEDS-ACTION' . PHP_EOL .
		'END:VTODO' . PHP_EOL .
		'END:VCALENDAR';

	// DUE DATE
	private static string $vTodo3 = 'BEGIN:VCALENDAR' . PHP_EOL .
		'PRODID:TEST' . PHP_EOL .
		'VERSION:2.0' . PHP_EOL .
		'BEGIN:VTODO' . PHP_EOL .
		'UID:20070313T123432Z-456553@example.com' . PHP_EOL .
		'DTSTAMP:20070313T123432Z' . PHP_EOL .
		'DUE;VALUE=DATE:20070501' . PHP_EOL .
		'SUMMARY:Task title' . PHP_EOL .
		'STATUS:NEEDS-ACTION' . PHP_EOL .
		'END:VTODO' . PHP_EOL .
		'END:VCALENDAR';

	// DUE DATETIME
	private static string $vTodo4 = 'BEGIN:VCALENDAR' . PHP_EOL .
		'PRODID:TEST' . PHP_EOL .
		'VERSION:2.0' . PHP_EOL .
		'BEGIN:VTODO' . PHP_EOL .
		'UID:20070313T123432Z-456553@example.com' . PHP_EOL .
		'DTSTAMP:20070313T123432Z' . PHP_EOL .
		'DUE:20070709T130000Z' . PHP_EOL .
		'SUMMARY:Task title' . PHP_EOL .
		'STATUS:NEEDS-ACTION' . PHP_EOL .
		'END:VTODO' . PHP_EOL .
		'END:VCALENDAR';

	protected function setUp(): void {
		parent::setUp();

		$this->appManager = $this->createMock(IAppManager::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->backend = $this->createMock(CalDavBackend::class);

		$this->provider = new TasksSearchProvider(
			$this->appManager,
			$this->l10n,
			$this->urlGenerator,
			$this->backend
		);
	}

	public function testGetId(): void {
		$this->assertEquals('tasks', $this->provider->getId());
	}

	public function testGetName(): void {
		$this->l10n->expects($this->exactly(1))
			->method('t')
			->with('Tasks')
			->willReturnArgument(0);

		$this->assertEquals('Tasks', $this->provider->getName());
	}

	public function testSearchAppDisabled(): void {
		$user = $this->createMock(IUser::class);
		$query = $this->createMock(ISearchQuery::class);
		$this->appManager->expects($this->once())
			->method('isEnabledForUser')
			->with('tasks', $user)
			->willReturn(false);
		$this->l10n->expects($this->exactly(1))
			->method('t')
			->willReturnArgument(0);
		$this->backend->expects($this->never())
			->method('getCalendarsForUser');
		$this->backend->expects($this->never())
			->method('getSubscriptionsForUser');
		$this->backend->expects($this->never())
			->method('searchPrincipalUri');

		$actual = $this->provider->search($user, $query);
		$data = $actual->jsonSerialize();
		$this->assertInstanceOf(SearchResult::class, $actual);
		$this->assertEquals('Tasks', $data['name']);
		$this->assertEmpty($data['entries']);
		$this->assertFalse($data['isPaginated']);
		$this->assertNull($data['cursor']);
	}

	public function testSearch(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('john.doe');
		$query = $this->createMock(ISearchQuery::class);
		$query->method('getTerm')->willReturn('search term');
		$query->method('getLimit')->willReturn(5);
		$query->method('getCursor')->willReturn(20);
		$this->appManager->expects($this->once())
			->method('isEnabledForUser')
			->with('tasks', $user)
			->willReturn(true);
		$this->l10n->method('t')->willReturnArgument(0);

		$this->backend->expects($this->once())
			->method('getCalendarsForUser')
			->with('principals/users/john.doe')
			->willReturn([
				[
					'id' => 99,
					'principaluri' => 'principals/users/john.doe',
					'uri' => 'calendar-uri-99',
				], [
					'id' => 123,
					'principaluri' => 'principals/users/john.doe',
					'uri' => 'calendar-uri-123',
				]
			]);
		$this->backend->expects($this->once())
			->method('getSubscriptionsForUser')
			->with('principals/users/john.doe')
			->willReturn([
				[
					'id' => 1337,
					'principaluri' => 'principals/users/john.doe',
					'uri' => 'subscription-uri-1337',
				]
			]);
		$this->backend->expects($this->once())
			->method('searchPrincipalUri')
			->with('principals/users/john.doe', '', ['VTODO'],
				['SUMMARY', 'DESCRIPTION', 'CATEGORIES'],
				[],
				['limit' => 5, 'offset' => 20, 'since' => null, 'until' => null])
			->willReturn([
				[
					'calendarid' => 99,
					'calendartype' => CalDavBackend::CALENDAR_TYPE_CALENDAR,
					'uri' => 'todo0.ics',
					'calendardata' => self::$vTodo0,
				],
				[
					'calendarid' => 123,
					'calendartype' => CalDavBackend::CALENDAR_TYPE_CALENDAR,
					'uri' => 'todo1.ics',
					'calendardata' => self::$vTodo1,
				],
				[
					'calendarid' => 1337,
					'calendartype' => CalDavBackend::CALENDAR_TYPE_SUBSCRIPTION,
					'uri' => 'todo2.ics',
					'calendardata' => self::$vTodo2,
				]
			]);

		$provider = $this->getMockBuilder(TasksSearchProvider::class)
			->setConstructorArgs([
				$this->appManager,
				$this->l10n,
				$this->urlGenerator,
				$this->backend,
			])
			->onlyMethods([
				'getDeepLinkToTasksApp',
				'generateSubline',
			])
			->getMock();

		$provider->expects($this->exactly(3))
			->method('generateSubline')
			->willReturn('subline');
		$provider->expects($this->exactly(3))
			->method('getDeepLinkToTasksApp')
			->willReturnMap([
				['calendar-uri-99', 'todo0.ics', 'deep-link-to-tasks'],
				['calendar-uri-123', 'todo1.ics', 'deep-link-to-tasks'],
				['subscription-uri-1337', 'todo2.ics', 'deep-link-to-tasks']
			]);

		$actual = $provider->search($user, $query);
		$data = $actual->jsonSerialize();
		$this->assertInstanceOf(SearchResult::class, $actual);
		$this->assertEquals('Tasks', $data['name']);
		$this->assertCount(3, $data['entries']);
		$this->assertTrue($data['isPaginated']);
		$this->assertEquals(23, $data['cursor']);

		$result0 = $data['entries'][0];
		$result0Data = $result0->jsonSerialize();
		$result1 = $data['entries'][1];
		$result1Data = $result1->jsonSerialize();
		$result2 = $data['entries'][2];
		$result2Data = $result2->jsonSerialize();

		$this->assertInstanceOf(SearchResultEntry::class, $result0);
		$this->assertEmpty($result0Data['thumbnailUrl']);
		$this->assertEquals('Untitled task', $result0Data['title']);
		$this->assertEquals('subline', $result0Data['subline']);
		$this->assertEquals('deep-link-to-tasks', $result0Data['resourceUrl']);
		$this->assertEquals('icon-checkmark', $result0Data['icon']);
		$this->assertFalse($result0Data['rounded']);

		$this->assertInstanceOf(SearchResultEntry::class, $result1);
		$this->assertEmpty($result1Data['thumbnailUrl']);
		$this->assertEquals('Task title', $result1Data['title']);
		$this->assertEquals('subline', $result1Data['subline']);
		$this->assertEquals('deep-link-to-tasks', $result1Data['resourceUrl']);
		$this->assertEquals('icon-checkmark', $result1Data['icon']);
		$this->assertFalse($result1Data['rounded']);

		$this->assertInstanceOf(SearchResultEntry::class, $result2);
		$this->assertEmpty($result2Data['thumbnailUrl']);
		$this->assertEquals('Task title', $result2Data['title']);
		$this->assertEquals('subline', $result2Data['subline']);
		$this->assertEquals('deep-link-to-tasks', $result2Data['resourceUrl']);
		$this->assertEquals('icon-checkmark', $result2Data['icon']);
		$this->assertFalse($result2Data['rounded']);
	}

	public function testGetDeepLinkToTasksApp(): void {
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('tasks.page.index')
			->willReturn('link-to-route-tasks.index');
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with('link-to-route-tasks.indexcalendars/uri-john.doe/tasks/task-uri.ics')
			->willReturn('absolute-url-link-to-route-tasks.indexcalendars/uri-john.doe/tasks/task-uri.ics');

		$actual = self::invokePrivate($this->provider, 'getDeepLinkToTasksApp', ['uri-john.doe', 'task-uri.ics']);
		$this->assertEquals('absolute-url-link-to-route-tasks.indexcalendars/uri-john.doe/tasks/task-uri.ics', $actual);
	}

	/**
	 * @dataProvider generateSublineDataProvider
	 */
	public function testGenerateSubline(string $ics, string $expectedSubline): void {
		$vCalendar = Reader::read($ics, Reader::OPTION_FORGIVING);
		$taskComponent = $vCalendar->VTODO;

		$this->l10n->method('t')->willReturnArgument(0);
		$this->l10n->method('l')->willReturnArgument(0);

		$actual = self::invokePrivate($this->provider, 'generateSubline', [$taskComponent]);
		$this->assertEquals($expectedSubline, $actual);
	}

	public static function generateSublineDataProvider(): array {
		return [
			[self::$vTodo0, ''],
			[self::$vTodo1, 'Completed on %s'],
			[self::$vTodo2, 'Completed on %s'],
			[self::$vTodo3, 'Due on %s'],
			[self::$vTodo4, 'Due on %s by %s'],
		];
	}
}
