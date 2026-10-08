<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\AppFramework\ORM;

use OC\AppFramework\ORM\EntityDeleteStatementBuilder;
use OC\AppFramework\ORM\EntityInfo;
use OC\AppFramework\ORM\EntityManager;
use OC\AppFramework\ORM\EntityQueryBuilder;
use OC\AppFramework\ORM\PropertyAttributes;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template T as object
 * @since 35.0.0
 */
class Repository {
	/**
	 * The class this repository holds.
	 *
	 * @var class-string
	 * @since 35.0.0
	 * @psalm-suppress InvalidConstantAssignmentValue
	 */
	public const string entityClass = '';

	/**
	 * @param class-string<T>|null $entityClassOverride Only meant for generic, runtime-typed
	 *                                                  repositories (e.g. EntityManager::getRepository()).
	 * @throws \ReflectionException
	 * @internal
	 * @since 35.0.0
	 */
	public function __construct(
		protected readonly IDBConnection $connection,
		protected readonly EntityManager $entityManager,
		private readonly ?string $entityClassOverride = null,
	) {
	}

	/**
	 * @return class-string<T>
	 */
	private function getEntityClass(): string {
		/** @var class-string<T> $entityClass */
		$entityClass = $this->entityClassOverride ?? static::entityClass;
		return $entityClass;
	}

	/**
	 * Inserts the entity and populates its generated primary key.
	 *
	 * @psalm-param T $entity
	 * @return T
	 * @throws Exception
	 * @since 35.0.0
	 */
	public function insert(object $entity): object {
		return $this->entityManager->insert($entity);
	}

	/**
	 * @psalm-param T $entity
	 * @return T
	 * @since 35.0.0
	 */
	public function update(object $entity): object {
		return $this->entityManager->update($entity);
	}

	/**
	 * @psalm-param T $entity
	 * @since 35.0.0
	 */
	public function delete(object $entity): void {
		$this->entityManager->delete($entity);
	}

	/**
	 * Tries to create a new entry in the db from an entity and
	 * updates an existing entry if duplicate keys are detected
	 * by the database
	 *
	 * @param T $entity the entity that should be created/updated
	 * @return T the saved entity with the (new) id
	 * @throws Exception
	 * @throws \InvalidArgumentException if entity has no id
	 * @since 15.0.0
	 */
	public function insertOrUpdate(object $entity): object {
		try {
			return $this->insert($entity);
		} catch (Exception $exception) {
			if ($exception->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				return $this->update($entity);
			}

			throw $exception;
		}
	}

	/**
	 * Finds entities by a set of criteria, keyed by property name.
	 *
	 * @param array<string, int|float|string|null|\DateTime|\BackedEnum|list<int|float|string|\BackedEnum>> $criteria
	 * @param array<string, \SortDirection> $orderBy
	 * @return \Generator<T>
	 *
	 * @note If you need to implement pagination, prefer using findByAfterId instead.
	 *
	 * @since 35.0.0
	 */
	public function findBy(array $criteria, array $orderBy = [], ?int $limit = null, ?int $offset = null): \Generator {
		[$qb, $relations] = $this->getJoinedSelectQueryBuilder($criteria, $orderBy);

		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}

		if ($offset !== null) {
			$qb->setFirstResult($offset);
		}

