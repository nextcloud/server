<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\BackgroundJob;

use OCA\DAV\CalDAV\Reminder\ReminderService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Class BuildReminderIndexBackgroundJob
 *
 * @package OCA\DAV\BackgroundJob
 */
class BuildReminderIndexBackgroundJob extends QueuedJob {

	/** @var ITimeFactory */
	private $timeFactory;

	/**
	 * BuildReminderIndexBackgroundJob constructor.
	 */
	public function __construct(
		private IDBConnection $db,
		private ReminderService $reminderService,
		private LoggerInterface $logger,
		private IJobList $jobList,
		ITimeFactory $timeFactory,
	) {
		parent::__construct($timeFactory);
		$this->timeFactory = $timeFactory;
	}

	public function run($argument) {
		$offset = (int)$argument['offset'];
		$stopAt = (int)$argument['stopAt'];

		$this->logger->info('Building calendar reminder index (' . $offset . '/' . $stopAt . ')');

		$offset = $this->buildIndex($offset, $stopAt);

		if ($offset >= $stopAt) {
			$this->logger->info('Building calendar reminder index done');
		} else {
			$this->jobList->add(self::class, [
				'offset' => $offset,
				'stopAt' => $stopAt
			]);
			$this->logger->info('Scheduled a new BuildReminderIndexBackgroundJob with offset ' . $offset);
		}
	}

	/**
	 * @param int $offset
	 * @param int $stopAt
	 * @return int
	 */
	private function buildIndex(int $offset, int $stopAt):int {
		$startTime = $this->timeFactory->getTime();

		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('calendarobjects')
			->where($query->expr()->lte('id', $query->createNamedParameter($stopAt)))
			->andWhere($query->expr()->gt('id', $query->createNamedParameter($offset)))
			->orderBy('id', 'ASC');

		$result = $query->executeQuery();
		while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
			$offset = (int)$row['id'];
			if (is_resource($row['calendardata'])) {
				$row['calendardata'] = stream_get_contents($row['calendardata']);
			}
			$row['component'] = $row['componenttype'];

			try {
				$this->reminderService->onCalendarObjectCreate($row);
			} catch (\Exception $ex) {
				$this->logger->error($ex->getMessage(), ['exception' => $ex]);
			}

			if (($this->timeFactory->getTime() - $startTime) > 15) {
				$result->closeCursor();
				return $offset;
			}
		}

		$result->closeCursor();
		return $stopAt;
	}
}
