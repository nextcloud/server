<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\AppFramework\ORM;

use OCP\AppFramework\ORM\IEntityDeleteStatementBuilder;
use OCP\AppFramework\ORM\IEntityExpressionBuilder;
use OCP\DB\QueryBuilder\ICompositeExpression;
use OCP\DB\QueryBuilder\IQueryBuilder;

final readonly class EntityDeleteStatementBuilder implements IEntityDeleteStatementBuilder {
	private EntityExpressionBuilder $expressionBuilder;

	public function __construct(
		private IQueryBuilder $qb,
		EntityManager $entityManager,
		EntityInfo $entityInfo,
	) {
		// Bulk deletes aren't run against a table alias (see Repository::deleteBy()), so
		// columns are referenced unprefixed here, unlike the `e.` used for joined selects.
		$this->expressionBuilder = new EntityExpressionBuilder($this->qb, $entityManager, $entityInfo, '');
	}

	#[\Override]
	public function expr(): IEntityExpressionBuilder {
		return $this->expressionBuilder;
	}

	#[\Override]
	public function where(string|ICompositeExpression ...$predicates): static {
		$this->qb->where(...$predicates);
		return $this;
	}

	#[\Override]
	public function andWhere(string|ICompositeExpression ...$predicates): static {
		$this->qb->andWhere(...$predicates);
		return $this;
	}

	#[\Override]
	public function orWhere(string|ICompositeExpression ...$predicates): static {
		$this->qb->orWhere(...$predicates);
		return $this;
	}

	#[\Override]
	public function setMaxResults(int $maxResults): static {
		$this->qb->setMaxResults($maxResults);
		return $this;
	}

	#[\Override]
	public function executeStatement(): int {
		return $this->qb->executeStatement();
	}
}
