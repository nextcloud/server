<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\AppFramework\ORM;

use OCP\AppFramework\ORM\IEntityExpressionBuilder;
use OCP\AppFramework\ORM\ISelectEntityQueryBuilder;
use OCP\DB\QueryBuilder\ICompositeExpression;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * @template T of object
 * @implements ISelectEntityQueryBuilder<T>
 */
final readonly class EntityQueryBuilder implements ISelectEntityQueryBuilder {
	private EntityExpressionBuilder $expressionBuilder;

	/**
	 * @param EntityInfo<T> $entityInfo
	 * @param array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo}> $relations
	 * @param class-string<T> $entityClass
	 */
	public function __construct(
		private IQueryBuilder $qb,
		private EntityManager $entityManager,
		private EntityInfo $entityInfo,
		private array $relations,
		private string $entityClass,
	) {
		$this->expressionBuilder = new EntityExpressionBuilder($this->qb, $this->entityManager, $this->entityInfo);
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
	public function orderBy(string $property, \SortDirection $order = \SortDirection::Ascending): static {
		$this->qb->orderBy('e.' . $this->resolveColumn($property), $order);
		return $this;
	}

	#[\Override]
	public function addOrderBy(string $property, \SortDirection $order = \SortDirection::Ascending): static {
		$this->qb->addOrderBy('e.' . $this->resolveColumn($property), $order);
		return $this;
	}

	#[\Override]
	public function setMaxResults(int $maxResults): static {
		$this->qb->setMaxResults($maxResults);
		return $this;
	}

	#[\Override]
	public function setFirstResult(int $firstResult): static {
		$this->qb->setFirstResult($firstResult);
		return $this;
	}

	#[\Override]
	public function toIterable(): \Generator {
		return $this->entityManager->yieldJoinedEntities($this->entityClass, $this->qb, $this->relations);
	}

	#[\Override]
	public function getArrayResult(): array {
		return iterator_to_array($this->toIterable(), false);
	}

	#[\Override]
	public function getSingleResult(): object {
		$this->qb->setMaxResults(2);

		return $this->entityManager->findJoinedEntity($this->entityClass, $this->qb, $this->relations);
	}

	#[\Override]
	public function getOneOrNullResult(): ?object {
		$this->qb->setMaxResults(2);

		return $this->entityManager->findJoinedEntityOrNull($this->entityClass, $this->qb, $this->relations);
	}

	private function resolveColumn(string $property): string {
		return $this->entityInfo->mappingPropertyToColumn[$property]
			?? throw new \InvalidArgumentException($this->entityClass . ' has no property named ' . $property);
	}
}
