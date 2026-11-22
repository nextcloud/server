<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\DB\Events;

/**
 * Event to allow apps to register information about missing database primary keys
 *
 * This event will be dispatched for checking on the admin settings and when running
 * occ db:add-missing-primary-keys which will then create those keys
 *
 * @since 28.0.0
 */
class AddMissingPrimaryKeyEvent extends \OCP\EventDispatcher\Event {
	/** @var array<array-key, array{tableName: string, primaryKeyName: string, columns: string[], formerIndex: null|string}> */
	private array $missingPrimaryKeys = [];

	/**
	 * @param string[] $columns
	 * @since 28.0.0
	 */
	public function addMissingPrimaryKey(string $tableName, string $primaryKeyName, array $columns, ?string $formerIndex = null): void {
		$this->missingPrimaryKeys[] = [
			'tableName' => $tableName,
			'primaryKeyName' => $primaryKeyName,
			'columns' => $columns,
			'formerIndex' => $formerIndex,
		];
	}

	/**
	 * @since 28.0.0
	 * @return array<array-key, array{tableName: string, primaryKeyName: string, columns: string[], formerIndex: null|string}>
	 */
	public function getMissingPrimaryKeys(): array {
		return $this->missingPrimaryKeys;
	}
}
