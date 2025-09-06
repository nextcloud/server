<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use JsonSerializable;
use OCP\AppFramework\Attribute\Consumable;

#[Consumable(since: '30.0.0')]
class MigrationAttribute implements JsonSerializable {
	/**
	 * @param string $table name of the database table
	 * @param string $description description of the migration
	 * @param array $notes notes about the migration
	 * @since 30.0.0
	 */
	public function __construct(
		private string $table,
		private string $description = '',
		private array $notes = [],
	) {
	}

	/**
	 * @param string $table
	 *
	 * @return $this
	 * @since 30.0.0
	 */
	public function setTable(string $table): self {
		$this->table = $table;
		return $this;
	}

	/**
	 * @return string
	 * @since 30.0.0
	 */
	public function getTable(): string {
		return $this->table;
	}

	/**
	 * @param string $description
	 *
	 * @return $this
	 * @since 30.0.0
	 */
	public function setDescription(string $description): self {
		$this->description = $description;
		return $this;
	}

	/**
	 * @return string
	 * @since 30.0.0
	 */
	public function getDescription(): string {
		return $this->description;
	}

	/**
	 * @param array $notes
	 *
	 * @return $this
	 * @since 30.0.0
	 */
	public function setNotes(array $notes): self {
		$this->notes = $notes;
		return $this;
	}

	/**
	 * @return array
	 * @since 30.0.0
	 */
	public function getNotes(): array {
		return $this->notes;
	}

	/**
	 * @return string
	 * @since 30.0.0
	 */
	public function definition(): string {
		return json_encode($this->jsonSerialize(), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * @param array $data
	 *
	 * @return self
	 * @since 30.0.0
	 */
	public function import(array $data): self {
		return $this->setDescription($data['description'] ?? '')
			->setNotes($data['notes'] ?? []);
	}

	/**
	 * @return array
	 * @since 30.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'class' => get_class($this),
			'table' => $this->getTable(),
			'description' => $this->getDescription(),
			'notes' => $this->getNotes()
		];
	}
}
