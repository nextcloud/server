<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use OCP\AppFramework\Attribute\Consumable;

/**
 * generic class related to migration attribute about index changes
 */
#[Consumable(since: '30.0.0')]
class IndexMigrationAttribute extends MigrationAttribute {
	/**
	 * @param string $table name of the database table
	 * @param IndexType|null $type type of the index
	 * @param string $description description of the migration
	 * @param array $notes notes abour the migration/index
	 * @since 30.0.0
	 */
	public function __construct(
		string $table,
		private ?IndexType $type = null,
		string $description = '',
		array $notes = [],
	) {
		parent::__construct($table, $description, $notes);
	}

	/**
	 * @param IndexType|null $type
	 *
	 * @return $this
	 * @since 30.0.0
	 */
	public function setType(?IndexType $type): self {
		$this->type = $type;
		return $this;
	}

	/**
	 * @return IndexType|null
	 * @since 30.0.0
	 */
	public function getType(): ?IndexType {
		return $this->type;
	}

	/**
	 * @param array $data
	 *
	 * @return $this
	 * @since 30.0.0
	 */
	public function import(array $data): self {
		parent::import($data);
		$this->setType(IndexType::tryFrom($data['type'] ?? ''));
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
				'type' => $this->getType() ?? '',
			]
		);
	}
}
