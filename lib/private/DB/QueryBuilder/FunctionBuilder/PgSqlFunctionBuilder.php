<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB\QueryBuilder\FunctionBuilder;

use OC\DB\QueryBuilder\QueryFunction;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;

class PgSqlFunctionBuilder extends FunctionBuilder {
	public function concat($x, ...$expr): IQueryFunction {
		$args = func_get_args();
		$list = [];
		foreach ($args as $item) {
			$list[] = $this->queryBuilder->expr()->castColumn($item, IQueryBuilder::PARAM_STR);
		}
		return new QueryFunction(sprintf('(%s)', implode(' || ', $list)));
	}

	public function groupConcat($expr, ?string $separator = ','): IQueryFunction {
		$castedExpression = $this->queryBuilder->expr()->castColumn($expr, IQueryBuilder::PARAM_STR);

		if (is_null($separator)) {
			return new QueryFunction('string_agg(' . $castedExpression . ')');
		}

		$separator = $this->connection->quote($separator);
		return new QueryFunction('string_agg(' . $castedExpression . ', ' . $separator . ')');
	}
}
