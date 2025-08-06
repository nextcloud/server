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
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Make sure vcategory entries are unique per user and type
 * This migration will clean up existing duplicates
 * and add a unique constraint to prevent future duplicates.
 */
class Version30000Date20250731062008 extends SimpleMigrationStep {
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
	private function cleanupDuplicateCategories(IOutput $output) {
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
			$categoryId = (int) $category['id'];

			if (!isset($seen[$key])) {
				// First occurrence - keep this one
				$seen[$key] = $categoryId;
				continue;
			}

			// Duplicate found
			$keepId = $seen[$key];
			$duplicateCount++;

			$output->info("Found duplicate: keeping ID $keepId, removing ID $categoryId");

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
}
