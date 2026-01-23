<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB\QueryBuilder\FunctionBuilder;

use OC\DB\ConnectionAdapter;
use OC\DB\QueryBuilder\QueryFunction;
use OC\DB\QueryBuilder\QuoteHelper;
use OCP\DB\QueryBuilder\IFunctionBuilder;
use OCP\DB\QueryBuilder\ILiteral;
use OCP\DB\QueryBuilder\IParameter;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;
use Override;

class FunctionBuilder implements IFunctionBuilder {
	public function __construct(
		protected readonly ConnectionAdapter $connection,
		protected readonly IQueryBuilder $queryBuilder,
		protected readonly QuoteHelper $helper,
	) {
	}

	#[Override]
	public function md5(string|ILiteral|IParameter|IQueryFunction $input): IQueryFunction {
		return new QueryFunction('MD5(' . $this->helper->quoteColumnName($input) . ')');
	}

	#[Override]
	public function concat(string|ILiteral|IParameter|IQueryFunction $x, string|ILiteral|IParameter|IQueryFunction ...$expr): IQueryFunction {
		$args = func_get_args();
		$list = [];
		foreach ($args as $item) {
			$list[] = $this->helper->quoteColumnName($item);
		}
		return new QueryFunction(sprintf('CONCAT(%s)', implode(', ', $list)));
	}

	#[Override]
	public function groupConcat(string|ILiteral|IParameter|IQueryFunction $expr, ?string $separator = ','): IQueryFunction {
		$separator = $this->connection->quote($separator);
		return new QueryFunction('GROUP_CONCAT(' . $this->helper->quoteColumnName($expr) . ' SEPARATOR ' . $separator . ')');
	}

	#[Override]
	public function substring(
		string|ILiteral|IParameter|IQueryFunction $input,
		string|ILiteral|IParameter|IQueryFunction $start,
		null|ILiteral|IParameter|IQueryFunction $length = null,
	): IQueryFunction {
		if ($length) {
			return new QueryFunction('SUBSTR(' . $this->helper->quoteColumnName($input) . ', ' . $this->helper->quoteColumnName($start) . ', ' . $this->helper->quoteColumnName($length) . ')');
		} else {
			return new QueryFunction('SUBSTR(' . $this->helper->quoteColumnName($input) . ', ' . $this->helper->quoteColumnName($start) . ')');
		}
	}

	#[Override]
	public function sum(string|ILiteral|IParameter|IQueryFunction $field): IQueryFunction {
		return new QueryFunction('SUM(' . $this->helper->quoteColumnName($field) . ')');
	}

	#[Override]
	public function lower(string|ILiteral|IParameter|IQueryFunction $field): IQueryFunction {
		return new QueryFunction('LOWER(' . $this->helper->quoteColumnName($field) . ')');
	}

	#[Override]
	public function add(
		string|ILiteral|IParameter|IQueryFunction $x,
		string|ILiteral|IParameter|IQueryFunction $y,
	): IQueryFunction {
		return new QueryFunction($this->helper->quoteColumnName($x) . ' + ' . $this->helper->quoteColumnName($y));
	}

	#[Override]
	public function subtract(
		string|ILiteral|IParameter|IQueryFunction $x,
		string|ILiteral|IParameter|IQueryFunction $y,
	): IQueryFunction {
		return new QueryFunction($this->helper->quoteColumnName($x) . ' - ' . $this->helper->quoteColumnName($y));
	}

	#[Override]
	public function count(string|ILiteral|IParameter|IQueryFunction $count = '', string $alias = ''): IQueryFunction {
		$alias = $alias ? (' AS ' . $this->helper->quoteColumnName($alias)) : '';
		$quotedName = $count === '' ? '*' : $this->helper->quoteColumnName($count);
		return new QueryFunction('COUNT(' . $quotedName . ')' . $alias);
	}

	#[Override]
	public function octetLength(string|ILiteral|IParameter|IQueryFunction $field, string $alias = ''): IQueryFunction {
		$alias = $alias ? (' AS ' . $this->helper->quoteColumnName($alias)) : '';
		$quotedName = $this->helper->quoteColumnName($field);
		return new QueryFunction('OCTET_LENGTH(' . $quotedName . ')' . $alias);
	}

	#[Override]
	public function charLength(string|ILiteral|IParameter|IQueryFunction $field, string $alias = ''): IQueryFunction {
		$alias = $alias ? (' AS ' . $this->helper->quoteColumnName($alias)) : '';
		$quotedName = $this->helper->quoteColumnName($field);
		return new QueryFunction('CHAR_LENGTH(' . $quotedName . ')' . $alias);
	}

	#[Override]
	public function max(string|ILiteral|IParameter|IQueryFunction $field): IQueryFunction {
		return new QueryFunction('MAX(' . $this->helper->quoteColumnName($field) . ')');
	}

	#[Override]
	public function min(string|ILiteral|IParameter|IQueryFunction $field): IQueryFunction {
		return new QueryFunction('MIN(' . $this->helper->quoteColumnName($field) . ')');
	}

	#[Override]
	public function greatest(
		string|ILiteral|IParameter|IQueryFunction $x,
		string|ILiteral|IParameter|IQueryFunction $y,
	): IQueryFunction {
		return new QueryFunction('GREATEST(' . $this->helper->quoteColumnName($x) . ', ' . $this->helper->quoteColumnName($y) . ')');
	}

	#[Override]
	public function least(
		string|ILiteral|IParameter|IQueryFunction $x,
		string|ILiteral|IParameter|IQueryFunction $y,
	): IQueryFunction {
		return new QueryFunction('LEAST(' . $this->helper->quoteColumnName($x) . ', ' . $this->helper->quoteColumnName($y) . ')');
	}

	#[Override]
	public function now(): IQueryFunction {
		return new QueryFunction('NOW()');
	}
}
