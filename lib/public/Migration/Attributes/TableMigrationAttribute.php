<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use OCP\AppFramework\Attribute\Consumable;

/**
 * generic class related to migration attribute about table changes
 */
#[Consumable(since: '30.0.0')]
class TableMigrationAttribute extends MigrationAttribute {
	/**
	 * @param string $table name of the database table
	 * @param array $columns list of columns
	 * @param string $description description of the migration
	 * @param array $notes notes about the migration/table
	 * @since 30.0.0
	 */
	public function __construct(
		string $table,
		private array $columns = [],
		string $description = '',
		array $notes = [],
	) {
		parent::__construct($table, $description, $notes);
	}

	/**
	 * @param array $columns
	 *
	 * @return $this
	 * @since 30.0.0
	 */
	public function setColumns(array $columns): self {
		$this->columns = $columns;
		return $this;
	}

	/**
	 * @return array
	 * @since 30.0.0
	 */
	public function getColumns(): array {
		return $this->columns;
	}

	/**
	 * @param array $data
	 *
	 * @return $this
	 * @since 30.0.0
	 */
	public function import(array $data): self {
		parent::import($data);
		$this->setColumns($data['columns'] ?? []);
		return $this;
	}

	/**
	 * @return array
	 * @since 30.0.0
	 */
	public function jsonSerialize(): array {
		return array_merge(
			parent::jsonSerialize(),
			[
				'columns' => $this->getColumns(),
			]
		);
	}
}
