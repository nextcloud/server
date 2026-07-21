<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\AppFramework\ORM;

use OC\AppFramework\ORM\EntityInfo;
use OC\AppFramework\ORM\EntityManager;
use OC\AppFramework\ORM\PropertyAttributes;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Server;

/**
 * @template T as object
 * @since 35.0.0
 */
class Repository {
	private readonly EntityManager $entityManager;

	/**
	 * @param class-string<T> $entityClass
	 * @throws \ReflectionException
	 */
	public function __construct(
		protected readonly IDBConnection $connection,
		protected readonly string $entityClass,
	) {
		$this->entityManager = Server::get(EntityManager::class);
	}

	private function buildDebugMessage(string $msg, IQueryBuilder $sql): string {
		return $msg . ': query "' . $sql->getSQL() . '"; ';
	}

	/**
	 * Builds an entity from a flat row of its own scalar columns. OneToOne relations are
	 * always left null here; resolving them is mapJoinedRowToEntity()'s job.
	 *
	 * @param class-string $entityClass
	 * @param array<string, mixed> $row
	 */
	private function hydrateRow(string $entityClass, mixed $row): object {
		$entityInfo = $this->entityManager->getEntityInfo($entityClass);

		$entity = new $entityClass();
		foreach ($row as $column => $value) {
			$property = $entityInfo->mappingColumnToProperty[$column];
			$type = $entityInfo->mappingColumnToTypes[$column];
			if ($type === Types::BLOB) {
				// (B)LOB is treated as string when we read from the DB
				if (is_resource($value)) {
					$value = stream_get_contents($value);
				}
				$type = Types::STRING;
			}

			if ($column === $entityInfo->idProperty->getName()) {
				/** @var list<\ReflectionAttribute<Id>> $ids */
				$ids = $entityInfo->idProperty->getAttributes(Id::class, \ReflectionAttribute::IS_INSTANCEOF);
				$id = array_shift($ids);
				if ($id->newInstance()->generatorClass !== null) {
					$entity->$property = (string)$value;
					continue;
				}
			}

			switch ($type) {
				case Types::BIGINT:
				case Types::SMALLINT:
					settype($value, Types::INTEGER);
					break;
				case Types::BINARY:
				case Types::DECIMAL:
				case Types::TEXT:
					settype($value, Types::STRING);
					break;
				case Types::TIME:
				case Types::DATE:
				case Types::DATETIME:
				case Types::DATETIME_TZ:
					if (!$value instanceof \DateTime) {
						$value = new \DateTime($value);
					}
					break;
				case Types::TIME_IMMUTABLE:
				case Types::DATE_IMMUTABLE:
				case Types::DATETIME_IMMUTABLE:
				case Types::DATETIME_TZ_IMMUTABLE:
					if (!$value instanceof \DateTimeImmutable) {
						$value = new \DateTimeImmutable($value);
					}
					break;
				case Types::JSON:
					if (!is_array($value)) {
						$value = json_decode((string)$value, true);
					}
					break;
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

	/**
	 * Builds a select query resolving OneToOne and ManyToOne relations via a LEFT JOIN.
	 * Columns are aliased `e_<column>` (main entity) and `r<index>_<column>` (each relation)
	 * to stay unique even when tables share column names.
	 *
	 * @return array{0: IQueryBuilder, 1: array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo}>}
	 */
	private function buildJoinedSelectQuery(EntityInfo $entityInfo): array {
		$qb = $this->connection->getQueryBuilder();
		$qb->from($entityInfo->tableName, 'e');

		foreach (array_keys($entityInfo->mappingColumnToProperty) as $column) {
			$qb->selectAlias('e.' . $column, 'e_' . $column);
		}

		/** @var array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo}> $relations */
		$relations = [];
		$index = 0;
		foreach ($entityInfo->propertiesAttributes as $propertyAttributes) {
			if (!$propertyAttributes->isRelation()) {
				continue;
			}

			$owningTargetClass = $propertyAttributes->getOwningRelationTarget();
			if ($owningTargetClass !== null) {
				// Owning side (OneToOne's invertedBy, or ManyToOne): the join column lives on our own table.
				$targetEntityInfo = $this->entityManager->getEntityInfo($owningTargetClass);
				$alias = 'r' . $index++;

				$this->joinRelation(
					$qb,
					$alias,
					$targetEntityInfo,
					'e.' . $propertyAttributes->joinColumn->name,
					$alias . '.' . $propertyAttributes->joinColumn->referencedColumnName,
				);

				$relations[$alias] = ['attributes' => $propertyAttributes, 'entityInfo' => $targetEntityInfo];
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

				if ($owningPropertyAttributes === null || $owningPropertyAttributes->joinColumn === null) {
					continue;
				}

				$alias = 'r' . $index++;
				$this->joinRelation(
					$qb,
					$alias,
					$targetEntityInfo,
					$alias . '.' . $owningPropertyAttributes->joinColumn->name,
					'e.' . $owningPropertyAttributes->joinColumn->referencedColumnName,
				);

				$relations[$alias] = ['attributes' => $propertyAttributes, 'entityInfo' => $targetEntityInfo];
			}
		}

		return [$qb, $relations];
	}

	private function joinRelation(IQueryBuilder $qb, string $alias, EntityInfo $targetEntityInfo, string $leftExpr, string $rightExpr): void {
		$qb->leftJoin('e', $targetEntityInfo->tableName, $alias, $qb->expr()->eq($leftExpr, $rightExpr));

		foreach (array_keys($targetEntityInfo->mappingColumnToProperty) as $column) {
			$qb->selectAlias($alias . '.' . $column, $alias . '_' . $column);
		}
	}

	/**
	 * @param array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo}> $relations
	 * @param array<string, mixed> $row
	 * @return T
	 */
	private function mapJoinedRowToEntity(array $relations, mixed $row): object {
		$mainRow = [];
		/** @var array<string, array<string, mixed>> $relationRows */
		$relationRows = [];
		foreach ($row as $key => $value) {
			if (str_starts_with($key, 'e_')) {
				$mainRow[substr($key, 2)] = $value;
				continue;
			}

			foreach ($relations as $alias => $relation) {
				$prefix = $alias . '_';
				if (str_starts_with($key, $prefix)) {
					$relationRows[$alias][substr($key, strlen($prefix))] = $value;
					continue 2;
				}
			}
		}

		$entity = $this->hydrateRow($this->entityClass, $mainRow);

		foreach ($relations as $alias => $relation) {
			$propertyName = $relation['attributes']->property->getName();
			$targetEntityInfo = $relation['entityInfo'];
			$idColumn = $targetEntityInfo->mappingPropertyToColumn[$targetEntityInfo->idProperty->getName()];
			$relationRow = $relationRows[$alias] ?? [];

			if (($relationRow[$idColumn] ?? null) === null) {
				$entity->$propertyName = null;
				continue;
			}

			$entity->$propertyName = $this->hydrateRow($targetEntityInfo->entityClass, $relationRow);
		}

		// Safety net for a malformed mapping that never made it into $relations.
		$entityInfo = $this->entityManager->getEntityInfo($this->entityClass);
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

		/** @var T */
		return $entity;
	}

	/**
	 * @param array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo}> $relations
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
	 * @param array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo}> $relations
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
	 */
	public function insert(object $entity): object {
		return $this->entityManager->insert($entity);
	}

	/**
	 * @psalm-param T $entity
	 * @return T
	 */
	public function update(object $entity): object {
		return $this->entityManager->update($entity);
	}

	/**
	 * @psalm-param T $entity
	 */
	public function delete(object $entity): void {
		$this->entityManager->delete($entity);
	}

	/**
	 * Finds entities by a set of criteria, keyed by property name.
	 *
	 * @param array<string, int|float|string|null|\DateTime|list<int|float|string>> $criteria
	 * @param array<string, 'ASC'|'DESC'> $orderBy
	 * @return \Generator<T>
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
	 * @param array<string, int|float|string|null|\DateTime|list<int|float|string>> $criteria
	 * @return int The number of rows deleted
	 * @throws Exception
	 */
	public function deleteBy(array $criteria, ?int $limit = null): int {
		$entityInfo = $this->entityManager->getEntityInfo($this->entityClass);

		$qb = $this->connection->getQueryBuilder();
		$qb->delete($entityInfo->tableName);

		foreach ($criteria as $property => $value) {
			$column = $entityInfo->mappingPropertyToColumn[$property];
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
	 * Finds a single entity by a set of criteria, keyed by property name.
	 *
	 * @param array<string, int|float|string|null|\DateTime|list<int|float|string>> $criteria
	 * @param array<string, 'ASC'|'DESC'> $orderBy
	 * @return T
	 * @throws DoesNotExistException
	 */
	public function findOneBy(array $criteria, array $orderBy = []): object {
		[$qb, $relations] = $this->getJoinedSelectQueryBuilder($criteria, $orderBy);

		$qb->setMaxResults(1);

		return $this->findJoinedEntity($qb, $relations);
	}

	/**
	 * @param array<string, int|float|string|null|\DateTime|list<int|float|string>> $criteria
	 * @param array<string, 'ASC'|'DESC'> $orderBy
	 * @return array{0: IQueryBuilder, 1: array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo}>}
	 */
	private function getJoinedSelectQueryBuilder(array $criteria, ?array $orderBy = []): array {
		$entityInfo = $this->entityManager->getEntityInfo($this->entityClass);
		[$qb, $relations] = $this->buildJoinedSelectQuery($entityInfo);

		foreach ($criteria as $property => $value) {
			$column = $entityInfo->mappingPropertyToColumn[$property];
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
			$qb->addOrderBy($qb->createNamedParameter($field), $direction);
		}

		return [$qb, $relations];
	}

	/**
	 * @return \Generator<T>
	 * @throws Exception
	 */
	public function yieldAll(): \Generator {
		$entityInfo = $this->entityManager->getEntityInfo($this->entityClass);
		[$qb, $relations] = $this->buildJoinedSelectQuery($entityInfo);

		return $this->yieldJoinedEntities($qb, $relations);
	}

	public function getTableName(): string {
		$entityInfo = $this->entityManager->getEntityInfo($this->entityClass);
		return $entityInfo->tableName;
	}
}
