<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\DB\QueryBuilder\Partitioned;

use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * A sub-query from a partitioned join
 */
class PartitionQuery {
	public const JOIN_MODE_INNER = 'inner';
	public const JOIN_MODE_LEFT = 'left';
	// left-join where the left side IS NULL
	public const JOIN_MODE_LEFT_NULL = 'left_null';

	public const JOIN_MODE_RIGHT = 'right';

	public function __construct(
		public IQueryBuilder $query,
		public string $joinFromColumn,
		public string $joinToColumn,
		public string $joinMode,
	) {
		if ($joinMode !== self::JOIN_MODE_LEFT && $joinMode !== self::JOIN_MODE_INNER) {
			throw new InvalidPartitionedQueryException("$joinMode joins aren't allowed in partitioned queries");
		}
	}

	public function mergeWith(array $rows): array {
		if (empty($rows)) {
			return [];
		}
		// strip table/alias from column names
		$joinFromColumn = preg_replace('/\w+\./', '', $this->joinFromColumn);
		$joinToColumn = preg_replace('/\w+\./', '', $this->joinToColumn);

		$joinFromValues = array_map(function (array $row) use ($joinFromColumn) {
			return $row[$joinFromColumn];
		}, $rows);
		$joinFromValues = array_filter($joinFromValues, function ($value) {
			return $value !== null;
		});
		$this->query->andWhere($this->query->expr()->in($this->joinToColumn, $this->query->createNamedParameter($joinFromValues, IQueryBuilder::PARAM_STR_ARRAY, ':' . uniqid())));

		$s = $this->query->getSQL();
		$partitionedRows = $this->query->executeQuery()->fetchAll();

		$columns = $this->query->getOutputColumns();
		$nullResult = array_combine($columns, array_fill(0, count($columns), null));

		$partitionedRowsByKey = [];
		foreach ($partitionedRows as $partitionedRow) {
			$partitionedRowsByKey[$partitionedRow[$joinToColumn]][] = $partitionedRow;
		}
		$result = [];
		foreach ($rows as $row) {
			if (isset($partitionedRowsByKey[$row[$joinFromColumn]])) {
				if ($this->joinMode !== self::JOIN_MODE_LEFT_NULL) {
					foreach ($partitionedRowsByKey[$row[$joinFromColumn]] as $partitionedRow) {
						$result[] = array_merge($row, $partitionedRow);
					}
				}
			} elseif ($this->joinMode === self::JOIN_MODE_LEFT || $this->joinMode === self::JOIN_MODE_LEFT_NULL) {
				$result[] = array_merge($nullResult, $row);
			}
		}
		return $result;
	}
}
