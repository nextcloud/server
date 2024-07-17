<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use JsonSerializable;

class MigrationAttribute implements JsonSerializable {
	public function __construct(
		private string $table = '',
		private string $description = '',
		private array $notes = [],
	) {
	}

	public function setTable(string $table): self {
		$this->table = $table;
		return $this;
	}

	public function getTable(): string {
		return $this->table;
	}

	public function setDescription(string $description): self {
		$this->description = $description;
		return $this;
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function setNotes(array $notes): self {
		$this->notes = $notes;
		return $this;
	}

	public function getNotes(): array {
		return $this->notes;
	}

	public function definition(): string {
		return json_encode($this->jsonSerialize(), JSON_UNESCAPED_SLASHES);
	}

	public function import(array $data): self {
		return $this->setTable($data['table'] ?? '')
					->setDescription($data['description'] ?? '')
					->setNotes($data['notes'] ?? []);
	}

	public function jsonSerialize(): array {
		return [
			'class' => get_class($this),
			'table' => $this->getTable(),
			'description' => $this->getDescription(),
			'notes' => $this->getNotes(),
		];
	}
}
