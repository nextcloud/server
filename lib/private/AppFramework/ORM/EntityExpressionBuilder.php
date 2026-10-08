<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\AppFramework\ORM;

use OCP\AppFramework\ORM\IEntityExpressionBuilder;
use OCP\DB\QueryBuilder\ICompositeExpression;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;

final readonly class EntityExpressionBuilder implements IEntityExpressionBuilder {
	private IExpressionBuilder $expr;

	/**
	 * @param EntityInfo<object> $entityInfo
	 * @param string $columnPrefix Prepended to every resolved column, e.g. the `e.` table
	 *                             alias used for joined selects; pass '' for unaliased
	 *                             queries such as a bulk delete.
	 */
	public function __construct(
		private IQueryBuilder $qb,
		private EntityManager $entityManager,
		private EntityInfo $entityInfo,
		private string $columnPrefix = 'e.',
	) {
		$this->expr = $this->qb->expr();
	}

	#[\Override]
	public function andX(string|ICompositeExpression ...$x): ICompositeExpression {
		return $this->expr->andX(...$x);
	}

	#[\Override]
	public function orX(string|ICompositeExpression ...$x): ICompositeExpression {
		return $this->expr->orX(...$x);
	}

	#[\Override]
	public function eq(string $property, mixed $value): string {
		return $this->expr->eq($this->column($property), $this->parameter($property, $value));
	}

	#[\Override]
	public function neq(string $property, mixed $value): string {
		return $this->expr->neq($this->column($property), $this->parameter($property, $value));
	}

	#[\Override]
	public function lt(string $property, mixed $value): string {
		return $this->expr->lt($this->column($property), $this->parameter($property, $value));
	}

	#[\Override]
	public function lte(string $property, mixed $value): string {
		return $this->expr->lte($this->column($property), $this->parameter($property, $value));
	}

	#[\Override]
	public function gt(string $property, mixed $value): string {
		return $this->expr->gt($this->column($property), $this->parameter($property, $value));
	}

	#[\Override]
	public function gte(string $property, mixed $value): string {
		return $this->expr->gte($this->column($property), $this->parameter($property, $value));
	}

	#[\Override]
	public function like(string $property, string $pattern): string {
		return $this->expr->like($this->column($property), $this->parameter($property, $pattern));
	}

	#[\Override]
	public function notLike(string $property, string $pattern): string {
		return $this->expr->notLike($this->column($property), $this->parameter($property, $pattern));
	}

	#[\Override]
	public function in(string $property, array $values): string {
		return $this->expr->in($this->column($property), $this->parameter($property, $values, true));
	}

	#[\Override]
	public function notIn(string $property, array $values): string {
		return $this->expr->notIn($this->column($property), $this->parameter($property, $values, true));
	}

	#[\Override]
	public function isNull(string $property): string {
		return $this->expr->isNull($this->column($property));
	}

	#[\Override]
	public function isNotNull(string $property): string {
		return $this->expr->isNotNull($this->column($property));
	}

	private function column(string $property): string {
		return $this->columnPrefix . $this->resolveColumn($property);
	}

	private function parameter(string $property, mixed $value, bool $isArray = false): string {
		$column = $this->resolveColumn($property);
		/** @psalm-suppress MixedAssignment $value is caller-supplied, unwrapped of any \BackedEnum case. */
		$value = $this->entityManager->toParameterValue($value);
		$type = $this->entityManager->getParameterType($this->entityInfo->mappingColumnToTypes[$column], $isArray);

		return (string)$this->qb->createNamedParameter($value, $type);
	}

	private function resolveColumn(string $property): string {
		return $this->entityInfo->mappingPropertyToColumn[$property]
			?? throw new \InvalidArgumentException($this->entityInfo->entityClass . ' has no property named ' . $property);
	}
}
