<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use JsonSerializable;

class TableMigrationAttribute extends MigrationAttribute implements JsonSerializable {
	public function __construct(
		string $table = '',
		private array $columns = [],
		string $description = '',
		array $notes = [],
	) {
		parent::__construct($table, $description, $notes);
	}

	public function setColumns(array $columns): self {
		$this->columns = $columns;
		return $this;
	}

	public function getColumns(): array {
		return $this->columns;
	}

	public function import(array $data): self {
		parent::import($data);
		$this->setColumns($data['columns'] ?? []);
		return $this;
	}

	public function jsonSerialize(): array {
		return array_merge(
			parent::jsonSerialize(),
			[
				'columns' => $this->getColumns(),
			]
		);
	}
}
