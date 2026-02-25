<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace OC\DB\QueryBuilder\ExpressionBuilder;

use Doctrine\DBAL\Query\Expression\ExpressionBuilder as DoctrineExpressionBuilder;
use OC\DB\ConnectionAdapter;
use OC\DB\QueryBuilder\CompositeExpression;
use OC\DB\QueryBuilder\Literal;
use OC\DB\QueryBuilder\QueryFunction;
use OC\DB\QueryBuilder\QuoteHelper;
use OCP\DB\QueryBuilder\ICompositeExpression;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IFunctionBuilder;
use OCP\DB\QueryBuilder\ILiteral;
use OCP\DB\QueryBuilder\IParameter;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;
use Override;
use Psr\Log\LoggerInterface;

class ExpressionBuilder implements IExpressionBuilder {
	protected DoctrineExpressionBuilder $expressionBuilder;
	protected QuoteHelper $helper;
	protected IFunctionBuilder $functionBuilder;

	public function __construct(
		protected ConnectionAdapter $connection,
		IQueryBuilder $queryBuilder,
		protected LoggerInterface $logger,
	) {
		$this->helper = new QuoteHelper();
		$this->expressionBuilder = new DoctrineExpressionBuilder($this->connection->getInner());
		$this->functionBuilder = $queryBuilder->func();
	}

	#[Override]
	public function andX(ICompositeExpression|string ...$x): ICompositeExpression {
		if (empty($x)) {
			$this->logger->debug('Calling ' . IQueryBuilder::class . '::' . __FUNCTION__ . ' without parameters is deprecated and will throw soon.', ['exception' => new \Exception('No parameters in call to ' . __METHOD__)]);
		}
		return new CompositeExpression(CompositeExpression::TYPE_AND, $x);
	}

	#[Override]
	public function orX(ICompositeExpression|string ...$x): ICompositeExpression {
		if (empty($x)) {
			$this->logger->debug('Calling ' . IQueryBuilder::class . '::' . __FUNCTION__ . ' without parameters is deprecated and will throw soon.', ['exception' => new \Exception('No parameters in call to ' . __METHOD__)]);
		}
		return new CompositeExpression(CompositeExpression::TYPE_OR, $x);
	}

