<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\DB\Events;

/**
 * Event to allow apps to register information about missing database indices
 *
 * This event will be dispatched for checking on the admin settings and when running
 * occ db:add-missing-indices which will then create those indices
 *
 * @since 28.0.0
 */
class AddMissingIndicesEvent extends \OCP\EventDispatcher\Event {
	/** @var array<array-key, array{tableName: string, indexName: string, columns: string[], options: array{}, dropUnnamedIndex: bool, uniqueIndex: bool}> */
	private array $missingIndices = [];

	/** @var array<array-key, array{tableName: string, oldIndexNames: array, newIndexName: string, columns: string[], uniqueIndex: bool, options: array{}}> */
	private array $toReplaceIndices = [];

	/**
	 * @param string[] $columns
	 * @since 28.0.0
	 */
	public function addMissingIndex(string $tableName, string $indexName, array $columns, array $options = [], bool $dropUnnamedIndex = false): void {
		$this->missingIndices[] = [
			'tableName' => $tableName,
			'indexName' => $indexName,
			'columns' => $columns,
			'options' => $options,
			'dropUnnamedIndex' => $dropUnnamedIndex,
			'uniqueIndex' => false,
		];
	}
	/**
	 * @param string[] $columns
	 * @since 28.0.0
	 */
	public function addMissingUniqueIndex(string $tableName, string $indexName, array $columns, array $options = [], bool $dropUnnamedIndex = false): void {
		$this->missingIndices[] = [
			'tableName' => $tableName,
			'indexName' => $indexName,
			'columns' => $columns,
			'options' => $options,
			'dropUnnamedIndex' => $dropUnnamedIndex,
			'uniqueIndex' => true,
		];
	}

	/**
	 * @since 28.0.0
	 * @return array<array-key, array{tableName: string, indexName: string, columns: string[], options: array{}, dropUnnamedIndex: bool, uniqueIndex: bool}>
	 */
	public function getMissingIndices(): array {
		return $this->missingIndices;
	}

	/**
	 * Replace one or more existing indices with a new one. Can be used to make an index unique afterwards or merge two indices into a multicolumn index.
	 *
	 * Note: Make sure to not use the same index name for the new index as for old indices.
	 *
	 * Example:
	 *
	 * <code>
	 *     $event->replaceIndex(
	 *         'my_table',
	 *         ['old_index_col_a', 'old_index_col_b'],
	 *         'new_index_col_a_b',
	 *         ['column_a', 'column_b'],
	 *         false
	 *     );
	 * </code>
	 *
	 * @since 29.0.0
	 */
	public function replaceIndex(string $tableName, array $oldIndexNames, string $newIndexName, array $columns, bool $unique, array $options = []): void {
		$this->toReplaceIndices[] = [
			'tableName' => $tableName,
			'oldIndexNames' => $oldIndexNames,
			'newIndexName' => $newIndexName,
			'columns' => $columns,
			'uniqueIndex' => $unique,
			'options' => $options,
		];
	}

	/**
	 * @since 29.0.0
	 * @return array<array-key, array{tableName: string, oldIndexNames: array, newIndexName: string, columns: string[], uniqueIndex: bool, options: array{}}>
	 */
	public function getIndicesToReplace(): array {
		return $this->toReplaceIndices;
	}
}
