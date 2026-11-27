<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\BackgroundJob;

use OCA\DAV\BackgroundJob\BuildReminderIndexBackgroundJob;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Reminder\ReminderService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

#[Group('DB')]
class BuildReminderIndexBackgroundJobTest extends TestCase {
	private IDBConnection $db;
	private ReminderService&MockObject $reminderService;
	private LoggerInterface&MockObject $logger;
	private IJobList&MockObject $jobList;
	private ITimeFactory&MockObject $timeFactory;
	private BuildReminderIndexBackgroundJob $job;
	private string $uri;
	/** @var list<int> */
	private array $insertedIds = [];

	protected function setUp(): void {
		parent::setUp();

		$this->db = Server::get(IDBConnection::class);
		$this->reminderService = $this->createMock(ReminderService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->method('getTime')->willReturn(1700000000);
		$this->uri = 'build-reminder-index-' . bin2hex(random_bytes(8)) . '.ics';

		$this->job = new BuildReminderIndexBackgroundJob(
			$this->db,
			$this->reminderService,
			$this->logger,
			$this->jobList,
			$this->timeFactory,
		);
	}

	protected function tearDown(): void {
		$query = $this->db->getQueryBuilder();
		$query->delete('calendarobjects')
			->where($query->expr()->in('id', $query->createNamedParameter($this->insertedIds, IQueryBuilder::PARAM_INT_ARRAY)))
			->executeStatement();

		parent::tearDown();
	}

	private function insertCalendarObject(int $calendarType): int {
		$query = $this->db->getQueryBuilder();
		$query->insert('calendarobjects')
			->values([
				'calendarid' => $query->createNamedParameter(42, IQueryBuilder::PARAM_INT),
				'calendartype' => $query->createNamedParameter($calendarType, IQueryBuilder::PARAM_INT),
				'uri' => $query->createNamedParameter($this->uri),
				'calendardata' => $query->createNamedParameter('BEGIN:VCALENDAR' . "\r\n" . 'END:VCALENDAR', IQueryBuilder::PARAM_LOB),
				'componenttype' => $query->createNamedParameter('VEVENT'),
				'size' => $query->createNamedParameter(28, IQueryBuilder::PARAM_INT),
			])
			->executeStatement();
		$id = $query->getLastInsertId();
		$this->insertedIds[] = $id;
		return $id;
	}

	public function testRunSkipsSubscriptionObjects(): void {
		$calendarObjectId = $this->insertCalendarObject(CalDavBackend::CALENDAR_TYPE_CALENDAR);
		$subscriptionObjectId = $this->insertCalendarObject(CalDavBackend::CALENDAR_TYPE_SUBSCRIPTION);
		$indexedIds = [];
		$this->reminderService->method('onCalendarObjectCreate')
			->willReturnCallback(function (array $row) use (&$indexedIds): void {
				if ($row['uri'] === $this->uri) {
					$indexedIds[] = (int)$row['id'];
				}
			});

		$this->job->run([
			'offset' => min($calendarObjectId, $subscriptionObjectId) - 1,
			'stopAt' => max($calendarObjectId, $subscriptionObjectId),
		]);

		$this->assertSame([$calendarObjectId], $indexedIds);
	}
}
