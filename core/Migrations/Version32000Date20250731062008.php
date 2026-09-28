<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\Attributes\DataCleansing;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Make sure vcategory entries are unique per user and type
 * This migration will clean up existing duplicates.
 * The new unique constraint is added in @see \OC\Core\Listener\AddMissingIndicesListener
 */
#[DataCleansing(table: 'vcategory', description: 'Cleanup of duplicate vcategory records')]
#[DataCleansing(table: 'vcategory_to_object', description: 'Update object references')]
class Version32000Date20250731062008 extends SimpleMigrationStep {
	public function __construct(
		private IDBConnection $connection,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	#[Override]
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		// Clean up duplicate categories before adding unique constraint
		$this->cleanupDuplicateCategories($output);
	}

	/**
	 * Clean up duplicate categories
	 */
	private function cleanupDuplicateCategories(IOutput $output): void {
		$output->info('Starting cleanup of duplicate vcategory records...');

		// Find all categories, ordered to identify duplicates
		$qb = $this->connection->getQueryBuilder();
		$qb->select('id', 'uid', 'type', 'category')
			->from('vcategory')
			->orderBy('uid')
			->addOrderBy('type')
			->addOrderBy('category')
			->addOrderBy('id');

		$result = $qb->executeQuery();

		$seen = [];
		$duplicateCount = 0;

		while ($category = $result->fetch()) {
			$key = $category['uid'] . '|' . $category['type'] . '|' . $category['category'];
			$categoryId = (int)$category['id'];

			if (!isset($seen[$key])) {
				// First occurrence - keep this one
				$seen[$key] = $categoryId;
				continue;
			}

			// Duplicate found
			$keepId = $seen[$key];
			$duplicateCount++;

			$output->info("Found duplicate: keeping ID $keepId, removing ID $categoryId");

			$this->cleanupDuplicateAssignments($output, $categoryId, $keepId);

			// Update object references
			$updateQb = $this->connection->getQueryBuilder();
			$updateQb->update('vcategory_to_object')
				->set('categoryid', $updateQb->createNamedParameter($keepId))
				->where($updateQb->expr()->eq('categoryid', $updateQb->createNamedParameter($categoryId)));

			$affectedRows = $updateQb->executeStatement();
			if ($affectedRows > 0) {
				$output->info(" - Updated $affectedRows object references from category $categoryId to $keepId");
			}

			// Remove duplicate category record
			$deleteQb = $this->connection->getQueryBuilder();
			$deleteQb->delete('vcategory')
				->where($deleteQb->expr()->eq('id', $deleteQb->createNamedParameter($categoryId)));

			$deleteQb->executeStatement();
			$output->info(" - Deleted duplicate category record ID $categoryId");

		}

		$result->closeCursor();

		if ($duplicateCount === 0) {
			$output->info('No duplicate categories found');
		} else {
			$output->info("Duplicate cleanup completed - processed $duplicateCount duplicates");
		}
	}

	/**
	 * Clean up duplicate assignments
	 * That will delete rows with $categoryId when there is the same row with $keepId
	 */
	private function cleanupDuplicateAssignments(IOutput $output, int $categoryId, int $keepId): void {
		$selectQb = $this->connection->getQueryBuilder();
		$selectQb->select('o1.*')
			->from('vcategory_to_object', 'o1')
			->join(
				'o1', 'vcategory_to_object', 'o2',
				$selectQb->expr()->andX(
					$selectQb->expr()->eq('o1.type', 'o2.type'),
					$selectQb->expr()->eq('o1.objid', 'o2.objid'),
				)
			)
			->where($selectQb->expr()->eq('o1.categoryid', $selectQb->createNamedParameter($categoryId)))
			->andWhere($selectQb->expr()->eq('o2.categoryid', $selectQb->createNamedParameter($keepId)));

		$deleteQb = $this->connection->getQueryBuilder();
		$deleteQb->delete('vcategory_to_object')
			->where($deleteQb->expr()->eq('objid', $deleteQb->createParameter('objid')))
			->andWhere($deleteQb->expr()->eq('categoryid', $deleteQb->createParameter('categoryid')))
			->andWhere($deleteQb->expr()->eq('type', $deleteQb->createParameter('type')));

		$duplicatedAssignments = $selectQb->executeQuery();
		$count = 0;
		while ($row = $duplicatedAssignments->fetch()) {
			$deleteQb
				->setParameters($row)
				->executeStatement();
			$count++;
		}
		if ($count > 0) {
			$output->info(" - Deleted $count duplicate category assignments for $categoryId and $keepId");
		}
	}
}
