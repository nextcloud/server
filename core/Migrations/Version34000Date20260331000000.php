<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\DataCleansing;
use OCP\Migration\Attributes\DropIndex;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Adds a UNIQUE constraint on (group_id, class) to the authorized_groups table.
 *
 * Without this constraint, a narrow concurrent-write race in
 * AuthorizedGroupService::saveSettings() could insert duplicate rows, causing
 * findAllClassesForUser() to return the same authorized class name more than
 * once. The constraint makes the database the authoritative guard.
 *
 * preSchemaChange() removes any pre-existing duplicate rows (keeping the row
 * with the lowest id per pair) so that the index creation cannot fail on
 * instances that already have duplicates from before this migration.
 */
#[DataCleansing(table: 'authorized_groups', description: 'Remove duplicate (group_id, class) rows before adding unique index')]
#[DropIndex(table: 'authorized_groups', type: IndexType::INDEX, description: 'Drop non-unique admindel_groupid_idx, superseded by the new unique index')]
#[AddIndex(table: 'authorized_groups', type: IndexType::UNIQUE, description: 'Add unique index on (group_id, class) to prevent concurrent duplicate inserts')]
class Version34000Date20260331000000 extends SimpleMigrationStep {

	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	/**
	 * Remove duplicate (group_id, class) rows before the schema change.
	 * Keeps the row with the lowest id for each pair.
	 *
	 */
	#[Override]
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$qb = $this->connection->getQueryBuilder();

		// Fetch all rows ordered so that within each (group_id, class) pair the
		// lowest id comes first — the first occurrence is the one we keep.
		$result = $qb->select('id', 'group_id', 'class')
			->from('authorized_groups')
			->orderBy('group_id')
			->addOrderBy('class')
			->addOrderBy('id')
			->executeQuery();

		/** @var array<string, true> $seen */
		$seen = [];
		/** @var list<int> $idsToDelete */
		$idsToDelete = [];

		while ($row = $result->fetch()) {
			// Use NUL byte as separator — group_id and class are both max 200 chars
			// of arbitrary text, so we need a separator that cannot appear in either.
			$key = $row['group_id'] . "\0" . $row['class'];
			if (isset($seen[$key])) {
				$idsToDelete[] = (int)$row['id'];
			} else {
				$seen[$key] = true;
			}
		}
		$result->closeCursor();

		if ($idsToDelete === []) {
			return;
		}

		$output->info(sprintf(
			'authorized_groups: removing %d duplicate row(s) before adding unique index.',
			count($idsToDelete)
		));

		// Delete in chunks of 1000 to stay within query-parameter limits across
		// all supported database engines.
		foreach (array_chunk($idsToDelete, 1000) as $chunk) {
			$qb = $this->connection->getQueryBuilder();
			$qb->delete('authorized_groups')
				->where($qb->expr()->in('id', $qb->createNamedParameter($chunk, IQueryBuilder::PARAM_INT_ARRAY)))
				->executeStatement();
		}
	}

	/**
	 * Add the UNIQUE index on (group_id, class) and drop the superseded
	 * plain index on group_id.
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('authorized_groups')) {
			return null;
		}

		$table = $schema->getTable('authorized_groups');

		// Idempotency guard — safe to run twice (e.g. after a failed upgrade retry).
		if ($table->hasIndex('admindel_group_class_uniq')) {
			return null;
		}

		// The original index on group_id alone is superseded by the new unique
		// index on (group_id, class): any lookup by group_id will use the
		// leftmost column of the composite index.
		if ($table->hasIndex('admindel_groupid_idx')) {
			$table->dropIndex('admindel_groupid_idx');
		}

		$table->addUniqueIndex(['group_id', 'class'], 'admindel_group_class_uniq');

		return $schema;
	}
}
