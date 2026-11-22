<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace OC\Repair;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Util;

class RepairSanitizeSystemTags implements IRepairStep {

	public function __construct(
		protected IDBConnection $connection,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Sanitize and merge duplicate system tags';
	}

	/**
	 * Cheap probe used by the setup check to tell whether this repair step has
	 * anything to do, without running the full (expensive) merge logic.
	 *
	 * The `systemtag` table has a unique index on (name, visibility, editable),
	 * so two tags can only collide on their sanitized name if at least one of
	 * them still has a non-sanitized name. Detecting a single tag whose name is
	 * not already sanitized is therefore enough to know work is pending, and we
	 * can early-exit on the first match instead of loading every tag in memory.
	 */
	public function migrationsAvailable(): bool {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('name')
			->from('systemtag');

		$result = $qb->executeQuery();
		$available = false;
		while (($name = $result->fetchOne()) !== false) {
			if ($name !== Util::sanitizeWordsAndEmojis($name)) {
				$available = true;
				break;
			}
		}
		$result->closeCursor();

		return $available;
	}

	#[\Override]
	public function run(IOutput $output): void {
		$output->info('Starting sanitization of system tags...');

		// This is a manually triggered expensive repair step, so we load all
		// tags in memory: we need the full set to group duplicates by their
		// sanitized name anyway. Each tag already carries its object count.
		$tags = $this->getAllTags();

		// Group tags by sanitized name
		$sanitizedMap = [];
		foreach ($tags as $tag) {
			$sanitizedMap[$tag['sanitizedName']][] = $tag;
		}

		$output->info(count($tags) . ' tags found with ' . count($sanitizedMap) . ' unique sanitized names.');

		// Process each sanitized name group
		foreach ($sanitizedMap as $sanitizedName => $group) {
			// Single tag, no duplicates found
			if (count($group) === 1) {
				$tag = $group[0];
				if ($tag['originalName'] !== $sanitizedName) {
					$qb = $this->connection->getQueryBuilder();
					$qb->update('systemtag')
						->set('name', $qb->createNamedParameter($sanitizedName))
						->where($qb->expr()->eq('id', $qb->createNamedParameter($tag['id'])))
						->executeStatement();
					$output->info("Sanitized tag ID {$tag['id']}: '{$tag['originalName']}' → '$sanitizedName'");
				}
				continue;
			}

			// Multiple tags with same sanitized name - merge them
			$this->mergeTagGroup($group, $sanitizedName, $output);
		}

		$output->info('System tag sanitization and merge completed.');
	}

	private function mergeTagGroup(array $group, string $sanitizedName, IOutput $output): void {
		// Validate that all tags in the group have the same visibility and editable settings
		$firstTag = $group[0];
		$visibility = $firstTag['visibility'];
		$editable = $firstTag['editable'];

		foreach ($group as $tag) {
			if ($tag['visibility'] !== $visibility || $tag['editable'] !== $editable) {
				$output->warning(
					"Cannot merge tag group '$sanitizedName': tags have different visibility or editable settings. "
					. 'Manual verification required. Tag IDs: ' . implode(', ', array_column($group, 'id'))
				);
				return;
			}
		}

		// Determine which tag to keep (most object mappings, then lowest ID as tiebreaker)
		$keepTag = null;
		$maxCount = -1;

		foreach ($group as $tag) {
			$count = $tag['objectCount'];
			if ($count > $maxCount || ($count === $maxCount && ($keepTag === null || $tag['id'] < $keepTag['id']))) {
				$maxCount = $count;
				$keepTag = $tag;
			}
		}

		$keepId = $keepTag['id'];
		if ($keepTag === null) {
			$output->warning("Cannot merge tag group '$sanitizedName': unable to determine which tag to keep");
			return;
		}

		$duplicateIds = array_filter(array_column($group, 'id'), fn ($id) => $id !== $keepId);
		if (empty($duplicateIds)) {
			return;
		}

		$this->connection->beginTransaction();
		try {
			// Step 1: Delete ALL mappings from duplicate tags that conflict with keepId
			// This must happen FIRST before any updates to avoid unique constraint violations
			$this->deleteConflictingMappings($duplicateIds, $keepId);

			// Step 2: Update all remaining mappings from duplicates to keepId
			// These won't conflict because we just deleted the conflicts
			$qb = $this->connection->getQueryBuilder();
			$qb->update('systemtag_object_mapping')
				->set('systemtagid', $qb->createNamedParameter($keepId))
				->where($qb->expr()->in('systemtagid', $qb->createNamedParameter($duplicateIds, IQueryBuilder::PARAM_INT_ARRAY)))
				->executeStatement();

			// Step 3: Delete duplicate tags in bulk (safe now that mappings are gone)
			$qb = $this->connection->getQueryBuilder();
			$qb->delete('systemtag')
				->where($qb->expr()->in('id', $qb->createNamedParameter($duplicateIds, IQueryBuilder::PARAM_INT_ARRAY)))
				->executeStatement();

			// Step 4: Sanitize the kept tag name if needed
			// This is safe because we've already deleted all duplicates with the same sanitized name
			if ($keepTag['originalName'] !== $sanitizedName) {
				$qb = $this->connection->getQueryBuilder();
				$qb->update('systemtag')
					->set('name', $qb->createNamedParameter($sanitizedName))
					->where($qb->expr()->eq('id', $qb->createNamedParameter($keepId)))
					->executeStatement();
			}

			$this->connection->commit();
		} catch (\Exception $e) {
			$this->connection->rollBack();
			$output->warning("Failed to merge tag group '$sanitizedName': " . $e->getMessage());
			return;
		}

		$duplicateIdsList = implode(', ', $duplicateIds);
		$output->info("Merged tags [$duplicateIdsList] into ID $keepId (sanitized: '$sanitizedName')");
	}

