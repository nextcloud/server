<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class BuildCalendarSearchIndexBackgroundJob extends QueuedJob {
	public function __construct(
		private IDBConnection $db,
		private CalDavBackend $calDavBackend,
		private LoggerInterface $logger,
		private IJobList $jobList,
		ITimeFactory $timeFactory,
	) {
		parent::__construct($timeFactory);
	}

	public function run($arguments) {
		$offset = (int)$arguments['offset'];
		$stopAt = (int)$arguments['stopAt'];

		$this->logger->info('Building calendar index (' . $offset . '/' . $stopAt . ')');

		$startTime = $this->time->getTime();
		while (($this->time->getTime() - $startTime) < 15) {
			$offset = $this->buildIndex($offset, $stopAt);
			if ($offset >= $stopAt) {
				break;
			}
		}

		if ($offset >= $stopAt) {
			$this->logger->info('Building calendar index done');
		} else {
			$this->jobList->add(self::class, [
				'offset' => $offset,
				'stopAt' => $stopAt
			]);
			$this->logger->info('New building calendar index job scheduled with offset ' . $offset);
		}
	}

	/**
	 * @param int $offset
	 * @param int $stopAt
	 * @return int
	 */
	private function buildIndex(int $offset, int $stopAt): int {
		$query = $this->db->getQueryBuilder();
		$query->select(['id', 'calendarid', 'uri', 'calendardata'])
			->from('calendarobjects')
			->where($query->expr()->lte('id', $query->createNamedParameter($stopAt)))
			->andWhere($query->expr()->gt('id', $query->createNamedParameter($offset)))
			->orderBy('id', 'ASC')
			->setMaxResults(500);

		$result = $query->executeQuery();
		while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
			$offset = $row['id'];

			$calendarData = $row['calendardata'];
			if (is_resource($calendarData)) {
				$calendarData = stream_get_contents($calendarData);
			}

			$this->calDavBackend->updateProperties($row['calendarid'], $row['uri'], $calendarData);
		}

		return $offset;
	}
}
