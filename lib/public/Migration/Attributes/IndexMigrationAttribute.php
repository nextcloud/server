<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use JsonSerializable;

class IndexMigrationAttribute extends MigrationAttribute implements JsonSerializable {
	public function __construct(
		string $table = '',
		private ?IndexType $type = null,
		string $description = '',
		array $notes = [],
	) {
		parent::__construct($table, $description, $notes);
	}

	public function setType(?IndexType $type): self {
		$this->type = $type;
		return $this;
	}

	public function getType(): ?IndexType {
		return $this->type;
	}

	public function import(array $data): self {
		parent::import($data);
		$this->setType(IndexType::tryFrom($data['type'] ?? ''));
		return $this;
	}

	public function jsonSerialize(): array {
		return array_merge(
			parent::jsonSerialize(),
			[
				'type' => $this->getType() ?? '',
			]
		);
	}
}
