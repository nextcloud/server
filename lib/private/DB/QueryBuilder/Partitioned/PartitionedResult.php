<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
		private IResult $result
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
		}
	}
}