	/**
	 * Delete mappings from duplicate tags where the same object is already mapped to keepId
	 * This prevents unique constraint violations when updating systemtagid
	 */
	private function deleteConflictingMappings(array $duplicateIds, int $keepId): void {
		$batchSize = 1000;
		$batch = [];

		// Stream keepId mappings and process in batches
		$qb = $this->connection->getQueryBuilder();
		$qb->select('objectid', 'objecttype')
			->from('systemtag_object_mapping')
			->where($qb->expr()->eq('systemtagid', $qb->createNamedParameter($keepId)));

		$result = $qb->executeQuery();

		while ($mapping = $result->fetch()) {
			$batch[] = $mapping;

			// When batch is full, delete conflicts for this batch
			if (count($batch) >= $batchSize) {
				$this->deleteBatchConflicts($batch, $duplicateIds);
				$batch = []; // Clear batch
			}
		}

		$result->closeCursor();

		// Process remaining mappings in the last batch
		if (!empty($batch)) {
			$this->deleteBatchConflicts($batch, $duplicateIds);
		}
	}

	/**
	 * Delete mappings in a batch that conflict with keepId mappings
	 */
	private function deleteBatchConflicts(array $batch, array $duplicateIds): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('systemtag_object_mapping')
			->where($qb->expr()->in('systemtagid', $qb->createNamedParameter($duplicateIds, IQueryBuilder::PARAM_INT_ARRAY)));

		$orX = $qb->expr()->orX();
		foreach ($batch as $mapping) {
			$orX->add($qb->expr()->andX(
				$qb->expr()->eq('objectid', $qb->createNamedParameter($mapping['objectid'])),
				$qb->expr()->eq('objecttype', $qb->createNamedParameter($mapping['objecttype']))
			));
		}
		$qb->andWhere($orX);
		$qb->executeStatement();
	}

	/**
	 * Fetch all tags together with their object mapping count in a single query.
	 *
	 * @return list<array{id: int, originalName: string, sanitizedName: string, visibility: int, editable: int, objectCount: int}>
	 */
	private function getAllTags(): array {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('t.id', 't.name', 't.visibility', 't.editable')
			->selectAlias($qb->func()->count('m.systemtagid'), 'object_count')
			->from('systemtag', 't')
			->leftJoin('t', 'systemtag_object_mapping', 'm', $qb->expr()->eq('t.id', 'm.systemtagid'))
			->groupBy('t.id', 't.name', 't.visibility', 't.editable')
			->orderBy('t.name')
			->addOrderBy('t.id');

		$tags = [];
		$result = $qb->executeQuery();
		while ($row = $result->fetch()) {
			$tags[] = [
				'id' => (int)$row['id'],
				'originalName' => $row['name'],
				'sanitizedName' => Util::sanitizeWordsAndEmojis($row['name']),
				'visibility' => (int)$row['visibility'],
				'editable' => (int)$row['editable'],
				'objectCount' => (int)$row['object_count'],
			];
		}
		$result->closeCursor();
		return $tags;
	}
}
