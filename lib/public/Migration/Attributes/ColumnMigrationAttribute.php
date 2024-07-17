<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use JsonSerializable;

class ColumnMigrationAttribute extends MigrationAttribute implements JsonSerializable {
	public function __construct(
		string $table = '',
		private string $name = '',
		private ?ColumnType $type = null,
		string $description = '',
		array $notes = [],
	) {
		parent::__construct($table, $description, $notes);
	}

	public function setName(string $name): self {
		$this->name = $name;
		return $this;
	}

	public function getName(): string {
		return $this->name;
	}

	public function setType(?ColumnType $type): self {
		$this->type = $type;
		return $this;
	}

	public function getType(): ?ColumnType {
		return $this->type;
	}

	public function import(array $data): self {
		parent::import($data);
		$this->setName($data['name'] ?? '');
		$this->setType(ColumnType::tryFrom($data['type'] ?? ''));
		return $this;
	}

	public function jsonSerialize(): array {
		return array_merge(
			parent::jsonSerialize(),
			[
				'name' => $this->getName(),
				'type' => $this->getType() ?? '',
			]
		);
	}
}
