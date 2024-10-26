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

class OCIExpressionBuilder extends ExpressionBuilder {
	/**
	 * @param mixed $column
	 * @param mixed|null $type
	 * @return array|IQueryFunction|string
	 */
	protected function prepareColumn($column, $type) {
		if ($type === IQueryBuilder::PARAM_STR && !is_array($column) && !($column instanceof IParameter) && !($column instanceof ILiteral)) {
			$column = $this->castColumn($column, $type);
		}

		return parent::prepareColumn($column, $type);
	}

	/**
	 * @inheritdoc
	 */
	public function in($x, $y, $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);

		return $this->expressionBuilder->in($x, $y);
	}

	/**
	 * @inheritdoc
	 */
	public function notIn($x, $y, $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);

		return $this->expressionBuilder->notIn($x, $y);
	}

	/**
	 * Creates a $x = '' statement, because Oracle needs a different check
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $x The field in string format to be inspected by the comparison.
	 * @return string
	 * @since 13.0.0
	 */
	public function emptyString($x): string {
		return $this->isNull($x);
	}

	/**
	 * Creates a `$x <> ''` statement, because Oracle needs a different check
	 *
	 * @param string|ILiteral|IParameter|IQueryFunction $x The field in string format to be inspected by the comparison.
	 * @return string
	 * @since 13.0.0
	 */
	public function nonEmptyString($x): string {
		return $this->isNotNull($x);
	}

	/**
	 * Returns a IQueryFunction that casts the column to the given type
	 *
	 * @param string|IQueryFunction $column
	 * @param mixed $type One of IQueryBuilder::PARAM_*
	 * @psalm-param IQueryBuilder::PARAM_* $type
	 * @return IQueryFunction
	 */
	public function castColumn($column, $type): IQueryFunction {
		if ($type === IQueryBuilder::PARAM_STR) {
			$column = $this->helper->quoteColumnName($column);
			return new QueryFunction('to_char(' . $column . ')');
		}
		if ($type === IQueryBuilder::PARAM_INT) {
			$column = $this->helper->quoteColumnName($column);
			return new QueryFunction('to_number(to_char(' . $column . '))');
		}

		return parent::castColumn($column, $type);
	}

	/**
	 * @inheritdoc
	 */
	public function like($x, $y, $type = null): string {
		return parent::like($x, $y, $type) . " ESCAPE '\\'";
	}

	/**
	 * @inheritdoc
	 */
	public function iLike($x, $y, $type = null): string {
		return $this->like($this->functionBuilder->lower($x), $this->functionBuilder->lower($y));
	}
}
