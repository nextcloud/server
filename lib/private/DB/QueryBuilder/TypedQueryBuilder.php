<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB\QueryBuilder;

use OCP\DB\QueryBuilder\ITypedQueryBuilder;
use RuntimeException;

/**
 * @psalm-suppress InvalidTemplateParam
 * @template-implements ITypedQueryBuilder<string>
 */
abstract class TypedQueryBuilder implements ITypedQueryBuilder {
	private function validateColumn(string $column): void {
		if (str_contains($column, '.') || trim($column) === '*') {
			throw new RuntimeException('Only column names are allowed, got: ' . $column);
		}
	}

	public function selectColumns(string ...$columns): static {
		foreach ($columns as $column) {
			$this->validateColumn($column);
		}

		return $this->select(...$columns);
	}

	public function selectColumnsDistinct(string ...$columns): static {
		foreach ($columns as $column) {
			$this->validateColumn($column);
		}

		return $this->selectDistinct($columns);
	}
}
