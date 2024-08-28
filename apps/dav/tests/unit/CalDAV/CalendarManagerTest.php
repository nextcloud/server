<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV;

use OC\Calendar\Manager;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarImpl;
use OCA\DAV\CalDAV\CalendarManager;
use OCP\Calendar\IManager;
use OCP\IConfig;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class CalendarManagerTest extends \Test\TestCase {
	/** @var CalDavBackend | MockObject */
	private $backend;

	/** @var IL10N | MockObject */
	private $l10n;

	/** @var IConfig|MockObject */
	private $config;

	/** @var CalendarManager */
	private $manager;

	/** @var MockObject|LoggerInterface */
	private $logger;

	protected function setUp(): void {
		parent::setUp();
		$this->backend = $this->createMock(CalDavBackend::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->manager = new CalendarManager(
			$this->backend,
			$this->l10n,
			$this->config,
			$this->logger
		);
	}

	public function testSetupCalendarProvider(): void {
		$this->backend->expects($this->once())
			->method('getCalendarsForUser')
			->with('principals/users/user123')
			->willReturn([
				['id' => 123, 'uri' => 'blablub1'],
				['id' => 456, 'uri' => 'blablub2'],
			]);

		/** @var IManager | MockObject $calendarManager */
		$calendarManager = $this->createMock(Manager::class);
		$registeredIds = [];
		$calendarManager->expects($this->exactly(2))
			->method('registerCalendar')
			->willReturnCallback(function ($parameter) use (&$registeredIds): void {
				$this->assertInstanceOf(CalendarImpl::class, $parameter);
				$registeredIds[] = $parameter->getKey();
			});

		$this->manager->setupCalendarProvider($calendarManager, 'user123');

		$this->assertEquals(['123','456'], $registeredIds);
	}
}