	#[Override]
	public function comparison(string|ILiteral|IQueryFunction|IParameter $x, string $operator, $y, int|string|null $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);
		return $this->expressionBuilder->comparison($x, $operator, $y);
	}

	#[Override]
	public function eq(string|ILiteral|IQueryFunction|IParameter $x, IQueryFunction|ILiteral|IParameter|string $y, int|string|null $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);
		return $this->expressionBuilder->eq($x, $y);
	}

	#[Override]
	public function neq(string|ILiteral|IQueryFunction|IParameter $x, string|ILiteral|IQueryFunction|IParameter $y, int|string|null $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);
		return $this->expressionBuilder->neq($x, $y);
	}

	#[Override]
	public function lt(string|ILiteral|IQueryFunction|IParameter $x, string|ILiteral|IQueryFunction|IParameter $y, int|string|null $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);
		return $this->expressionBuilder->lt($x, $y);
	}

	#[Override]
	public function lte(string|ILiteral|IQueryFunction|IParameter $x, string|ILiteral|IQueryFunction|IParameter $y, int|string|null $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);
		return $this->expressionBuilder->lte($x, $y);
	}

	#[Override]
	public function gt(string|ILiteral|IQueryFunction|IParameter $x, string|ILiteral|IQueryFunction|IParameter $y, int|string|null $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);
		return $this->expressionBuilder->gt($x, $y);
	}

	#[Override]
	public function gte(string|ILiteral|IQueryFunction|IParameter $x, string|ILiteral|IQueryFunction|IParameter $y, int|string|null $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);
		return $this->expressionBuilder->gte($x, $y);
	}

	#[Override]
	public function isNull(string|ILiteral|IParameter|IQueryFunction $x): string {
		$x = $this->helper->quoteColumnName($x);
		return $this->expressionBuilder->isNull($x);
	}

	#[Override]
	public function isNotNull(string|ILiteral|IParameter|IQueryFunction $x): string {
		$x = $this->helper->quoteColumnName($x);
		return $this->expressionBuilder->isNotNull($x);
	}

	#[Override]
	public function like(
		string|IParameter|ILiteral|IQueryFunction $x,
		string|IParameter|ILiteral|IQueryFunction $y,
		int|string|null $type = null,
	): string {
		$x = $this->helper->quoteColumnName($x);
		$y = $this->helper->quoteColumnName($y);
		return $this->expressionBuilder->like($x, $y);
	}

	#[Override]
	public function iLike(
		string|IParameter|ILiteral|IQueryFunction $x,
		string|IParameter|ILiteral|IQueryFunction $y,
		int|string|null $type = null,
	): string {
		return $this->expressionBuilder->like((string)$this->functionBuilder->lower($x), (string)$this->functionBuilder->lower($y));
	}

	#[Override]
	public function notLike(
		string|IParameter|ILiteral|IQueryFunction $x,
		string|IParameter|ILiteral|IQueryFunction $y,
		int|string|null $type = null,
	): string {
		$x = $this->helper->quoteColumnName($x);
		$y = $this->helper->quoteColumnName($y);
		return $this->expressionBuilder->notLike($x, $y);
	}

	#[Override]
	public function in(
		ILiteral|IParameter|IQueryFunction|string $x,
		ILiteral|IParameter|IQueryFunction|string|array $y,
		int|string|null $type = null,
	): string {
		$x = $this->helper->quoteColumnName($x);
		$y = $this->helper->quoteColumnNames($y);
		return $this->expressionBuilder->in($x, $y);
	}

	#[Override]
	public function notIn(
		ILiteral|IParameter|IQueryFunction|string $x,
		ILiteral|IParameter|IQueryFunction|string|array $y,
		int|string|null $type = null,
	): string {
		$x = $this->helper->quoteColumnName($x);
		$y = $this->helper->quoteColumnNames($y);
		return $this->expressionBuilder->notIn($x, $y);
	}

	#[Override]
	public function emptyString(string|ILiteral|IParameter|IQueryFunction $x): string {
		return $this->eq($x, $this->literal('', IQueryBuilder::PARAM_STR));
	}

	#[Override]
	public function nonEmptyString(string|ILiteral|IParameter|IQueryFunction $x): string {
		return $this->neq($x, $this->literal('', IQueryBuilder::PARAM_STR));
	}

	#[Override]
	public function bitwiseAnd(string|ILiteral $x, int $y): IQueryFunction {
		return new QueryFunction($this->connection->getDatabasePlatform()->getBitAndComparisonExpression(
			$this->helper->quoteColumnName($x),
			(string)$y
		));
	}

	#[Override]
	public function bitwiseOr(string|ILiteral $x, int $y): IQueryFunction {
		return new QueryFunction($this->connection->getDatabasePlatform()->getBitOrComparisonExpression(
			$this->helper->quoteColumnName($x),
			(string)$y
		));
	}

	#[Override]
	public function literal($input, int|string $type = IQueryBuilder::PARAM_STR): ILiteral {
		return new Literal($this->expressionBuilder->literal($input, $type));
	}

	#[Override]
	public function castColumn(string|IQueryFunction|ILiteral|IParameter $column, int|string $type): IQueryFunction {
		return new QueryFunction(
			$this->helper->quoteColumnName($column)
		);
	}

	/**
	 * @param IQueryBuilder::PARAM_* $type
	 */
	protected function prepareColumn(IQueryFunction|ILiteral|IParameter|string|array $column, int|string|null $type): array|string {
		return $this->helper->quoteColumnNames($column);
	}
}
