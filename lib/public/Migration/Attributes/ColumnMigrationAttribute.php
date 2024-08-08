<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use JsonSerializable;

/**
 * generic class related to migration attribute about column changes
 *
 * @since 30.0.0
 */
class ColumnMigrationAttribute extends MigrationAttribute implements JsonSerializable {
	/**
	 * @param string $table name of the database table
	 * @param string $name name of the column
	 * @param string $description description of the migration
	 * @param array $notes notes about the migration/column
	 * @since 30.0.0
	 */
	public function __construct(
		string $table,
		private string $name = '',
		string $description = '',
		array $notes = [],
	) {
		parent::__construct($table, $description, $notes);
	}

	/**
	 * @param string $name
	 *
	 * @return $this
	 * @since 30.0.0
	 */
	public function setName(string $name): self {
		$this->name = $name;
		return $this;
	}

	/**
	 * @return string
	 * @since 30.0.0
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @param array $data
	 *
	 * @return $this
	 * @since 30.0.0
	 */
	public function import(array $data): self {
		parent::import($data);
		$this->setName($data['name'] ?? '');
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
				'name' => $this->getName(),
			]
		);
	}
}