		return $this->entityManager->yieldJoinedEntities($this->getEntityClass(), $qb, $relations);
	}

	/**
	 * Finds entities by a set of criteria, keyed by property name, one page at a time ordered by
	 * their primary key — using keyset (seek) pagination instead of OFFSET/LIMIT.
	 *
	 * Unlike findBy()'s $offset, which forces the database to scan and discard every preceding
	 * row on every call, $lastId lets it seek straight to the right spot through the primary
	 * key's index, so each page costs the same regardless of how deep it is. Pass null to fetch
	 * the first page, then the id of the last entity returned to fetch the next one; stop once
	 * fewer than $limit entities come back.
	 *
	 * @warning This does not support tables with composite primary keys
	 *
	 * @param array<string, int|float|string|null|\DateTime|\BackedEnum|list<int|float|string|\BackedEnum>> $criteria
	 * @param int|string|null $lastId The primary key of the last entity from the previous
	 *                                page, or null to fetch the first page.
	 * @return \Generator<T>
	 * @throws \LogicException if the entity has a composite primary key
	 * @since 35.0.0
	 */
	public function findByAfterId(array $criteria, int|float|string|null $lastId, int $limit): \Generator {
		$entityInfo = $this->entityManager->getEntityInfo($this->getEntityClass());
		$idColumn = $entityInfo->mappingPropertyToColumn[$entityInfo->getSingleIdProperty()->getName()];

		[$qb, $relations] = $this->getJoinedSelectQueryBuilder($criteria);

		if ($lastId !== null) {
			$type = $this->entityManager->getParameterType($entityInfo->mappingColumnToTypes[$idColumn], false);
			$qb->andWhere($qb->expr()->gt('e.' . $idColumn, $qb->createNamedParameter($lastId, $type)));
		}

		$qb->orderBy('e.' . $idColumn, \SortDirection::Ascending);
		$qb->setMaxResults($limit);

		return $this->entityManager->yieldJoinedEntities($this->getEntityClass(), $qb, $relations);
	}

	/**
	 * Finds entities by a set of criteria, keyed by property name, one page at a time ordered by
	 * their primary key in descending order — using keyset (seek) pagination instead of
	 * OFFSET/LIMIT.
	 *
	 * This is the mirror image of findByAfterId(), walking from the highest id downwards instead
	 * of from the lowest id upwards. Pass null to fetch the first page (starting from the highest
	 * id), then the id of the last entity returned to fetch the next one; stop once fewer than
	 * $limit entities come back.
	 *
	 * @warning This does not support tables with composite primary keys
	 *
	 * @param array<string, int|float|string|null|\DateTime|\BackedEnum|list<int|float|string|\BackedEnum>> $criteria
	 * @param int|string|null $lastId The primary key of the last entity from the previous
	 *                                page, or null to fetch the first page.
	 * @return \Generator<T>
	 * @throws \LogicException if the entity has a composite primary key
	 * @since 35.0.0
	 */
	public function findByBeforeId(array $criteria, int|float|string|null $lastId, int $limit): \Generator {
		$entityInfo = $this->entityManager->getEntityInfo($this->getEntityClass());
		$idColumn = $entityInfo->mappingPropertyToColumn[$entityInfo->getSingleIdProperty()->getName()];

		[$qb, $relations] = $this->getJoinedSelectQueryBuilder($criteria);

		if ($lastId !== null) {
			$type = $this->entityManager->getParameterType($entityInfo->mappingColumnToTypes[$idColumn], false);
			$qb->andWhere($qb->expr()->lt('e.' . $idColumn, $qb->createNamedParameter($lastId, $type)));
		}

		$qb->orderBy('e.' . $idColumn, \SortDirection::Descending);
		$qb->setMaxResults($limit);

		return $this->entityManager->yieldJoinedEntities($this->getEntityClass(), $qb, $relations);
	}

	/**
	 * @param array<string, int|float|string|null|\DateTime|\BackedEnum|list<int|float|string|\BackedEnum>> $criteria
	 * @return int The number of rows deleted
	 * @throws Exception
	 * @since 35.0.0
	 */
	public function deleteBy(array $criteria, ?int $limit = null): int {
		$entityInfo = $this->entityManager->getEntityInfo($this->getEntityClass());

		$qb = $this->connection->getQueryBuilder();
		$qb->delete($entityInfo->tableName);

		foreach ($criteria as $property => $value) {
			$column = $entityInfo->mappingPropertyToColumn[$property];
			/** @psalm-suppress MixedAssignment can be anything */
			$value = $this->entityManager->toParameterValue($value);
			$type = $this->entityManager->getParameterType($entityInfo->mappingColumnToTypes[$column], is_array($value));
			if ($value === null) {
				$qb->andWhere($qb->expr()->isNull($column));
			} elseif (is_array($value)) {
				// IN expression
				$qb->andWhere($qb->expr()->in($column, $qb->createNamedParameter($value, $type)));
			} else {
				// = expression
				$qb->andWhere($qb->expr()->eq($column, $qb->createNamedParameter($value, $type)));
			}
		}

		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}

		return $qb->executeStatement();
	}

	/**
	 * A alternative to deleteBy() for conditions beyond equality, IN and IS NULL.
	 *
	 * @since 36.0.0
	 */
	protected function getDeleteStatementBuilder(): IEntityDeleteStatementBuilder {
		$entityInfo = $this->entityManager->getEntityInfo($this->getEntityClass());

		$qb = $this->connection->getQueryBuilder();
		$qb->delete($entityInfo->tableName);

		return new EntityDeleteStatementBuilder($qb, $this->entityManager, $entityInfo);
	}

	/**
	 * Finds a single entity by a set of criteria, keyed by property name.
	 *
	 * @param array<string, int|float|string|null|\DateTime|\BackedEnum|list<int|float|string|\BackedEnum>> $criteria
	 * @param array<string, \SortDirection> $orderBy
	 * @return T
	 * @throws DoesNotExistException
	 * @since 35.0.0
	 */
	public function findOneBy(array $criteria, array $orderBy = []): object {
		[$qb, $relations] = $this->getJoinedSelectQueryBuilder($criteria, $orderBy);

		$qb->setMaxResults(1);

		return $this->entityManager->findJoinedEntity($this->getEntityClass(), $qb, $relations);
	}

	/**
	 * A alternative to findBy()/findOneBy() for conditions beyond equality, IN and IS NULL.
	 *
	 * @return ISelectEntityQueryBuilder<T>
	 * @since 36.0.0
	 */
	protected function getSelectQueryBuilder(): ISelectEntityQueryBuilder {
		$entityInfo = $this->entityManager->getEntityInfo($this->getEntityClass());
		[$qb, $relations] = $this->entityManager->buildJoinedSelectQuery($entityInfo);

		return new EntityQueryBuilder($qb, $this->entityManager, $entityInfo, $relations, $this->getEntityClass());
	}

	/**
	 * @param array<string, int|float|string|null|\DateTime|\BackedEnum|list<int|float|string|\BackedEnum>> $criteria
	 * @param array<string, \SortDirection> $orderBy
	 * @return array{0: IQueryBuilder, 1: array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo}>}
	 */
	private function getJoinedSelectQueryBuilder(array $criteria, array $orderBy = []): array {
		$entityInfo = $this->entityManager->getEntityInfo($this->getEntityClass());
		[$qb, $relations] = $this->entityManager->buildJoinedSelectQuery($entityInfo);

		foreach ($criteria as $property => $value) {
			$column = $entityInfo->mappingPropertyToColumn[$property];
			/** @psalm-suppress MixedAssignment $value is caller-supplied criteria, unwrapped of any \BackedEnum case. */
			$value = $this->entityManager->toParameterValue($value);
			$type = $this->entityManager->getParameterType($entityInfo->mappingColumnToTypes[$column], is_array($value));
			if ($value === null) {
				$qb->andWhere($qb->expr()->isNull('e.' . $column));
			} elseif (is_array($value)) {
				// IN expression
				$qb->andWhere($qb->expr()->in('e.' . $column, $qb->createNamedParameter($value, $type)));
			} else {
				// = expression
				$qb->andWhere($qb->expr()->eq('e.' . $column, $qb->createNamedParameter($value, $type)));
			}
		}

		foreach ($orderBy as $field => $direction) {
			$column = $entityInfo->mappingPropertyToColumn[$field];
			$qb->addOrderBy('e.' . $column, $direction);
		}

		return [$qb, $relations];
	}

	/**
	 * @return \Generator<T>
	 * @throws Exception
	 * @since 35.0.0
	 */
	public function yieldAll(): \Generator {
		$entityInfo = $this->entityManager->getEntityInfo($this->getEntityClass());
		[$qb, $relations] = $this->entityManager->buildJoinedSelectQuery($entityInfo);

		return $this->entityManager->yieldJoinedEntities($this->getEntityClass(), $qb, $relations);
	}

	/**
	 * @since 35.0.0
	 */
	public function getTableName(): string {
		$entityInfo = $this->entityManager->getEntityInfo($this->getEntityClass());
		return $entityInfo->tableName;
	}
}
