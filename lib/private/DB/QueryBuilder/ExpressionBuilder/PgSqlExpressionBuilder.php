<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB\QueryBuilder\ExpressionBuilder;

use OC\DB\QueryBuilder\QueryFunction;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;

class PgSqlExpressionBuilder extends ExpressionBuilder {
	/**
	 * Returns a IQueryFunction that casts the column to the given type
	 *
	 * @param string|IQueryFunction $column
	 * @param mixed $type One of IQueryBuilder::PARAM_*
	 * @psalm-param IQueryBuilder::PARAM_* $type
	 * @return IQueryFunction
	 */
	public function castColumn($column, $type): IQueryFunction {
		switch ($type) {
			case IQueryBuilder::PARAM_INT:
				return new QueryFunction('CAST(' . $this->helper->quoteColumnName($column) . ' AS BIGINT)');
			case IQueryBuilder::PARAM_STR:
				return new QueryFunction('CAST(' . $this->helper->quoteColumnName($column) . ' AS TEXT)');
			default:
				return parent::castColumn($column, $type);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function iLike($x, $y, $type = null): string {
		$x = $this->helper->quoteColumnName($x);
		$y = $this->helper->quoteColumnName($y);
		return $this->expressionBuilder->comparison($x, 'ILIKE', $y);
	}
}
