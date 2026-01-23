<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace OC\DB\QueryBuilder\ExpressionBuilder;

use OC\DB\ConnectionAdapter;
use OC\DB\QueryBuilder\QueryFunction;
use OCP\DB\QueryBuilder\ILiteral;
use OCP\DB\QueryBuilder\IParameter;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;
use Override;
use Psr\Log\LoggerInterface;

class MySqlExpressionBuilder extends ExpressionBuilder {
	protected string $collation;

	public function __construct(ConnectionAdapter $connection, IQueryBuilder $queryBuilder, LoggerInterface $logger) {
		parent::__construct($connection, $queryBuilder, $logger);

		/** @psalm-suppress InternalMethod */
		$params = $connection->getInner()->getParams();
		/** @psalm-suppress InvalidArrayOffset collation is sometime defined */
		$this->collation = $params['collation'] ?? (($params['charset'] ?? 'utf8') . '_general_ci');
	}

	#[Override]
	public function iLike(string|IParameter|ILiteral|IQueryFunction $x, string|IParameter|ILiteral|IQueryFunction $y, int|string|null $type = null): string {
		$x = $this->helper->quoteColumnName($x);
		$y = $this->helper->quoteColumnName($y);
		return $this->expressionBuilder->comparison($x, ' COLLATE ' . $this->collation . ' LIKE', $y);
	}

	#[Override]
	public function castColumn(string|IParameter|ILiteral|IQueryFunction $column, int|string $type): IQueryFunction {
		return match ($type) {
			IQueryBuilder::PARAM_STR => new QueryFunction('CAST(' . $this->helper->quoteColumnName($column) . ' AS CHAR)'),
			IQueryBuilder::PARAM_JSON => new QueryFunction('CAST(' . $this->helper->quoteColumnName($column) . ' AS JSON)'),
			default => parent::castColumn($column, $type),
		};
	}
}
