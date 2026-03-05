<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\AppInfo\PluginManager;
use OCA\DAV\CalDAV\CachedSubscription;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarHome;
use OCA\DAV\CalDAV\Federation\FederatedCalendar;
use OCA\DAV\CalDAV\Federation\FederatedCalendarFactory;
use OCA\DAV\CalDAV\Integration\ExternalCalendar;
use OCA\DAV\CalDAV\Integration\ICalendarProvider;
use OCA\DAV\CalDAV\Outbox;
use OCA\DAV\CalDAV\Trashbin\TrashbinHome;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\CalDAV\Schedule\Inbox;
use Sabre\CalDAV\Subscriptions\Subscription;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV\MkCol;
use Test\TestCase;

class CalendarHomeTest extends TestCase {
	private CalDavBackend&MockObject $backend;
	private array $principalInfo = [];
	private PluginManager&MockObject $pluginManager;
	private LoggerInterface&MockObject $logger;
	private FederatedCalendarFactory&MockObject $federatedCalendarFactory;
	private CalendarHome $calendarHome;

	protected function setUp(): void {
		parent::setUp();

		$this->backend = $this->createMock(CalDavBackend::class);
		$this->principalInfo = [
			'uri' => 'user-principal-123',
		];
		$this->pluginManager = $this->createMock(PluginManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->federatedCalendarFactory = $this->createMock(FederatedCalendarFactory::class);

		$this->calendarHome = new CalendarHome(
			$this->backend,
			$this->principalInfo,
			$this->logger,
			$this->federatedCalendarFactory,
			false
		);

		// Replace PluginManager with our mock
		$reflection = new \ReflectionClass($this->calendarHome);
		$reflectionProperty = $reflection->getProperty('pluginManager');
		$reflectionProperty->setValue($this->calendarHome, $this->pluginManager);
	}

	public function testCreateCalendarValidName(): void {
		/** @var MkCol&MockObject $mkCol */
		$mkCol = $this->createMock(MkCol::class);

		$mkCol->method('getResourceType')
			->willReturn(['{DAV:}collection',
				'{urn:ietf:params:xml:ns:caldav}calendar']);
		$mkCol->method('getRemainingValues')
			->willReturn(['... properties ...']);

		$this->backend->expects(self::once())
			->method('createCalendar')
			->with('user-principal-123', 'name123', ['... properties ...']);

		$this->calendarHome->createExtendedCollection('name123', $mkCol);
	}

	public function testCreateCalendarReservedName(): void {
		$this->expectException(\Sabre\DAV\Exception\MethodNotAllowed::class);
		$this->expectExceptionMessage('The resource you tried to create has a reserved name');

		/** @var MkCol&MockObject $mkCol */
		$mkCol = $this->createMock(MkCol::class);

		$this->calendarHome->createExtendedCollection('contact_birthdays', $mkCol);
	}

	public function testCreateCalendarReservedNameAppGenerated(): void {
		$this->expectException(\Sabre\DAV\Exception\MethodNotAllowed::class);
		$this->expectExceptionMessage('The resource you tried to create has a reserved name');

		/** @var MkCol&MockObject $mkCol */
		$mkCol = $this->createMock(MkCol::class);

		$this->calendarHome->createExtendedCollection('app-generated--example--foo-1', $mkCol);
	}

	public function testGetChildren():void {
		$this->backend
			->expects(self::once())
			->method('getCalendarsForUser')
			->with('user-principal-123')
			->willReturn([]);

		$this->backend
			->expects(self::once())
			->method('getFederatedCalendarsForUser')
			->with('user-principal-123')
			->willReturn([]);

		$this->backend
			->expects(self::once())
			->method('getSubscriptionsForUser')
			->with('user-principal-123')
			->willReturn([]);

		$calendarPlugin1 = $this->createMock(ICalendarProvider::class);
		$calendarPlugin1
			->expects(self::once())
			->method('fetchAllForCalendarHome')
			->with('user-principal-123')
			->willReturn(['plugin1calendar1', 'plugin1calendar2']);

		$calendarPlugin2 = $this->createMock(ICalendarProvider::class);
		$calendarPlugin2
			->expects(self::once())
			->method('fetchAllForCalendarHome')
			->with('user-principal-123')
			->willReturn(['plugin2calendar1', 'plugin2calendar2']);

		$this->pluginManager
			->expects(self::once())
			->method('getCalendarPlugins')
			->with()
			->willReturn([$calendarPlugin1, $calendarPlugin2]);

		$actual = $this->calendarHome->getChildren();

		$this->assertCount(7, $actual);
		$this->assertInstanceOf(Inbox::class, $actual[0]);
		$this->assertInstanceOf(Outbox::class, $actual[1]);
		$this->assertInstanceOf(TrashbinHome::class, $actual[2]);
		$this->assertEquals('plugin1calendar1', $actual[3]);
		$this->assertEquals('plugin1calendar2', $actual[4]);
		$this->assertEquals('plugin2calendar1', $actual[5]);
		$this->assertEquals('plugin2calendar2', $actual[6]);
	}

	public function testGetChildNonAppGenerated():void {
		$this->backend
			->expects(self::once())
			->method('getCalendarByUri')
			->with('user-principal-123')
			->willReturn([]);

		$this->backend
			->expects(self::once())
			->method('getCalendarsForUser')
			->with('user-principal-123')
			->willReturn([]);

		$this->backend
			->expects(self::never())
			->method('getFederatedCalendarsForUser');

		$this->backend
			->expects(self::once())
			->method('getSubscriptionsForUser')
			->with('user-principal-123')
			->willReturn([]);

		$this->pluginManager
			->expects(self::never())
			->method('getCalendarPlugins');

		$this->expectException(\Sabre\DAV\Exception\NotFound::class);
		$this->expectExceptionMessage('Node with name \'personal\' could not be found');

		$this->calendarHome->getChild('personal');
	}

	public function testGetChildAppGenerated():void {
		$this->backend
			->expects(self::once())
			->method('getCalendarByUri')
			->with('user-principal-123')
			->willReturn([]);

		$this->backend
			->expects(self::once())
			->method('getCalendarsForUser')
			->with('user-principal-123')
			->willReturn([]);

		$this->backend
			->expects(self::never())
			->method('getFederatedCalendarsForUser');

		$this->backend
			->expects(self::once())
			->method('getSubscriptionsForUser')
			->with('user-principal-123')
			->willReturn([]);

		$calendarPlugin1 = $this->createMock(ICalendarProvider::class);
		$calendarPlugin1
			->expects(self::once())
			->method('getAppId')
			->with()
			->willReturn('calendar_plugin_1');
		$calendarPlugin1
			->expects(self::never())
			->method('hasCalendarInCalendarHome');
		$calendarPlugin1
			->expects(self::never())
			->method('getCalendarInCalendarHome');

		$externalCalendarMock = $this->createMock(ExternalCalendar::class);

		$calendarPlugin2 = $this->createMock(ICalendarProvider::class);
		$calendarPlugin2
			->expects(self::once())
			->method('getAppId')
			->with()
			->willReturn('calendar_plugin_2');
		$calendarPlugin2
			->expects(self::once())
			->method('hasCalendarInCalendarHome')
			->with('user-principal-123', 'calendar-uri-from-backend')
			->willReturn(true);
		$calendarPlugin2
			->expects(self::once())
			->method('getCalendarInCalendarHome')
			->with('user-principal-123', 'calendar-uri-from-backend')
			->willReturn($externalCalendarMock);

		$this->pluginManager
			->expects(self::once())
			->method('getCalendarPlugins')
			->with()
			->willReturn([$calendarPlugin1, $calendarPlugin2]);

		$actual = $this->calendarHome->getChild('app-generated--calendar_plugin_2--calendar-uri-from-backend');
		$this->assertEquals($externalCalendarMock, $actual);
	}

	public function testGetChildrenSubscriptions(): void {
		$this->backend
			->expects(self::once())
			->method('getCalendarsForUser')
			->with('user-principal-123')
			->willReturn([]);

		$this->backend
			->expects(self::once())
			->method('getFederatedCalendarsForUser')
			->with('user-principal-123')
			->willReturn([]);

		$this->backend
			->expects(self::once())
			->method('getSubscriptionsForUser')
			->with('user-principal-123')
			->willReturn([
				[
					'id' => 'subscription-1',
					'uri' => 'subscription-1',
					'principaluri' => 'user-principal-123',
					'source' => 'https://localhost/subscription-1',
					// A subscription array has actually more properties.
				],
				[
					'id' => 'subscription-2',
					'uri' => 'subscription-2',
					'principaluri' => 'user-principal-123',
					'source' => 'https://localhost/subscription-2',
					// A subscription array has actually more properties.
				]
			]);

		/*
		 * @FIXME: PluginManager should be injected via constructor.
		 */

		$pluginManager = $this->createMock(PluginManager::class);
		$pluginManager
			->expects(self::once())
			->method('getCalendarPlugins')
			->with()
			->willReturn([]);

		$calendarHome = new CalendarHome(
			$this->backend,
			$this->principalInfo,
			$this->logger,
			$this->federatedCalendarFactory,
			false
		);

		$reflection = new \ReflectionClass($calendarHome);
		$reflectionProperty = $reflection->getProperty('pluginManager');
		$reflectionProperty->setValue($calendarHome, $pluginManager);

		$actual = $calendarHome->getChildren();

		$this->assertCount(5, $actual);
		$this->assertInstanceOf(Inbox::class, $actual[0]);
		$this->assertInstanceOf(Outbox::class, $actual[1]);
		$this->assertInstanceOf(TrashbinHome::class, $actual[2]);
		$this->assertInstanceOf(Subscription::class, $actual[3]);
		$this->assertInstanceOf(Subscription::class, $actual[4]);
	}

	public function testGetChildrenCachedSubscriptions(): void {
		$this->backend
			->expects(self::once())
			->method('getCalendarsForUser')
			->with('user-principal-123')
			->willReturn([]);

		$this->backend
			->expects(self::once())
			->method('getFederatedCalendarsForUser')
			->with('user-principal-123')
			->willReturn([]);

		$this->backend
			->expects(self::once())
			->method('getSubscriptionsForUser')
			->with('user-principal-123')
			->willReturn([
				[
					'id' => 'subscription-1',
					'uri' => 'subscription-1',
					'principaluris' => 'user-principal-123',
					'source' => 'https://localhost/subscription-1',
					// A subscription array has actually more properties.
				],
				[
					'id' => 'subscription-2',
					'uri' => 'subscription-2',
					'principaluri' => 'user-principal-123',
					'source' => 'https://localhost/subscription-2',
					// A subscription array has actually more properties.
				]
			]);

		/*
		 * @FIXME: PluginManager should be injected via constructor.
		 */

		$pluginManager = $this->createMock(PluginManager::class);
		$pluginManager
			->expects(self::once())
			->method('getCalendarPlugins')
			->with()
			->willReturn([]);

		$calendarHome = new CalendarHome(
			$this->backend,
			$this->principalInfo,
			$this->logger,
			$this->federatedCalendarFactory,
			true
		);

		$reflection = new \ReflectionClass($calendarHome);
		$reflectionProperty = $reflection->getProperty('pluginManager');
		$reflectionProperty->setValue($calendarHome, $pluginManager);

		$actual = $calendarHome->getChildren();

		$this->assertCount(5, $actual);
		$this->assertInstanceOf(Inbox::class, $actual[0]);
		$this->assertInstanceOf(Outbox::class, $actual[1]);
		$this->assertInstanceOf(TrashbinHome::class, $actual[2]);
		$this->assertInstanceOf(CachedSubscription::class, $actual[3]);
		$this->assertInstanceOf(CachedSubscription::class, $actual[4]);
	}

	public function testGetChildrenFederatedCalendars(): void {
		$this->backend
			->expects(self::once())
			->method('getCalendarsForUser')
			->with('user-principal-123')
			->willReturn([]);

		$this->backend
			->expects(self::once())
			->method('getFederatedCalendarsForUser')
			->with('user-principal-123')
			->willReturn([
				[
					'id' => 10,
					'uri' => 'fed-cal-1',
					'principaluri' => 'user-principal-123',
					'{DAV:}displayname' => 'Federated calendar 1',
					'{http://sabredav.org/ns}sync-token' => 3,
					'{http://calendarserver.org/ns/}getctag' => 'http://sabre.io/ns/sync/3',
					'{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VEVENT']),
					'{http://owncloud.org/ns}owner-principal' => 'principals/remote-users/c2hhcmVyQGhvc3QudGxkCg==',
					'{http://owncloud.org/ns}read-only' => 1
				],
				[
					'id' => 11,
					'uri' => 'fed-cal-2',
					'principaluri' => 'user-principal-123',
					'{DAV:}displayname' => 'Federated calendar 2',
					'{http://sabredav.org/ns}sync-token' => 5,
					'{http://calendarserver.org/ns/}getctag' => 'http://sabre.io/ns/sync/5',
					'{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VEVENT']),
					'{http://owncloud.org/ns}owner-principal' => 'principals/remote-users/c2hhcmVyQGhvc3QudGxkCg==',
					'{http://owncloud.org/ns}read-only' => 1
				],
			]);

		$this->backend
			->expects(self::once())
			->method('getSubscriptionsForUser')
			->with('user-principal-123')
			->willReturn([]);

		$actual = $this->calendarHome->getChildren();

		$this->assertCount(5, $actual);
		$this->assertInstanceOf(Inbox::class, $actual[0]);
		$this->assertInstanceOf(Outbox::class, $actual[1]);
		$this->assertInstanceOf(TrashbinHome::class, $actual[2]);
		$this->assertInstanceOf(FederatedCalendar::class, $actual[3]);
		$this->assertInstanceOf(FederatedCalendar::class, $actual[4]);
	}
}
