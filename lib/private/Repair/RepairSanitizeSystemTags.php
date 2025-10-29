<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace OC\Repair;

use OC\Migration\NullOutput;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Util;

class RepairSanitizeSystemTags implements IRepairStep {
	private bool $dryRun = false;
	private int $changeCount = 0;

	public function __construct(
		protected IDBConnection $connection,
	) {
	}

	public function getName(): string {
		return 'Sanitize and merge duplicate system tags';
	}

	public function migrationsAvailable(): bool {
		$this->dryRun = true;
		$this->sanitizeAndMergeTags(new NullOutput());
		$this->dryRun = false;
		return $this->changeCount > 0;
	}

	public function run(IOutput $output): void {
		$this->dryRun = false;
		$this->sanitizeAndMergeTags($output);
	}

	private function sanitizeAndMergeTags(IOutput $output): void {
		$output->info('Starting sanitization of system tags...');

		$tags = $this->getAllTags();

		// Group tags by sanitized name
		$sanitizedMap = [];
		$totalTags = 0;
		foreach ($tags as $tag) {
			$sanitizedMap[$tag['sanitizedName']][] = $tag;
			$totalTags++;
		}

		$sanitizeCount = count($sanitizedMap);
		$output->info("Found $totalTags tags with $sanitizeCount unique sanitized names.");

		// Get object counts for all tags in one query
		$objectCounts = [];
		foreach ($this->getAllTagObjectCounts() as $tagId => $count) {
			$objectCounts[$tagId] = $count;
		}

		// Process each sanitized name group
		foreach ($sanitizedMap as $sanitizedName => $group) {
			// Single tag, no duplicates found
			if (count($group) === 1) {
				$tag = $group[0];
				if ($tag['originalName'] !== $sanitizedName) {
					if (!$this->dryRun) {
						$qb = $this->connection->getQueryBuilder();
						$qb->update('systemtag')
							->set('name', $qb->createNamedParameter($sanitizedName))
							->where($qb->expr()->eq('id', $qb->createNamedParameter($tag['id'])))
							->executeStatement();
					}
					$this->changeCount++;
					$output->info("Sanitized tag ID {$tag['id']}: '{$tag['originalName']}' → '$sanitizedName'");
				}
				continue;
			}

			// Multiple tags with same sanitized name - merge them
			$this->mergeTagGroup($group, $sanitizedName, $objectCounts, $output);
		}

		$output->info('System tag sanitization and merge completed.');
	}

	private function mergeTagGroup(array $group, string $sanitizedName, array $objectCounts, IOutput $output): void {
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
			$count = $objectCounts[$tag['id']] ?? 0;
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

		if (!$this->dryRun) {
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
		}

		$this->changeCount += count($duplicateIds);
		if ($keepTag['originalName'] !== $sanitizedName) {
			$this->changeCount++;
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
	 * Check if a tag name already exists (excluding a specific tag ID)
	 */
	private function tagNameExists(string $name, int $excludeId): bool {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('id')
			->from('systemtag')
			->where($qb->expr()->eq('name', $qb->createNamedParameter($name)))
			->andWhere($qb->expr()->neq('id', $qb->createNamedParameter($excludeId)))
			->setMaxResults(1);

		$result = $qb->executeQuery();
		$exists = $result->fetch() !== false;
		$result->closeCursor();

		return $exists;
	}

	// Fetch all tags in batches to avoid memory issues
	private function getAllTags(int $offset = 0, ?int $limit = null): \Iterator {
		$maxBatchSize = 1000;

		do {
			if ($limit !== null) {
				$batchSize = min($limit, $maxBatchSize);
				$limit -= $batchSize;
			} else {
				$batchSize = $maxBatchSize;
			}

			$tags = $this->getTags($batchSize, $offset);
			$offset += $batchSize;

			foreach ($tags as $tag) {
				yield $tag;
			}

		} while (count($tags) === $batchSize && $limit !== 0);
	}

	// Fetch tags from the database
	private function getTags($limit = null, $offset = null): array {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('id', 'name', 'visibility', 'editable')
			->from('systemtag')
			->orderBy('name')
			->addOrderBy('id');

		$tags = [];

		// Apply limit and offset if provided
		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}
		if ($offset !== null) {
			$qb->setFirstResult($offset);
		}

		// Fetch and return tags
		$result = $qb->executeQuery();
		while ($row = $result->fetch()) {
			$tags[] = [
				'id' => (int)$row['id'],
				'originalName' => $row['name'],
				'sanitizedName' => Util::sanitizeWordsAndEmojis($row['name']),
				'visibility' => (int)$row['visibility'],
				'editable' => (int)$row['editable'],
			];
		}
		$result->closeCursor();
		return $tags;
	}

	private function getAllTagObjectCounts(int $offset = 0, ?int $limit = null): \Iterator {
		$maxBatchSize = 1000;

		do {
			if ($limit !== null) {
				$batchSize = min($limit, $maxBatchSize);
				$limit -= $batchSize;
			} else {
				$batchSize = $maxBatchSize;
			}

			$counts = $this->getTagObjectCounts($batchSize, $offset);
			$offset += $batchSize;

			foreach ($counts as $tagId => $count) {
				yield $tagId => $count;
			}

		} while (count($counts) === $batchSize && $limit !== 0);
	}

	// Get object counts for all tags in one efficient query
	private function getTagObjectCounts($limit = null, $offset = null): array {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('systemtagid')
			->selectAlias($qb->createFunction('COUNT(*)'), 'cnt')
			->from('systemtag_object_mapping')
			->groupBy('systemtagid');

		$counts = [];

		// Apply limit and offset if provided
		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}
		if ($offset !== null) {
			$qb->setFirstResult($offset);
		}

		// Fetch and return counts
		$result = $qb->executeQuery();
		while ($row = $result->fetch()) {
			$counts[(int)$row['systemtagid']] = (int)$row['cnt'];
		}
		$result->closeCursor();
		return $counts;
	}
}
