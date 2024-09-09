<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB\QueryBuilder\ExpressionBuilder;

use Doctrine\DBAL\Query\Expression\ExpressionBuilder as DoctrineExpressionBuilder;
use OC\DB\ConnectionAdapter;
use OC\DB\QueryBuilder\CompositeExpression;
use OC\DB\QueryBuilder\FunctionBuilder\FunctionBuilder;
use OC\DB\QueryBuilder\Literal;
use OC\DB\QueryBuilder\QueryFunction;
use OC\DB\QueryBuilder\QuoteHelper;
use OCP\DB\QueryBuilder\ICompositeExpression;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\ILiteral;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class ExpressionBuilder implements IExpressionBuilder {
	/** @var \Doctrine\DBAL\Query\Expression\ExpressionBuilder */
	protected $expressionBuilder;

	/** @var QuoteHelper */
	protected $helper;

	/** @var IDBConnection */
	protected $connection;

	/** @var LoggerInterface */
	protected $logger;

	/** @var FunctionBuilder */
	protected $functionBuilder;

	public function __construct(ConnectionAdapter $connection, IQueryBuilder $queryBuilder, LoggerInterface $logger) {
		$this->connection = $connection;
		$this->logger = $logger;
		$this->helper = new QuoteHelper();
		$this->expressionBuilder = new DoctrineExpressionBuilder($connection->getInner());
		$this->functionBuilder = $queryBuilder->func();
	}

	public function andX(...$x): ICompositeExpression {
		if (empty($x)) {
			$this->logger->debug('Calling ' . IQueryBuilder::class . '::' . __FUNCTION__ . ' without parameters is deprecated and will throw soon.', ['exception' => new \Exception('No parameters in call to ' . __METHOD__)]);
		}
		return new CompositeExpression(CompositeExpression::TYPE_AND, $x);
	}

	public function orX(...$x): ICompositeExpression {
		if (empty($x)) {
			$this->logger->debug('Calling ' . IQueryBuilder::class . '::' . __FUNCTION__ . ' without parameters is deprecated and will throw soon.', ['exception' => new \Exception('No parameters in call to ' . __METHOD__)]);
		}
		return new CompositeExpression(CompositeExpression::TYPE_OR, $x);
	}

	public function comparison($x, string $operator, $y, $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);
		return $this->expressionBuilder->comparison($x, $operator, $y);
	}

	public function eq($x, $y, $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);
		return $this->expressionBuilder->eq($x, $y);
	}

	public function neq($x, $y, $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);
		return $this->expressionBuilder->neq($x, $y);
	}

	public function lt($x, $y, $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);
		return $this->expressionBuilder->lt($x, $y);
	}

	public function lte($x, $y, $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);
		return $this->expressionBuilder->lte($x, $y);
	}

	public function gt($x, $y, $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);
		return $this->expressionBuilder->gt($x, $y);
	}

	public function gte($x, $y, $type = null): string {
		$x = $this->prepareColumn($x, $type);
		$y = $this->prepareColumn($y, $type);
		return $this->expressionBuilder->gte($x, $y);
	}

	public function isNull($x): string {
		$x = $this->helper->quoteColumnName($x);
		return $this->expressionBuilder->isNull($x);
	}

	public function isNotNull($x): string {
		$x = $this->helper->quoteColumnName($x);
		return $this->expressionBuilder->isNotNull($x);
	}

	public function like($x, $y, $type = null): string {
		$x = $this->helper->quoteColumnName($x);
		$y = $this->helper->quoteColumnName($y);
		return $this->expressionBuilder->like($x, $y);
	}

	public function iLike($x, $y, $type = null): string {
		return $this->expressionBuilder->like($this->functionBuilder->lower($x), $this->functionBuilder->lower($y));
	}

	public function notLike($x, $y, $type = null): string {
		$x = $this->helper->quoteColumnName($x);
		$y = $this->helper->quoteColumnName($y);
		return $this->expressionBuilder->notLike($x, $y);
	}

	public function in($x, $y, $type = null): string {
		$x = $this->helper->quoteColumnName($x);
		$y = $this->helper->quoteColumnNames($y);
		return $this->expressionBuilder->in($x, $y);
	}

	public function notIn($x, $y, $type = null): string {
		$x = $this->helper->quoteColumnName($x);
		$y = $this->helper->quoteColumnNames($y);
		return $this->expressionBuilder->notIn($x, $y);
	}

	public function emptyString($x): string {
		return $this->eq($x, $this->literal('', IQueryBuilder::PARAM_STR));
	}

	public function nonEmptyString($x): string {
		return $this->neq($x, $this->literal('', IQueryBuilder::PARAM_STR));
	}

	public function bitwiseAnd($x, int $y): IQueryFunction {
		return new QueryFunction($this->connection->getDatabasePlatform()->getBitAndComparisonExpression(
			$this->helper->quoteColumnName($x),
			$y
		));
	}

	public function bitwiseOr($x, int $y): IQueryFunction {
		return new QueryFunction($this->connection->getDatabasePlatform()->getBitOrComparisonExpression(
			$this->helper->quoteColumnName($x),
			$y
		));
	}

	public function literal($input, $type = IQueryBuilder::PARAM_STR): ILiteral {
		return new Literal($this->expressionBuilder->literal($input, $type));
	}

	public function castColumn($column, $type): IQueryFunction {
		return new QueryFunction(
			$this->helper->quoteColumnName($column)
		);
	}

	/**
	 * @param mixed $column
	 * @param mixed|null $type
	 * @return array|IQueryFunction|string
	 */
	protected function prepareColumn($column, $type) {
		return $this->helper->quoteColumnNames($column);
	}
}
