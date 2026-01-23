<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB\QueryBuilder\ExpressionBuilder;

use OC\DB\QueryBuilder\QueryFunction;
use OCP\DB\QueryBuilder\ILiteral;
use OCP\DB\QueryBuilder\IParameter;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;
use Override;

class PgSqlExpressionBuilder extends ExpressionBuilder {
	#[Override]
	public function castColumn(string|IQueryFunction|ILiteral|IParameter $column, string|int $type): IQueryFunction {
		return match ($type) {
			IQueryBuilder::PARAM_INT => new QueryFunction('CAST(' . $this->helper->quoteColumnName($column) . ' AS BIGINT)'),
			IQueryBuilder::PARAM_STR, IQueryBuilder::PARAM_JSON => new QueryFunction('CAST(' . $this->helper->quoteColumnName($column) . ' AS TEXT)'),
			default => parent::castColumn($column, $type),
		};
	}

	#[Override]
	protected function prepareColumn(IQueryFunction|ILiteral|IParameter|string|array $column, int|string|null $type): string|array {
		if ($type === IQueryBuilder::PARAM_JSON && !is_array($column) && !($column instanceof IParameter) && !($column instanceof ILiteral)) {
			$column = $this->castColumn($column, $type);
		}

		return parent::prepareColumn($column, $type);
	}

	#[Override]
	public function iLike(string|IParameter|ILiteral|IQueryFunction $x, mixed $y, mixed $type = null): string {
		$x = $this->helper->quoteColumnName($x);
		$y = $this->helper->quoteColumnName($y);
		return $this->expressionBuilder->comparison($x, 'ILIKE', $y);
	}
}
