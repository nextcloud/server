<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\DB;

use OCP\DB\IResult;
use PDO;

/**
 * Wrap an array or rows into a result interface
 */
class ArrayResult implements IResult {
	protected int $count;

	public function __construct(
		protected array $rows,
	) {
		$this->count = count($this->rows);
	}

	public function closeCursor(): bool {
		// noop
		return true;
	}

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

	public function fetchAll(int $fetchMode = PDO::FETCH_ASSOC): array {
		return match ($fetchMode) {
			PDO::FETCH_ASSOC => $this->rows,
			PDO::FETCH_NUM => array_map(function ($row) {
				return array_values($row);
			}, $this->rows),
			PDO::FETCH_COLUMN => array_map(function ($row) {
				return current($row);
			}, $this->rows),
			default => throw new \InvalidArgumentException('Fetch mode not supported for array result'),
		};
	}

	public function fetchColumn() {
		return $this->fetchOne();
	}

	public function fetchOne() {
		$row = $this->fetch();
		if ($row) {
			return current($row);
		} else {
			return false;
		}
	}

	public function rowCount(): int {
		return $this->count;
	}
}
