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
use OCP\DB\QueryBuilder\IQueryFunction;

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

	public function greatest($x, $y): IQueryFunction {
		return new QueryFunction('MAX(' . $this->helper->quoteColumnName($x) . ', ' . $this->helper->quoteColumnName($y) . ')');
	}

	public function least($x, $y): IQueryFunction {
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
