<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\DB\QueryBuilder\Partitioned;

use OC\DB\ArrayResult;
use OCP\DB\IResult;
use PDO;

/**
 * Combine the results of multiple join parts into a single result
 */
class PartitionedResult extends ArrayResult {
	private bool $fetched = false;

	/**
	 * @param PartitionQuery[] $splitOfParts
	 * @param IResult $result
	 */
	public function __construct(
		private array $splitOfParts,
		private IResult $result,
	) {
		parent::__construct([]);
	}

	public function closeCursor(): bool {
		return $this->result->closeCursor();
	}

	public function fetch(int $fetchMode = PDO::FETCH_ASSOC) {
		$this->fetchRows();
		return parent::fetch($fetchMode);
	}

	public function fetchAll(int $fetchMode = PDO::FETCH_ASSOC): array {
		$this->fetchRows();
		return parent::fetchAll($fetchMode);
	}

	public function rowCount(): int {
		$this->fetchRows();
		return parent::rowCount();
	}

	private function fetchRows(): void {
		if (!$this->fetched) {
			$this->fetched = true;
			$this->rows = $this->result->fetchAll();
			foreach ($this->splitOfParts as $part) {
				$this->rows = $part->mergeWith($this->rows);
			}
			$this->count = count($this->rows);
		}
	}
}
