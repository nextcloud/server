<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB\QueryBuilder\ExpressionBuilder;

use OC\DB\QueryBuilder\QueryFunction;
use OCP\DB\QueryBuilder\ILiteral;
use OCP\DB\QueryBuilder\IParameter;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;
use Override;

class SqliteExpressionBuilder extends ExpressionBuilder {
	#[Override]
	public function like(ILiteral|IParameter|IQueryFunction|string $x, mixed $y, int|string|null $type = null): string {
		return parent::like($x, $y, $type) . " ESCAPE '\\'";
	}

	#[Override]
	public function iLike(string|IParameter|ILiteral|IQueryFunction $x, mixed $y, int|string|null $type = null): string {
		return $this->like($this->functionBuilder->lower($x), $this->functionBuilder->lower($y), $type);
	}

	#[Override]
	protected function prepareColumn(IQueryFunction|ILiteral|IParameter|string|array $column, int|string|null $type): string|array {
		if (!($column instanceof IParameter)
			&& !($column instanceof IQueryFunction)
			&& !($column instanceof ILiteral)
			&& !is_array($column)
			&& is_string($type)
			&& (str_starts_with($type, 'date') || str_starts_with($type, 'time'))) {
			return (string)$this->castColumn($column, $type);
		}

		return parent::prepareColumn($column, $type);
	}

	#[Override]
	public function castColumn(string|IQueryFunction|ILiteral|IParameter $column, string|int $type): IQueryFunction {
		switch ($type) {
			case IQueryBuilder::PARAM_DATE_MUTABLE:
			case IQueryBuilder::PARAM_DATE_IMMUTABLE:
				$column = $this->helper->quoteColumnName($column);
				return new QueryFunction('DATE(' . $column . ')');
			case IQueryBuilder::PARAM_DATETIME_MUTABLE:
			case IQueryBuilder::PARAM_DATETIME_IMMUTABLE:
			case IQueryBuilder::PARAM_DATETIME_TZ_MUTABLE:
			case IQueryBuilder::PARAM_DATETIME_TZ_IMMUTABLE:
				$column = $this->helper->quoteColumnName($column);
				return new QueryFunction('DATETIME(' . $column . ')');
			case IQueryBuilder::PARAM_TIME_MUTABLE:
			case IQueryBuilder::PARAM_TIME_IMMUTABLE:
				$column = $this->helper->quoteColumnName($column);
				return new QueryFunction('TIME(' . $column . ')');
		}

		return parent::castColumn($column, $type);
	}
}
