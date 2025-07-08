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

class SqliteExpressionBuilder extends ExpressionBuilder {
	/**
	 * @inheritdoc
	 */
	public function like($x, $y, $type = null): string {
		return parent::like($x, $y, $type) . " ESCAPE '\\'";
	}

	public function iLike($x, $y, $type = null): string {
		return $this->like($this->functionBuilder->lower($x), $this->functionBuilder->lower($y), $type);
	}

	/**
	 * @param mixed $column
	 * @param mixed|null $type
	 * @return array|IQueryFunction|string
	 */
	protected function prepareColumn($column, $type) {
		if ($type !== null
			&& !is_array($column)
			&& !($column instanceof IParameter)
			&& !($column instanceof ILiteral)
			&& (str_starts_with($type, 'date') || str_starts_with($type, 'time'))) {
			return $this->castColumn($column, $type);
		}

		return parent::prepareColumn($column, $type);
	}

	/**
	 * Returns a IQueryFunction that casts the column to the given type
	 *
	 * @param string $column
	 * @param mixed $type One of IQueryBuilder::PARAM_*
	 * @return IQueryFunction
	 */
	public function castColumn($column, $type): IQueryFunction {
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
