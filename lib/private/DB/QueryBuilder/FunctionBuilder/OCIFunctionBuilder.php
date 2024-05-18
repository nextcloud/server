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
use OCP\DB\QueryBuilder\ILiteral;
use OCP\DB\QueryBuilder\IParameter;
use OCP\DB\QueryBuilder\IQueryFunction;

class OCIFunctionBuilder extends FunctionBuilder {
	public function md5($input): IQueryFunction {
		return new QueryFunction('LOWER(DBMS_OBFUSCATION_TOOLKIT.md5 (input => UTL_RAW.cast_to_raw(' . $this->helper->quoteColumnName($input) .')))');
	}

	/**
	 * As per https://docs.oracle.com/cd/B19306_01/server.102/b14200/functions060.htm
	 * Oracle uses the first value to cast the rest or the values. So when the
	 * first value is a literal, plain value or column, instead of doing the
	 * math, it will cast the expression to int and continue with a "0". So when
	 * the second parameter is a function or column, we have to put that as
	 * first parameter.
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $x
	 * @param string|ILiteral|IParameter|IQueryFunction $y
	 * @return IQueryFunction
	 */
	public function greatest($x, $y): IQueryFunction {
		if (is_string($y) || $y instanceof IQueryFunction) {
			return parent::greatest($y, $x);
		}

		return parent::greatest($x, $y);
	}

	/**
	 * As per https://docs.oracle.com/cd/B19306_01/server.102/b14200/functions060.htm
	 * Oracle uses the first value to cast the rest or the values. So when the
	 * first value is a literal, plain value or column, instead of doing the
	 * math, it will cast the expression to int and continue with a "0". So when
	 * the second parameter is a function or column, we have to put that as
	 * first parameter.
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $x
	 * @param string|ILiteral|IParameter|IQueryFunction $y
	 * @return IQueryFunction
	 */
	public function least($x, $y): IQueryFunction {
		if (is_string($y) || $y instanceof IQueryFunction) {
			return parent::least($y, $x);
		}

		return parent::least($x, $y);
	}

	public function concat($x, ...$expr): IQueryFunction {
		$args = func_get_args();
		$list = [];
		foreach ($args as $item) {
			$list[] = $this->helper->quoteColumnName($item);
		}
		return new QueryFunction(sprintf('(%s)', implode(' || ', $list)));
	}

	public function groupConcat($expr, ?string $separator = ','): IQueryFunction {
		$orderByClause = ' WITHIN GROUP(ORDER BY NULL)';
		if (is_null($separator)) {
			return new QueryFunction('LISTAGG(' . $this->helper->quoteColumnName($expr) . ')' . $orderByClause);
		}

		$separator = $this->connection->quote($separator);
		return new QueryFunction('LISTAGG(' . $this->helper->quoteColumnName($expr) . ', ' . $separator . ')' . $orderByClause);
	}

	public function octetLength($field, $alias = ''): IQueryFunction {
		$alias = $alias ? (' AS ' . $this->helper->quoteColumnName($alias)) : '';
		$quotedName = $this->helper->quoteColumnName($field);
		return new QueryFunction('LENGTHB(' . $quotedName . ')' . $alias);
	}

	public function charLength($field, $alias = ''): IQueryFunction {
		$alias = $alias ? (' AS ' . $this->helper->quoteColumnName($alias)) : '';
		$quotedName = $this->helper->quoteColumnName($field);
		return new QueryFunction('LENGTH(' . $quotedName . ')' . $alias);
	}
}
