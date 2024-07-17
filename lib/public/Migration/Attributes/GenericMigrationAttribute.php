<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use JsonSerializable;

class GenericMigrationAttribute extends MigrationAttribute implements JsonSerializable {
	public function __construct(
		private readonly array $details = []
	) {
		parent::__construct(
			$details['table'] ?? '',
			$details['description'] ?? '',
			$details['notes'] ?? []
		);
	}

	public function definition(): string {
		return json_encode($this->jsonSerialize(), JSON_UNESCAPED_SLASHES);
	}

	public function jsonSerialize(): array {
		return $this->details;
	}
}
