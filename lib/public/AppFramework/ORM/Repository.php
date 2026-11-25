<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\AppFramework\ORM;

use OC\AppFramework\ORM\EntityInfo;
use OC\AppFramework\ORM\EntityManager;
use OC\AppFramework\ORM\PropertyAttributes;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Schema\ColumnType;
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
	 * @return class-string
	 */
	private function getEntityClass(): string {
		return $this->entityClassOverride ?? static::entityClass;
	}

	private function buildDebugMessage(string $msg, IQueryBuilder $sql): string {
		return $msg . ': query "' . $sql->getSQL() . '"; ';
	}

	/**
	 * Builds an entity from a flat row of its own scalar columns. OneToOne relations are
	 * always left null here; resolving them is mapJoinedRowToEntity()'s job.
	 *
	 * @template S of object
	 * @param class-string<S> $entityClass
	 * @param array<string, mixed> $row
	 * @return S
	 */
	private function hydrateRow(string $entityClass, mixed $row): object {
		$entityInfo = $this->entityManager->getEntityInfo($entityClass);

		/** @psalm-suppress MixedMethodCall Entities are a contract of this ORM: every mapped entity class has a public no-argument constructor. */
		$entity = new $entityClass();
		/** @psalm-suppress MixedAssignment $value is a raw, untyped DB driver value. */
		foreach ($row as $column => $value) {
			$property = $entityInfo->mappingColumnToProperty[$column];
			$type = $entityInfo->mappingColumnToTypes[$column];
			if ($type === ColumnType::Blob) {
				// (B)LOB is treated as string when we read from the DB
				if (is_resource($value)) {
					$value = stream_get_contents($value);
				}

				$type = ColumnType::String;
			}

			if ($this->isGeneratedIdColumn($entityInfo, $column)) {
				$entity->$property = (string)$value;
				continue;
			}

			if ($value === null) {
				$entity->$property = null;
				continue;
			}

			/** @psalm-suppress MixedAssignment $value is a raw DB driver value; each branch below settype()s or reconstructs it. */
			$value = match ($type) {
				ColumnType::Bigint, ColumnType::Smallint, ColumnType::Integer, ColumnType::TinyInt => (int)$value,
				ColumnType::Float => (float)$value,
				ColumnType::Boolean => (bool)$value,
				ColumnType::Binary, ColumnType::Decimal, ColumnType::Guid, ColumnType::Text, ColumnType::String => (string)$value,
				ColumnType::Time, ColumnType::Date, ColumnType::Datetime, ColumnType::DatetimeTz => $value instanceof \DateTime
					? $value
					: new \DateTime((string)$value),
				ColumnType::TimeImmutable, ColumnType::DateImmutable, ColumnType::DatetimeImmutable, ColumnType::DatetimeTzImmutable, ColumnType::TimestampImmutable => $value instanceof \DateTimeImmutable
					? $value
					: new \DateTimeImmutable((string)$value),
				ColumnType::Json => is_array($value) ? $value : json_decode((string)$value, true),
				ColumnType::Blob => $value,
			};

			$enumType = $entityInfo->mappingColumnToEnumType[$column] ?? null;

			if ($enumType !== null) {
				if (!is_string($value) && !is_int($value)) {
					throw new \LogicException('Can only convert int and string to enum');
				}

				$value = $enumType::from($value);
			}

			$entity->$property = $value;
		}

		foreach ($entityInfo->propertiesAttributes as $propertyAttributes) {
			if ($propertyAttributes->isRelation()) {
				$entity->{$propertyAttributes->property->getName()} = null;
			}
		}

		return $entity;
	}

	private function isGeneratedIdColumn(EntityInfo $entityInfo, string $column): bool {
		foreach ($entityInfo->propertiesAttributes as $propertyAttributes) {
			if ($propertyAttributes->id !== null && $propertyAttributes->column?->name === $column) {
				return $propertyAttributes->id->generatorClass !== null;
			}
		}

		return false;
	}

	/**
	 * Builds a select query resolving OneToOne and ManyToOne relations via a LEFT JOIN.
	 * Columns are aliased `e_<column>` (main entity) and `r<index>_<column>` (each relation)
	 * to stay unique even when tables share column names.
	 *
	 * @return array{0: IQueryBuilder, 1: array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo, leftExpr: string, rightExpr: string}>}
	 */
	private function buildJoinedSelectQuery(EntityInfo $entityInfo): array {
		$qb = $this->connection->getQueryBuilder();
		$qb->from($entityInfo->tableName, 'e');

		foreach (array_keys($entityInfo->mappingColumnToProperty) as $column) {
			$qb->selectAlias('e.' . $column, 'e_' . $column);
		}

		$relations = $this->getRelations($entityInfo);
		foreach ($relations as $alias => $relation) {
			$this->joinRelation($qb, $alias, $relation['entityInfo'], $relation['leftExpr'], $relation['rightExpr']);
		}

		return [$qb, $relations];
	}

	/**
	 * Resolves the relations joined by buildJoinedSelectQuery(), keyed by their table alias.
	 * The aliases only depend on the entity's mapping, so they are the same for every query.
	 *
	 * @return array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo, leftExpr: string, rightExpr: string}>
	 */
	private function getRelations(EntityInfo $entityInfo): array {
		/** @var array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo, leftExpr: string, rightExpr: string}> $relations */
		$relations = [];
		$index = 0;
		foreach ($entityInfo->propertiesAttributes as $propertyAttributes) {
			if (!$propertyAttributes->isRelation()) {
				continue;
			}

			$owningTargetClass = $propertyAttributes->getOwningRelationTarget();
			if ($owningTargetClass !== null) {
				$joinColumn = $propertyAttributes->joinColumn;
				if ($joinColumn === null) {
					throw new \LogicException('Unreachable: owning relation without a JoinColumn');
				}

				// Owning side (OneToOne's invertedBy, or ManyToOne): the join column lives on our own table.
				$targetEntityInfo = $this->entityManager->getEntityInfo($owningTargetClass);
				$alias = 'r' . $index++;
				$relations[$alias] = [
					'attributes' => $propertyAttributes,
					'entityInfo' => $targetEntityInfo,
					'leftExpr' => 'e.' . $joinColumn->name,
					'rightExpr' => $alias . '.' . $joinColumn->referencedColumnName,
				];
				continue;
			}

			if ($propertyAttributes->oneToOne !== null && $propertyAttributes->oneToOne->mappedBy !== null) {
				// Inverse side: the join column lives on the target's table, pointing back at us.
				$targetEntityInfo = $this->entityManager->getEntityInfo($propertyAttributes->oneToOne->targetEntity);

				$owningPropertyAttributes = null;
				foreach ($targetEntityInfo->propertiesAttributes as $candidate) {
					if ($candidate->property->getName() === $propertyAttributes->oneToOne->mappedBy) {
						$owningPropertyAttributes = $candidate;
						break;
					}
				}

				if ($owningPropertyAttributes === null) {
					continue;
				}

				if ($owningPropertyAttributes->joinColumn === null) {
					continue;
				}

				$alias = 'r' . $index++;
				$relations[$alias] = [
					'attributes' => $propertyAttributes,
					'entityInfo' => $targetEntityInfo,
					'leftExpr' => $alias . '.' . $owningPropertyAttributes->joinColumn->name,
					'rightExpr' => 'e.' . $owningPropertyAttributes->joinColumn->referencedColumnName,
				];
			}
		}

		return $relations;
	}

	private function joinRelation(IQueryBuilder $qb, string $alias, EntityInfo $targetEntityInfo, string $leftExpr, string $rightExpr): void {
		$qb->leftJoin('e', $targetEntityInfo->tableName, $alias, $qb->expr()->eq($leftExpr, $rightExpr));

		foreach (array_keys($targetEntityInfo->mappingColumnToProperty) as $column) {
			$qb->selectAlias($alias . '.' . $column, $alias . '_' . $column);
		}
	}

	/**
	 * @param array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo, leftExpr: string, rightExpr: string}> $relations
	 * @param array<string, mixed> $row
	 * @return T
	 */
	private function mapJoinedRowToEntity(array $relations, mixed $row): object {
		$mainRow = [];
		/** @var array<string, array<string, mixed>> $relationRows */
		$relationRows = [];
		/** @psalm-suppress MixedAssignment $value is a raw, untyped DB driver value. */
		foreach ($row as $key => $value) {
			if (str_starts_with($key, 'e_')) {
				$mainRow[substr($key, 2)] = $value;
				continue;
			}

			foreach (array_keys($relations) as $alias) {
				$prefix = $alias . '_';
				if (str_starts_with($key, $prefix)) {
					$relationRows[$alias][substr($key, strlen($prefix))] = $value;
					continue 2;
				}
			}
		}

		/** @var T $entity */
		$entity = $this->hydrateRow($this->getEntityClass(), $mainRow);

		foreach ($relations as $alias => $relation) {
			$propertyName = $relation['attributes']->property->getName();
			$targetEntityInfo = $relation['entityInfo'];
			$idColumn = $targetEntityInfo->mappingPropertyToColumn[$targetEntityInfo->getSingleIdProperty()->getName()];
			$relationRow = $relationRows[$alias] ?? [];

			if (($relationRow[$idColumn] ?? null) === null) {
				$entity->$propertyName = null;
				continue;
			}

			$entity->$propertyName = $this->hydrateRow($targetEntityInfo->entityClass, $relationRow);
		}

		// Safety net for a malformed mapping that never made it into $relations.
		$entityInfo = $this->entityManager->getEntityInfo($this->getEntityClass());
		foreach ($entityInfo->propertiesAttributes as $propertyAttributes) {
			if (!$propertyAttributes->isRelation()) {
				continue;
			}

			$alreadyResolved = false;
			foreach ($relations as $relation) {
				if ($relation['attributes'] === $propertyAttributes) {
					$alreadyResolved = true;
					break;
				}
			}

			if (!$alreadyResolved) {
				$entity->{$propertyAttributes->property->getName()} = null;
			}
		}

		return $entity;
	}

	/**
	 * @param array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo, leftExpr: string, rightExpr: string}> $relations
	 * @return \Generator<T>
	 */
	private function yieldJoinedEntities(IQueryBuilder $query, array $relations): \Generator {
		$result = $query->executeQuery();
		try {
			while ($row = $result->fetch()) {
				yield $this->mapJoinedRowToEntity($relations, $row);
			}
		} finally {
			$result->closeCursor();
		}
	}

	/**
	 * @param array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo, leftExpr: string, rightExpr: string}> $relations
	 * @return T
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 */
	private function findJoinedEntity(IQueryBuilder $query, array $relations): object {
		$result = $query->executeQuery();

		$row = $result->fetch();
		if ($row === false) {
			$result->closeCursor();
			throw new DoesNotExistException($this->buildDebugMessage(
				'Did expect one result but found none when executing', $query
			));
		}

		$row2 = $result->fetch();
		$result->closeCursor();
		if ($row2 !== false) {
			throw new MultipleObjectsReturnedException($this->buildDebugMessage(
				'Did not expect more than one result when executing', $query
			));
		}

		return $this->mapJoinedRowToEntity($relations, $row);
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
	 * The owning side of a relation (ManyToOne, or OneToOne with a JoinColumn) can be used as
	 * criterion as well, by passing the related entity or its primary key:
	 *
	 * ```php
	 * $orders = $orderRepository->findBy(['merchant' => $merchant]);
	 * ```
	 *
	 * @param array<string, int|float|string|null|\DateTime|\BackedEnum|object|list<int|float|string|\BackedEnum|object>> $criteria
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

		return $this->yieldJoinedEntities($qb, $relations);
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
	 * @param array<string, int|float|string|null|\DateTime|\BackedEnum|object|list<int|float|string|\BackedEnum|object>> $criteria
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

		return $this->yieldJoinedEntities($qb, $relations);
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
	 * @param array<string, int|float|string|null|\DateTime|\BackedEnum|object|list<int|float|string|\BackedEnum|object>> $criteria
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

		return $this->yieldJoinedEntities($qb, $relations);
	}

	/**
	 * @param array<string, int|float|string|null|\DateTime|\BackedEnum|object|list<int|float|string|\BackedEnum|object>> $criteria
	 * @return int The number of rows deleted
	 * @throws Exception
	 * @since 35.0.0
	 */
	public function deleteBy(array $criteria, ?int $limit = null): int {
		$entityInfo = $this->entityManager->getEntityInfo($this->getEntityClass());

		$qb = $this->connection->getQueryBuilder();
		$qb->delete($entityInfo->tableName);
		$this->addCriteria($qb, $entityInfo, $criteria, '');

		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}

		return $qb->executeStatement();
	}

	/**
	 * Finds a single entity by a set of criteria, keyed by property name.
	 *
	 * @param array<string, int|float|string|null|\DateTime|\BackedEnum|object|list<int|float|string|\BackedEnum|object>> $criteria
	 * @param array<string, \SortDirection> $orderBy
	 * @return T
	 * @throws DoesNotExistException
	 * @since 35.0.0
	 */
	public function findOneBy(array $criteria, array $orderBy = []): object {
		[$qb, $relations] = $this->getJoinedSelectQueryBuilder($criteria, $orderBy);

		$qb->setMaxResults(1);

		return $this->findJoinedEntity($qb, $relations);
	}

	/**
	 * @param array<string, int|float|string|null|\DateTime|\BackedEnum|object|list<int|float|string|\BackedEnum|object>> $criteria
	 * @param array<string, \SortDirection> $orderBy
	 * @return array{0: IQueryBuilder, 1: array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo, leftExpr: string, rightExpr: string}>}
	 */
	private function getJoinedSelectQueryBuilder(array $criteria, array $orderBy = []): array {
		$entityInfo = $this->entityManager->getEntityInfo($this->getEntityClass());
		[$qb, $relations] = $this->buildJoinedSelectQuery($entityInfo);
		$this->addCriteria($qb, $entityInfo, $criteria, 'e.');

		foreach ($orderBy as $field => $direction) {
			$column = $entityInfo->mappingPropertyToColumn[$field];
			$qb->addOrderBy('e.' . $column, $direction);
		}

		return [$qb, $relations];
	}

	/**
	 * Adds the criteria, keyed by property name, as conditions to the query.
	 *
	 * @param array<string, mixed> $criteria
	 * @param string $columnPrefix Prepended to every column, e.g. the table alias and a dot
	 * @throws \InvalidArgumentException if a property cannot be used as criterion
	 */
	private function addCriteria(IQueryBuilder $qb, EntityInfo $entityInfo, array $criteria, string $columnPrefix): void {
		/** @psalm-suppress MixedAssignment $value is caller-supplied criteria */
		foreach ($criteria as $property => $value) {
			/** @psalm-suppress MixedAssignment $value is normalized to a scalar, null or a list of scalars */
			[$column, $columnType, $value] = $this->resolveCriterion($entityInfo, $property, $value);
			$type = $this->entityManager->getParameterType($columnType, is_array($value));
			if ($value === null) {
				$qb->andWhere($qb->expr()->isNull($columnPrefix . $column));
			} elseif (is_array($value)) {
				// IN expression
				$qb->andWhere($qb->expr()->in($columnPrefix . $column, $qb->createNamedParameter($value, $type)));
			} else {
				// = expression
				$qb->andWhere($qb->expr()->eq($columnPrefix . $column, $qb->createNamedParameter($value, $type)));
			}
		}
	}

	/**
	 * Resolves a criterion to its column, the column type and the parameter value.
	 *
	 * Besides mapped columns, the owning side of a relation (ManyToOne, or OneToOne with a
	 * JoinColumn) is supported. It matches on the join column and accepts the related entity,
	 * its primary key, null or a list of related entities or primary keys.
	 *
	 * @return array{0: string, 1: ColumnType, 2: mixed}
	 * @throws \InvalidArgumentException if the property cannot be used as criterion
	 */
	private function resolveCriterion(EntityInfo $entityInfo, string $property, mixed $value): array {
		$column = $entityInfo->mappingPropertyToColumn[$property] ?? null;
		if ($column !== null) {
			return [$column, $entityInfo->mappingColumnToTypes[$column], $this->entityManager->toParameterValue($value)];
		}

		$propertyAttributes = $entityInfo->getPropertyAttributes($property);
		$targetEntityClass = $propertyAttributes?->getOwningRelationTarget();
		$joinColumn = $propertyAttributes?->joinColumn;
		if ($targetEntityClass === null || $joinColumn === null) {
			throw new \InvalidArgumentException($entityInfo->entityClass . '::' . $property . ' is neither a mapped column nor the owning side of a relation, so it cannot be used as criterion.');
		}

		$targetEntityInfo = $this->entityManager->getEntityInfo($targetEntityClass);
		$idProperty = $targetEntityInfo->getSingleIdProperty();
		$idColumn = $targetEntityInfo->mappingPropertyToColumn[$idProperty->getName()];

		$toId = static function (mixed $value) use ($targetEntityClass, $idProperty): mixed {
			if (!is_object($value)) {
				return $value;
			}

			if (!$value instanceof $targetEntityClass) {
				throw new \InvalidArgumentException('Expected an instance of ' . $targetEntityClass . ' or its primary key, got ' . $value::class . '.');
			}

			/** @psalm-suppress MixedAssignment the primary key is a scalar */
			$id = $idProperty->getValue($value);
			if ($id === null) {
				throw new \InvalidArgumentException('The given ' . $targetEntityClass . ' has no primary key, insert it before using it as criterion.');
			}

			return $id;
		};

		/** @psalm-suppress MixedAssignment */
		$value = is_array($value) ? array_map($toId, $value) : $toId($value);
		return [$joinColumn->name, $targetEntityInfo->mappingColumnToTypes[$idColumn], $value];
	}

	/**
	 * Creates a select query for this repository's entity, to run queries that the find
	 * methods cannot express, e.g. range conditions.
	 *
	 * The query already selects the entity and its relations, filtered by the criteria and
	 * sorted like findBy() does. The entity's table is aliased as `e`, so additional
	 * conditions have to reference its columns as `e.<column>`. Pass the query to
	 * findEntities() or findEntity() to get the hydrated entities.
	 *
	 * ```php
	 * $qb = $this->getSelectQueryBuilder(['merchant' => $merchant], ['createdAt' => \SortDirection::Ascending]);
	 * $qb->andWhere($qb->expr()->gte('e.created_at', $qb->createNamedParameter($since, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)));
	 * return iterator_to_array($this->findEntities($qb), false);
	 * ```
	 *
	 * @param array<string, int|float|string|null|\DateTime|\BackedEnum|object|list<int|float|string|\BackedEnum|object>> $criteria
	 * @param array<string, \SortDirection> $orderBy
	 * @throws \InvalidArgumentException if a property cannot be used as criterion
	 * @since 36.0.0
	 */
	protected function getSelectQueryBuilder(array $criteria = [], array $orderBy = []): IQueryBuilder {
		return $this->getJoinedSelectQueryBuilder($criteria, $orderBy)[0];
	}

	/**
	 * Runs a query created by getSelectQueryBuilder() and returns the hydrated entities.
	 *
	 * @return \Generator<T>
	 * @throws Exception
	 * @since 36.0.0
	 */
	protected function findEntities(IQueryBuilder $query): \Generator {
		$entityInfo = $this->entityManager->getEntityInfo($this->getEntityClass());
		return $this->yieldJoinedEntities($query, $this->getRelations($entityInfo));
	}

	/**
	 * Runs a query created by getSelectQueryBuilder() that has exactly one result and
	 * returns the hydrated entity.
	 *
	 * @return T
	 * @throws DoesNotExistException if the query has no result
	 * @throws MultipleObjectsReturnedException if the query has more than one result
	 * @throws Exception
	 * @since 36.0.0
	 */
	protected function findEntity(IQueryBuilder $query): object {
		$entityInfo = $this->entityManager->getEntityInfo($this->getEntityClass());
		return $this->findJoinedEntity($query, $this->getRelations($entityInfo));
	}

	/**
	 * @return \Generator<T>
	 * @throws Exception
	 * @since 35.0.0
	 */
	public function yieldAll(): \Generator {
		$entityInfo = $this->entityManager->getEntityInfo($this->getEntityClass());
		[$qb, $relations] = $this->buildJoinedSelectQuery($entityInfo);

		return $this->yieldJoinedEntities($qb, $relations);
	}

	/**
	 * @since 35.0.0
	 */
	public function getTableName(): string {
		$entityInfo = $this->entityManager->getEntityInfo($this->getEntityClass());
		return $entityInfo->tableName;
	}
}
