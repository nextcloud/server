<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use Attribute;

#[Attribute]
class TableCreation {
	public function __construct(
		public readonly string $table = '',
		public readonly string $description = ''
	) {}

	public function getTable(): string {
		return $this->table;
	}

	public function getDescription(): string {
		return $this->description;
	}
}
