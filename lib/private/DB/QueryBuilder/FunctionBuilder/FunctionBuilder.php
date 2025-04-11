<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB\QueryBuilder\FunctionBuilder;

use OC\DB\Connection;
use OC\DB\QueryBuilder\QueryFunction;
use OC\DB\QueryBuilder\QuoteHelper;
use OCP\DB\QueryBuilder\IFunctionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;
use OCP\IDBConnection;

class FunctionBuilder implements IFunctionBuilder {
	/** @var IDBConnection|Connection */
	protected $connection;

	/** @var IQueryBuilder */
	protected $queryBuilder;

	/** @var QuoteHelper */
	protected $helper;

	public function __construct(IDBConnection $connection, IQueryBuilder $queryBuilder, QuoteHelper $helper) {
		$this->connection = $connection;
		$this->queryBuilder = $queryBuilder;
		$this->helper = $helper;
	}

	public function md5($input): IQueryFunction {
		return new QueryFunction('MD5(' . $this->helper->quoteColumnName($input) . ')');
	}

	public function concat($x, ...$expr): IQueryFunction {
		$args = func_get_args();
		$list = [];
		foreach ($args as $item) {
			$list[] = $this->helper->quoteColumnName($item);
		}
		return new QueryFunction(sprintf('CONCAT(%s)', implode(', ', $list)));
	}

	public function groupConcat($expr, ?string $separator = ','): IQueryFunction {
		$separator = $this->connection->quote($separator);
		return new QueryFunction('GROUP_CONCAT(' . $this->helper->quoteColumnName($expr) . ' SEPARATOR ' . $separator . ')');
	}

	public function substring($input, $start, $length = null): IQueryFunction {
		if ($length) {
			return new QueryFunction('SUBSTR(' . $this->helper->quoteColumnName($input) . ', ' . $this->helper->quoteColumnName($start) . ', ' . $this->helper->quoteColumnName($length) . ')');
		} else {
			return new QueryFunction('SUBSTR(' . $this->helper->quoteColumnName($input) . ', ' . $this->helper->quoteColumnName($start) . ')');
		}
	}

	public function sum($field): IQueryFunction {
		return new QueryFunction('SUM(' . $this->helper->quoteColumnName($field) . ')');
	}

	public function lower($field): IQueryFunction {
		return new QueryFunction('LOWER(' . $this->helper->quoteColumnName($field) . ')');
	}

	public function add($x, $y): IQueryFunction {
		return new QueryFunction($this->helper->quoteColumnName($x) . ' + ' . $this->helper->quoteColumnName($y));
	}

	public function subtract($x, $y): IQueryFunction {
		return new QueryFunction($this->helper->quoteColumnName($x) . ' - ' . $this->helper->quoteColumnName($y));
	}

	public function count($count = '', $alias = ''): IQueryFunction {
		$alias = $alias ? (' AS ' . $this->helper->quoteColumnName($alias)) : '';
		$quotedName = $count === '' ? '*' : $this->helper->quoteColumnName($count);
		return new QueryFunction('COUNT(' . $quotedName . ')' . $alias);
	}

	public function octetLength($field, $alias = ''): IQueryFunction {
		$alias = $alias ? (' AS ' . $this->helper->quoteColumnName($alias)) : '';
		$quotedName = $this->helper->quoteColumnName($field);
		return new QueryFunction('OCTET_LENGTH(' . $quotedName . ')' . $alias);
	}

	public function charLength($field, $alias = ''): IQueryFunction {
		$alias = $alias ? (' AS ' . $this->helper->quoteColumnName($alias)) : '';
		$quotedName = $this->helper->quoteColumnName($field);
		return new QueryFunction('CHAR_LENGTH(' . $quotedName . ')' . $alias);
	}

	public function max($field): IQueryFunction {
		return new QueryFunction('MAX(' . $this->helper->quoteColumnName($field) . ')');
	}

	public function min($field): IQueryFunction {
		return new QueryFunction('MIN(' . $this->helper->quoteColumnName($field) . ')');
	}

	public function greatest($x, $y): IQueryFunction {
		return new QueryFunction('GREATEST(' . $this->helper->quoteColumnName($x) . ', ' . $this->helper->quoteColumnName($y) . ')');
	}

	public function least($x, $y): IQueryFunction {
		return new QueryFunction('LEAST(' . $this->helper->quoteColumnName($x) . ', ' . $this->helper->quoteColumnName($y) . ')');
	}
}
