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
		return $this->index->getName();
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
	public function spansColumns(array $columnNames): bool {
		return $this->index->spansColumns($columnNames);
	}

	#[\Override]
	public function hasColumnAtPosition(string $name, int $pos = 0): bool {
		return $this->index->hasColumnAtPosition($name, $pos);
	}

	#[\Override]
	public function getFlags(): array {
		return array_values($this->index->getFlags());
	}

	#[\Override]
	public function hasFlag(string $flag): bool {
		return $this->index->hasFlag($flag);
	}

	#[\Override]
	public function hasOption(string $name): bool {
		return $this->index->hasOption($name);
	}

	#[\Override]
	public function getOption(string $name): mixed {
		return $this->index->getOption($name);
	}

	#[\Override]
	public function getOptions(): array {
		return $this->index->getOptions();
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
