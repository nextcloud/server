<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Delete all share entries that have no matching entries in the file cache table.
 */
class DeleteOrphanedItems extends TimedJob {
	public const CHUNK_SIZE = 200;

	/**
	 * sets the correct interval for this timed job
	 */
	public function __construct(
		ITimeFactory $time,
		protected IDBConnection $connection,
		protected LoggerInterface $logger,
	) {
		parent::__construct($time);
		$this->setInterval(60 * 60);
	}

	/**
	 * Makes the background job do its work
	 *
	 * @param array $argument unused argument
	 */
	public function run($argument) {
		$this->cleanSystemTags();
		$this->cleanUserTags();
		$this->cleanComments();
		$this->cleanCommentMarkers();
	}

	/**
	 * Deleting orphaned system tag mappings
	 *
	 * @param string $table
	 * @param string $idCol
	 * @param string $typeCol
	 * @return int Number of deleted entries
	 */
	protected function cleanUp(string $table, string $idCol, string $typeCol): int {
		$deletedEntries = 0;

		$deleteQuery = $this->connection->getQueryBuilder();
		$deleteQuery->delete($table)
			->where($deleteQuery->expr()->eq($idCol, $deleteQuery->createParameter('objectid')));

		if ($this->connection->getShardDefinition('filecache')) {
			$sourceIdChunks = $this->getItemIds($table, $idCol, $typeCol, 1000);
			foreach ($sourceIdChunks as $sourceIdChunk) {
				$deletedSources = $this->findMissingSources($sourceIdChunk);
				$deleteQuery->setParameter('objectid', $deletedSources, IQueryBuilder::PARAM_INT_ARRAY);
				$deletedEntries += $deleteQuery->executeStatement();
			}
		} else {
			$query = $this->connection->getQueryBuilder();
			$query->select('t1.' . $idCol)
				->from($table, 't1')
				->where($query->expr()->eq($typeCol, $query->expr()->literal('files')))
				->leftJoin('t1', 'filecache', 't2', $query->expr()->eq($query->expr()->castColumn('t1.' . $idCol, IQueryBuilder::PARAM_INT), 't2.fileid'))
				->andWhere($query->expr()->isNull('t2.fileid'))
				->groupBy('t1.' . $idCol)
				->setMaxResults(self::CHUNK_SIZE);

			$deleteQuery = $this->connection->getQueryBuilder();
			$deleteQuery->delete($table)
				->where($deleteQuery->expr()->in($idCol, $deleteQuery->createParameter('objectid')));

			$deletedInLastChunk = self::CHUNK_SIZE;
			while ($deletedInLastChunk === self::CHUNK_SIZE) {
				$chunk = $query->executeQuery()->fetchAll(\PDO::FETCH_COLUMN);
				$deletedInLastChunk = count($chunk);

				$deleteQuery->setParameter('objectid', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
				$deletedEntries += $deleteQuery->executeStatement();
			}
		}

		return $deletedEntries;
	}

	/**
	 * @param string $table
	 * @param string $idCol
	 * @param string $typeCol
	 * @param int $chunkSize
	 * @return \Iterator<int[]>
	 * @throws \OCP\DB\Exception
	 */
	private function getItemIds(string $table, string $idCol, string $typeCol, int $chunkSize): \Iterator {
		$query = $this->connection->getQueryBuilder();
		$query->select($idCol)
			->from($table)
			->where($query->expr()->eq($typeCol, $query->expr()->literal('files')))
			->groupBy($idCol)
			->andWhere($query->expr()->gt($idCol, $query->createParameter('min_id')))
			->setMaxResults($chunkSize);

		$minId = 0;
		while (true) {
			$query->setParameter('min_id', $minId);
			$rows = $query->executeQuery()->fetchAll(\PDO::FETCH_COLUMN);
			if (count($rows) > 0) {
				$minId = $rows[count($rows) - 1];
				yield $rows;
			} else {
				break;
			}
		}
	}

	private function findMissingSources(array $ids): array {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('fileid')
			->from('filecache')
			->where($qb->expr()->in('fileid', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)));
		$found = $qb->executeQuery()->fetchAll(\PDO::FETCH_COLUMN);
		return array_diff($ids, $found);
	}

	/**
	 * Deleting orphaned system tag mappings
	 *
	 * @return int Number of deleted entries
	 */
	protected function cleanSystemTags() {
		$deletedEntries = $this->cleanUp('systemtag_object_mapping', 'objectid', 'objecttype');
		$this->logger->debug("$deletedEntries orphaned system tag relations deleted", ['app' => 'DeleteOrphanedItems']);
		return $deletedEntries;
	}

	/**
	 * Deleting orphaned user tag mappings
	 *
	 * @return int Number of deleted entries
	 */
	protected function cleanUserTags() {
		$deletedEntries = $this->cleanUp('vcategory_to_object', 'objid', 'type');
		$this->logger->debug("$deletedEntries orphaned user tag relations deleted", ['app' => 'DeleteOrphanedItems']);
		return $deletedEntries;
	}

	/**
	 * Deleting orphaned comments
	 *
	 * @return int Number of deleted entries
	 */
	protected function cleanComments() {
		$deletedEntries = $this->cleanUp('comments', 'object_id', 'object_type');
		$this->logger->debug("$deletedEntries orphaned comments deleted", ['app' => 'DeleteOrphanedItems']);
		return $deletedEntries;
	}

	/**
	 * Deleting orphaned comment read markers
	 *
	 * @return int Number of deleted entries
	 */
	protected function cleanCommentMarkers() {
		$deletedEntries = $this->cleanUp('comments_read_markers', 'object_id', 'object_type');
		$this->logger->debug("$deletedEntries orphaned comment read marks deleted", ['app' => 'DeleteOrphanedItems']);
		return $deletedEntries;
	}
}
