<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\DB\Events;

/**
 * Event to allow apps to register information about missing database columns
 *
 * This event will be dispatched for checking on the admin settings and when running
 * occ db:add-missing-columns which will then create those columns
 *
 * @since 28.0.0
 */
class AddMissingColumnsEvent extends \OCP\EventDispatcher\Event {
	/** @var array<array-key, array{tableName: string, columnName: string, typeName: string, options: array{}}> */
	private array $missingColumns = [];

	/**
	 * @param mixed[] $options
	 * @since 28.0.0
	 */
	public function addMissingColumn(string $tableName, string $columnName, string $typeName, array $options): void {
		$this->missingColumns[] = [
			'tableName' => $tableName,
			'columnName' => $columnName,
			'typeName' => $typeName,
			'options' => $options,
		];
	}

	/**
	 * @since 28.0.0
	 * @return array<array-key, array{tableName: string, columnName: string, typeName: string, options: array{}}>
	 */
	public function getMissingColumns(): array {
		return $this->missingColumns;
	}
}
