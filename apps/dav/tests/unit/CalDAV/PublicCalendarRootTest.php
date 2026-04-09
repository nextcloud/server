<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\Federation\FederatedCalendarMapper;
use OCA\DAV\CalDAV\PublicCalendar;
use OCA\DAV\CalDAV\PublicCalendarRoot;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class PublicCalendarRootTest
 *
 *
 * @package OCA\DAV\Tests\unit\CalDAV
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class PublicCalendarRootTest extends TestCase {
	public const UNIT_TEST_USER = '';
	private CalDavBackend $backend;
	private PublicCalendarRoot $publicCalendarRoot;
	private IL10N&MockObject $l10n;
	private Principal&MockObject $principal;
	protected IUserManager&MockObject $userManager;
	protected IGroupManager&MockObject $groupManager;
	protected IConfig&MockObject $config;
	private ISecureRandom $random;
	private LoggerInterface&MockObject $logger;
	protected ICacheFactory&MockObject $cacheFactory;

	protected FederatedCalendarMapper&MockObject $federatedCalendarMapper;

	protected function setUp(): void {
		parent::setUp();

		$db = Server::get(IDBConnection::class);
		$this->principal = $this->createMock('OCA\DAV\Connector\Sabre\Principal');
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->random = Server::get(ISecureRandom::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->federatedCalendarMapper = $this->createMock(FederatedCalendarMapper::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$config = $this->createMock(IConfig::class);
		$sharingBackend = $this->createMock(\OCA\DAV\CalDAV\Sharing\Backend::class);

		$this->principal->expects($this->any())->method('getGroupMembership')
			->withAnyParameters()
			->willReturn([]);

		$this->principal->expects($this->any())->method('getCircleMembership')
			->withAnyParameters()
			->willReturn([]);

		$this->backend = new CalDavBackend(
			$db,
			$this->principal,
			$this->userManager,
			$this->random,
			$this->logger,
			$dispatcher,
			$config,
			$sharingBackend,
			$this->federatedCalendarMapper,
			$this->cacheFactory,
			false,
		);
		$this->l10n = $this->createMock(IL10N::class);
		$this->config = $this->createMock(IConfig::class);

		$this->publicCalendarRoot = new PublicCalendarRoot($this->backend,
			$this->l10n, $this->config, $this->logger);
	}

	protected function tearDown(): void {
		parent::tearDown();

		if (is_null($this->backend)) {
			return;
		}
		$this->principal->expects($this->any())->method('getGroupMembership')
			->withAnyParameters()
			->willReturn([]);

		$this->principal->expects($this->any())->method('getCircleMembership')
			->withAnyParameters()
			->willReturn([]);

		$books = $this->backend->getCalendarsForUser(self::UNIT_TEST_USER);
		foreach ($books as $book) {
			$this->backend->deleteCalendar($book['id'], true);
		}
	}

	public function testGetName(): void {
		$name = $this->publicCalendarRoot->getName();
		$this->assertEquals('public-calendars', $name);
	}

	public function testGetChild(): void {
		$calendar = $this->createPublicCalendar();

		$publicCalendars = $this->backend->getPublicCalendars();
		$this->assertEquals(1, count($publicCalendars));
		$this->assertEquals(true, $publicCalendars[0]['{http://owncloud.org/ns}public']);

		$publicCalendarURI = $publicCalendars[0]['uri'];

		$calendarResult = $this->publicCalendarRoot->getChild($publicCalendarURI);
		$this->assertEquals($calendar, $calendarResult);
	}

	public function testGetChildren(): void {
		$this->createPublicCalendar();
		$calendarResults = $this->publicCalendarRoot->getChildren();
		$this->assertSame([], $calendarResults);
	}

	protected function createPublicCalendar(): Calendar {
		$this->backend->createCalendar(self::UNIT_TEST_USER, 'Example', []);

		$calendarInfo = $this->backend->getCalendarsForUser(self::UNIT_TEST_USER)[0];
		$calendar = new PublicCalendar($this->backend, $calendarInfo, $this->l10n, $this->config, $this->logger);
		$publicUri = $calendar->setPublishStatus(true);

		$calendarInfo = $this->backend->getPublicCalendar($publicUri);
		$calendar = new PublicCalendar($this->backend, $calendarInfo, $this->l10n, $this->config, $this->logger);

		return $calendar;
	}
}
