<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\DB\QueryBuilder\FunctionBuilder;

use OC\DB\QueryBuilder\QueryFunction;
use OC\DB\QueryBuilder\QuoteHelper;
use OCP\DB\QueryBuilder\IFunctionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;
use OCP\IDBConnection;

class FunctionBuilder implements IFunctionBuilder {
	/** @var IDBConnection */
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
