<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\DB;

use OCP\DB\IResult;
use Override;
use PDO;

/**
 * Wrap an array or rows into a result interface
 */
class ArrayResult implements IResult {
	protected int $count;

	public function __construct(
		/** @var array<string, mixed> $rows */
		protected array $rows,
	) {
		$this->count = count($this->rows);
	}

	#[Override]
	public function closeCursor(): bool {
		// noop
		return true;
	}

	#[Override]
	public function fetch(int $fetchMode = PDO::FETCH_ASSOC) {
		$row = array_shift($this->rows);
		if (!$row) {
			return false;
		}
		return match ($fetchMode) {
			PDO::FETCH_ASSOC => $row,
			PDO::FETCH_NUM => array_values($row),
			PDO::FETCH_COLUMN => current($row),
			default => throw new \InvalidArgumentException('Fetch mode not supported for array result'),
		};

	}

	#[Override]
	public function fetchAll(int $fetchMode = PDO::FETCH_ASSOC): array {
		return match ($fetchMode) {
			PDO::FETCH_ASSOC => $this->rows,
			PDO::FETCH_NUM => array_map(static fn (array $row): array => array_values($row), $this->rows),
			PDO::FETCH_COLUMN => array_map(static fn (array $row): mixed => current($row), $this->rows),
			default => throw new \InvalidArgumentException('Fetch mode not supported for array result'),
		};
	}

	#[Override]
	public function fetchColumn() {
		return $this->fetchOne();
	}

	#[Override]
	public function fetchOne() {
		$row = $this->fetch();
		if ($row) {
			return current($row);
		} else {
			return false;
		}
	}

	#[Override]
	public function fetchAssociative(): array|false {
		$row = $this->fetch();
		if ($row) {
			/** @var array<string, mixed> $row */
			return $row;
		} else {
			return false;
		}
	}

	#[Override]
	public function fetchNumeric(): array|false {
		$row = $this->fetch(PDO::FETCH_NUM);
		if ($row) {
			/** @var list<mixed> $row */
			return $row;
		} else {
			return false;
		}
	}

	#[Override]
	public function fetchAllNumeric(): array {
		/** @var list<list<mixed>> $result */
		$result = $this->fetchAll(PDO::FETCH_NUM);
		return $result;
	}

	#[Override]
	public function fetchAllAssociative(): array {
		/** @var list<array<string,mixed>> $result */
		$result = $this->fetchAll();
		return $result;
	}

	#[Override]
	public function fetchFirstColumn(): array {
		/** @var list<mixed> $result */
		$result = $this->fetchAll(PDO::FETCH_COLUMN);
		return $result;
	}

	#[Override]
	public function rowCount(): int {
		return $this->count;
	}

	#[Override]
	public function iterateNumeric(): \Traversable {
		while (($row = $this->fetchNumeric()) !== false) {
			yield $row;
		}
	}

	#[Override]
	public function iterateAssociative(): \Traversable {
		while (($row = $this->fetchAssociative()) !== false) {
			yield $row;
		}
	}
}
