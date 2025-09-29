<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\BackgroundJob;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class CleanupOrphanedChildrenJob extends QueuedJob {
	public const ARGUMENT_CHILD_TABLE = 'childTable';
	public const ARGUMENT_PARENT_TABLE = 'parentTable';
	public const ARGUMENT_PARENT_ID = 'parentId';
	public const ARGUMENT_LOG_MESSAGE = 'logMessage';

	private const BATCH_SIZE = 1000;

	public function __construct(
		ITimeFactory $time,
		private readonly IDBConnection $connection,
		private readonly LoggerInterface $logger,
		private readonly IJobList $jobList,
	) {
		parent::__construct($time);
	}

	protected function run($argument): void {
		$childTable = $argument[self::ARGUMENT_CHILD_TABLE];
		$parentTable = $argument[self::ARGUMENT_PARENT_TABLE];
		$parentId = $argument[self::ARGUMENT_PARENT_ID];
		$logMessage = $argument[self::ARGUMENT_LOG_MESSAGE];

		$orphanCount = $this->cleanUpOrphans($childTable, $parentTable, $parentId);
		$this->logger->debug(sprintf($logMessage, $orphanCount));

		// Requeue if there might be more orphans
		if ($orphanCount >= self::BATCH_SIZE) {
			$this->jobList->add(self::class, $argument);
		}
	}

	private function cleanUpOrphans(
		string $childTable,
		string $parentTable,
		string $parentId,
	): int {
		// We can't merge both queries into a single one here as DELETEing from a table while
		// SELECTing it in a sub query is not supported by Oracle DB.
		// Ref https://docs.oracle.com/cd/E17952_01/mysql-8.0-en/delete.html#idm46006185488144

		$selectQb = $this->connection->getQueryBuilder();

		$selectQb->select('c.id')
			->from($childTable, 'c')
			->leftJoin('c', $parentTable, 'p', $selectQb->expr()->eq('c.' . $parentId, 'p.id'))
			->where($selectQb->expr()->isNull('p.id'))
			->setMaxResults(self::BATCH_SIZE);

		if (\in_array($parentTable, ['calendars', 'calendarsubscriptions'], true)) {
			$calendarType = $parentTable === 'calendarsubscriptions' ? CalDavBackend::CALENDAR_TYPE_SUBSCRIPTION : CalDavBackend::CALENDAR_TYPE_CALENDAR;
			$selectQb->andWhere($selectQb->expr()->eq('c.calendartype', $selectQb->createNamedParameter($calendarType, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT));
		}

		$result = $selectQb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();
		if (empty($rows)) {
			return 0;
		}

		$orphanItems = array_map(static fn ($row) => $row['id'], $rows);
		$deleteQb = $this->connection->getQueryBuilder();
		$deleteQb->delete($childTable)
			->where($deleteQb->expr()->in('id', $deleteQb->createNamedParameter($orphanItems, IQueryBuilder::PARAM_INT_ARRAY)));
		$deleteQb->executeStatement();

		return count($orphanItems);
	}
}
