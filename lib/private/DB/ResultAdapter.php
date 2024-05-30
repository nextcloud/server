<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB;

use Doctrine\DBAL\Result;
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
		return $this->inner->fetch($fetchMode);
	}

	public function fetchAll(int $fetchMode = PDO::FETCH_ASSOC): array {
		if ($fetchMode !== PDO::FETCH_ASSOC && $fetchMode !== PDO::FETCH_NUM && $fetchMode !== PDO::FETCH_COLUMN) {
			throw new \Exception('Fetch mode needs to be assoc, num or column.');
		}
		return $this->inner->fetchAll($fetchMode);
	}

	public function fetchColumn($columnIndex = 0) {
		return $this->inner->fetchOne();
	}

	public function fetchOne() {
		return $this->inner->fetchOne();
	}

	public function rowCount(): int {
		return $this->inner->rowCount();
	}
}
