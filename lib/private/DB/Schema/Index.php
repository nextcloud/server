<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\Schema;

use Doctrine\DBAL\Schema\Index as DBALIndex;
use OCP\DB\Schema\IIndex;

/**
 * Object representation of an index, wrapping a Doctrine DBAL Index.
 */
class Index implements IIndex {
	public function __construct(
		private DBALIndex $index,
	) {
	}

	/**
	 * Returns the wrapped Doctrine DBAL index.
	 */
	public function getWrappedIndex(): DBALIndex {
		return $this->index;
	}

	#[\Override]
	public function getName(): string {
		/** @var non-empty-string $name */
		$name = $this->index->getName();
		return $name;
	}

	#[\Override]
	public function getColumns(): array {
		return array_values($this->index->getColumns());
	}

	#[\Override]
	public function isUnique(): bool {
		return $this->index->isUnique();
	}

	#[\Override]
	public function isPrimary(): bool {
		return $this->index->isPrimary();
	}

	#[\Override]
	public function isSimpleIndex(): bool {
		return $this->index->isSimpleIndex();
	}

	#[\Override]
	public function hasColumnAtPosition(string $name, int $position = 0): bool {
		return $this->index->hasColumnAtPosition($name, $position);
	}

	#[\Override]
	public function spansColumns(array $columnNames): bool {
		return $this->index->spansColumns($columnNames);
	}

	/**
	 * Forwards any method not declared on IIndex to the wrapped Doctrine
	 * DBAL index, e.g. mutators like `addFlag()` or `removeFlag()` that are
	 * not part of the public API.
	 */
	public function __call(string $name, array $arguments): mixed {
		return $this->index->$name(...$arguments);
	}
}
