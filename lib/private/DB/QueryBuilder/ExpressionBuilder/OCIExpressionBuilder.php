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

class OCIExpressionBuilder extends ExpressionBuilder {
	#[Override]
	protected function prepareColumn(IQueryFunction|ILiteral|IParameter|string|array $column, int|string|null $type): array|string {
		if ($type === IQueryBuilder::PARAM_STR && !is_array($column) && !($column instanceof IParameter) && !($column instanceof ILiteral)) {
			$column = $this->castColumn($column, $type);
		}

		return parent::prepareColumn($column, $type);
	}

	#[Override]
	public function eq(IQueryFunction|ILiteral|IParameter|string $x, IQueryFunction|ILiteral|IParameter|string $y, int|string|null $type = null): string {
		if ($type === IQueryBuilder::PARAM_JSON) {
			$x = $this->prepareColumn($x, $type);
			$y = $this->prepareColumn($y, $type);
			return (string)(new QueryFunction('JSON_EQUAL(' . $x . ',' . $y . ')'));
		}

		return parent::eq($x, $y, $type);
	}

	#[Override]
	public function neq(string|ILiteral|IQueryFunction|IParameter $x, string|ILiteral|IQueryFunction|IParameter $y, int|string|null $type = null): string {
		if ($type === IQueryBuilder::PARAM_JSON) {
			$x = $this->prepareColumn($x, $type);
			$y = $this->prepareColumn($y, $type);
			return (string)(new QueryFunction('NOT JSON_EQUAL(' . $x . ',' . $y . ')'));
		}

		return parent::neq($x, $y, $type);
	}

	#[Override]
	public function in(ILiteral|IParameter|IQueryFunction|string $x, ILiteral|IParameter|IQueryFunction|string|array $y, int|string|null $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);

		return $this->expressionBuilder->in($x, $y);
	}

	#[Override]
	public function notIn(ILiteral|IParameter|IQueryFunction|string $x, ILiteral|IParameter|IQueryFunction|string|array $y, int|string|null $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);

		return $this->expressionBuilder->notIn($x, $y);
	}

	#[Override]
	public function emptyString(string|ILiteral|IParameter|IQueryFunction $x): string {
		return $this->isNull($x);
	}

	#[Override]
	public function nonEmptyString(string|ILiteral|IParameter|IQueryFunction $x): string {
		return $this->isNotNull($x);
	}

	#[Override]
	public function castColumn(string|IQueryFunction|ILiteral|IParameter $column, int|string|null $type): IQueryFunction {
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

	#[Override]
	public function like(
		ILiteral|IParameter|IQueryFunction|string $x,
		ILiteral|IParameter|IQueryFunction|string $y,
		int|string|null $type = null,
	): string {
		return parent::like($x, $y, $type) . " ESCAPE '\\'";
	}

	#[Override]
	public function iLike(
		ILiteral|IParameter|IQueryFunction|string $x,
		ILiteral|IParameter|IQueryFunction|string $y,
		int|string|null $type = null,
	): string {
		return $this->like($this->functionBuilder->lower($x), $this->functionBuilder->lower($y));
	}
}
