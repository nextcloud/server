<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB\QueryBuilder\FunctionBuilder;

use OC\DB\QueryBuilder\QueryFunction;
use OCP\DB\QueryBuilder\ILiteral;
use OCP\DB\QueryBuilder\IParameter;
use OCP\DB\QueryBuilder\IQueryFunction;
use Override;

class SqliteFunctionBuilder extends FunctionBuilder {
	public function concat($x, ...$expr): IQueryFunction {
		$args = func_get_args();
		$list = [];
		foreach ($args as $item) {
			$list[] = $this->helper->quoteColumnName($item);
		}
		return new QueryFunction(sprintf('(%s)', implode(' || ', $list)));
	}

	public function groupConcat($expr, ?string $separator = ','): IQueryFunction {
		$separator = $this->connection->quote($separator);
		return new QueryFunction('GROUP_CONCAT(' . $this->helper->quoteColumnName($expr) . ', ' . $separator . ')');
	}

	#[Override]
	public function greatest(
		string|ILiteral|IParameter|IQueryFunction $x,
		string|ILiteral|IParameter|IQueryFunction $y,
	): IQueryFunction {
		return new QueryFunction('MAX(' . $this->helper->quoteColumnName($x) . ', ' . $this->helper->quoteColumnName($y) . ')');
	}

	#[Override]
	public function least(
		string|ILiteral|IParameter|IQueryFunction $x,
		string|ILiteral|IParameter|IQueryFunction $y,
	): IQueryFunction {
		return new QueryFunction('MIN(' . $this->helper->quoteColumnName($x) . ', ' . $this->helper->quoteColumnName($y) . ')');
	}

	public function octetLength($field, $alias = ''): IQueryFunction {
		$alias = $alias ? (' AS ' . $this->helper->quoteColumnName($alias)) : '';
		$quotedName = $this->helper->quoteColumnName($field);
		return new QueryFunction('LENGTH(CAST(' . $quotedName . ' as BLOB))' . $alias);
	}

	public function charLength($field, $alias = ''): IQueryFunction {
		$alias = $alias ? (' AS ' . $this->helper->quoteColumnName($alias)) : '';
		$quotedName = $this->helper->quoteColumnName($field);
		return new QueryFunction('LENGTH(' . $quotedName . ')' . $alias);
	}
}
