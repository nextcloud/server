<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB;

use Doctrine\DBAL\Result;
use OCP\DB\IResult;
use Override;
use PDO;

/**
 * Adapts DBAL 2.6 API for DBAL 3.x for backwards compatibility of a leaked type
 */
class ResultAdapter implements IResult {
	public function __construct(
		private readonly Result $inner,
	) {
	}

	#[Override]
	public function closeCursor(): bool {
		$this->inner->free();

		return true;
	}

	#[Override]
	public function fetch(int $fetchMode = PDO::FETCH_ASSOC) {
		return match ($fetchMode) {
			PDO::FETCH_ASSOC => $this->inner->fetchAssociative(),
			PDO::FETCH_NUM => $this->inner->fetchNumeric(),
			PDO::FETCH_COLUMN => $this->inner->fetchOne(),
			default => throw new \Exception('Fetch mode needs to be assoc, num or column.'),
		};
	}

	#[Override]
	public function fetchAssociative(): array|false {
		return $this->inner->fetchAssociative();
	}

	#[Override]
	public function fetchNumeric(): array|false {
		return $this->inner->fetchNumeric();
	}

	#[Override]
	public function fetchOne(): mixed {
		return $this->inner->fetchOne();
	}

	#[Override]
	public function fetchAll(int $fetchMode = PDO::FETCH_ASSOC): array {
		return match ($fetchMode) {
			PDO::FETCH_ASSOC => $this->inner->fetchAllAssociative(),
			PDO::FETCH_NUM => $this->inner->fetchAllNumeric(),
			PDO::FETCH_COLUMN => $this->inner->fetchFirstColumn(),
			default => throw new \Exception('Fetch mode needs to be assoc, num or column.'),
		};
	}

	#[Override]
	public function fetchColumn($columnIndex = 0) {
		return $this->inner->fetchOne();
	}

	#[Override]
	public function rowCount(): int {
		return $this->inner->rowCount();
	}

	#[Override]
	public function fetchAllAssociative(): array {
		return $this->inner->fetchAllAssociative();
	}

	#[Override]
	public function fetchAllNumeric(): array {
		return $this->inner->fetchAllNumeric();
	}

	#[Override]
	public function fetchFirstColumn(): array {
		return $this->inner->fetchFirstColumn();
	}

	#[Override]
	public function iterateNumeric(): \Traversable {
		yield from $this->inner->iterateNumeric();
	}

	#[Override]
	public function iterateAssociative(): \Traversable {
		yield from $this->inner->iterateAssociative();
	}
}
