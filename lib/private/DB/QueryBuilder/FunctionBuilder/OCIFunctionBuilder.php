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

class OCIFunctionBuilder extends FunctionBuilder {
	public function md5($input): IQueryFunction {
		if (version_compare($this->connection->getServerVersion(), '20', '>=')) {
			return new QueryFunction('LOWER(STANDARD_HASH(' . $this->helper->quoteColumnName($input) . ", 'MD5'))");
		}
		return new QueryFunction('LOWER(DBMS_OBFUSCATION_TOOLKIT.md5 (input => UTL_RAW.cast_to_raw(' . $this->helper->quoteColumnName($input) . ')))');
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
		return new QueryFunction('COALESCE(LENGTHB(' . $quotedName . '), 0)' . $alias);
	}

	public function charLength($field, $alias = ''): IQueryFunction {
		$alias = $alias ? (' AS ' . $this->helper->quoteColumnName($alias)) : '';
		$quotedName = $this->helper->quoteColumnName($field);
		return new QueryFunction('COALESCE(LENGTH(' . $quotedName . '), 0)' . $alias);
	}
}
