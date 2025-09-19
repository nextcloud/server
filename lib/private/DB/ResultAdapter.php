<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use OC\DB\Exceptions\DbalException;
use OCP\DB\IResult;
use PDO;

/**
 * Adapts DBAL 2.6 API for DBAL 3.x for backwards compatibility of a leaked type
 */
class ResultAdapter implements IResult {
	/** @var Result */
	private $inner;

	public function __construct(Result $inner) {
		$this->inner = $inner;
	}

	public function closeCursor(): bool {
		$this->inner->free();

		return true;
	}

	public function fetch(int $fetchMode = PDO::FETCH_ASSOC) {
		return match ($fetchMode) {
			PDO::FETCH_ASSOC => $this->inner->fetchAssociative(),
			PDO::FETCH_NUM => $this->inner->fetchNumeric(),
			PDO::FETCH_COLUMN => $this->inner->fetchOne(),
			default => throw new \Exception('Fetch mode needs to be assoc, num or column.'),
		};
	}

	public function fetchAssociative(): array|false {
		try {
			return $this->inner->fetchAssociative();
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function fetchAllAssociative(): array {
		try {
			return $this->inner->fetchAllAssociative();
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function fetchAll(int $fetchMode = PDO::FETCH_ASSOC): array {
		return match ($fetchMode) {
			PDO::FETCH_ASSOC => $this->inner->fetchAllAssociative(),
			PDO::FETCH_NUM => $this->inner->fetchAllNumeric(),
			PDO::FETCH_COLUMN => $this->inner->fetchFirstColumn(),
			default => throw new \Exception('Fetch mode needs to be assoc, num or column.'),
		};
	}

	public function fetchColumn($columnIndex = 0) {
		return $this->inner->fetchOne();
	}

	public function fetchNumeric(): array|false {
		try {
			return $this->inner->fetchNumeric();
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function fetchAllNumeric(): array {
		try {
			return $this->inner->fetchAllNumeric();
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function fetchOne() {
		return $this->inner->fetchOne();
	}

	public function fetchFirstColumn(): array {
		try {
			return $this->inner->fetchFirstColumn();
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function rowCount(): int {
		return $this->inner->rowCount();
	}
}
