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
	public const CHUNK_SIZE = 1000;

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
	#[\Override]
	public function run($argument) {
		$this->cleanSystemTags();
		$this->cleanUserTags();
		$this->cleanComments();
		$this->cleanCommentMarkers();
	}

	/**
	 * Delete mapping rows of the 'files' object type whose referenced file no
	 * longer exists in the file cache.
	 *
	 * The candidate ids are read from the mapping table itself in keyset-paginated
	 * chunks and each chunk is checked against the filecache primary key. This
	 * avoids joining the (potentially huge) mapping table against filecache with a
	 * GROUP BY, which reads the whole mapping table - and on some databases
	 * materialises a temp table - on every run even when there are no orphans (the
	 * common case). It also works the same way whether or not filecache is sharded,
	 * so a single code path covers both.
	 *
	 * @param string $table The mapping table to clean up
	 * @param string $idCol The column referencing the file id
	 * @param string $typeCol The column holding the object type
	 * @param bool $numericId Whether $idCol is an integer column. String columns
	 *                        hold the numeric file id as text; the keyset cursor is
	 *                        compared (and the rows ordered) using the column's own
	 *                        type so the index on $idCol stays usable on every
	 *                        database instead of being defeated by an implicit cast.
	 * @return int Number of deleted entries
	 */
	protected function cleanUp(string $table, string $idCol, string $typeCol, bool $numericId): int {
		$deletedEntries = 0;

		$deleteQuery = $this->connection->getQueryBuilder();
		$deleteQuery->delete($table)
			->where($deleteQuery->expr()->in($idCol, $deleteQuery->createParameter('objectid')));

		foreach ($this->getItemIds($table, $idCol, $typeCol, $numericId, self::CHUNK_SIZE) as $idChunk) {
			$missingSources = $this->findMissingSources($idChunk);
			if (count($missingSources) === 0) {
				continue;
			}

			// Bind the ids using the column's own type (mirroring the keyset cursor in
			// getItemIds()) so a string column is not implicitly coerced to a number.
			$deleteQuery->setParameter('objectid', $missingSources, $numericId ? IQueryBuilder::PARAM_INT_ARRAY : IQueryBuilder::PARAM_STR_ARRAY);
			$deletedEntries += $deleteQuery->executeStatement();
		}

		return $deletedEntries;
	}

	/**
	 * Yield the distinct 'files' ids of $table in keyset-paginated chunks.
	 *
	 * Chunks are ordered by $idCol and advanced with a `$idCol > cursor`
	 * comparison so the scan stays on the index covering ($typeCol, $idCol). The
	 * cursor is bound - and the rows therefore ordered and compared - using the
	 * column's own type: an integer column numerically, a string column lexically.
	 * Mixing the two (e.g. comparing a varchar column to an integer) would force
	 * an implicit cast that defeats the index and makes the ordering and the
	 * comparison disagree, which could skip chunks.
	 *
	 * @param string $table
	 * @param string $idCol
	 * @param string $typeCol
	 * @param bool $numericId Whether $idCol is an integer column
	 * @param int $chunkSize
	 * @return \Iterator<int[]>
	 * @throws \OCP\DB\Exception
	 */
	private function getItemIds(string $table, string $idCol, string $typeCol, bool $numericId, int $chunkSize): \Iterator {
		$cursorType = $numericId ? IQueryBuilder::PARAM_INT : IQueryBuilder::PARAM_STR;

		$query = $this->connection->getQueryBuilder();
		$query->select($idCol)
			->from($table)
			->where($query->expr()->eq($typeCol, $query->expr()->literal('files')))
			->andWhere($query->expr()->gt($idCol, $query->createParameter('min_id')))
			->groupBy($idCol)
			->orderBy($idCol)
			->setMaxResults($chunkSize);

		$minId = $numericId ? 0 : '0';
		while (true) {
			$query->setParameter('min_id', $minId, $cursorType);
			$rows = $query->executeQuery()->fetchFirstColumn();
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
		$found = $qb->executeQuery()->fetchFirstColumn();
		return array_diff($ids, $found);
	}

	/**
	 * Deleting orphaned system tag mappings
	 *
	 * @return int Number of deleted entries
	 */
	protected function cleanSystemTags() {
		$deletedEntries = $this->cleanUp('systemtag_object_mapping', 'objectid', 'objecttype', false);
		$this->logger->debug("$deletedEntries orphaned system tag relations deleted", ['app' => 'DeleteOrphanedItems']);
		return $deletedEntries;
	}

	/**
	 * Deleting orphaned user tag mappings
	 *
	 * @return int Number of deleted entries
	 */
	protected function cleanUserTags() {
		$deletedEntries = $this->cleanUp('vcategory_to_object', 'objid', 'type', true);
		$this->logger->debug("$deletedEntries orphaned user tag relations deleted", ['app' => 'DeleteOrphanedItems']);
		return $deletedEntries;
	}

	/**
	 * Deleting orphaned comments
	 *
	 * @return int Number of deleted entries
	 */
	protected function cleanComments() {
		$deletedEntries = $this->cleanUp('comments', 'object_id', 'object_type', false);
		$this->logger->debug("$deletedEntries orphaned comments deleted", ['app' => 'DeleteOrphanedItems']);
		return $deletedEntries;
	}

	/**
	 * Deleting orphaned comment read markers
	 *
	 * @return int Number of deleted entries
	 */
	protected function cleanCommentMarkers() {
		$deletedEntries = $this->cleanUp('comments_read_markers', 'object_id', 'object_type', false);
		$this->logger->debug("$deletedEntries orphaned comment read marks deleted", ['app' => 'DeleteOrphanedItems']);
		return $deletedEntries;
	}
}
