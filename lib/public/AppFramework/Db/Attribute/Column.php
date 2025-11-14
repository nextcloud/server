<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Db\Attribute;

use Attribute;
use OCP\AppFramework\Attribute\Consumable;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;

#[Attribute(Attribute::TARGET_PROPERTY)]
#[Consumable(since: '33.0.0')]
final readonly class Column {
	public function __construct(
		public string $name,
		public string|null $type,
		public int|null $length = null,
		public bool $nullable = false,
		public mixed $default = null,
	) {
	}
}
