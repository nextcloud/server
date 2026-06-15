<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\ORM;

use Generator;
use OC\AppFramework\ORM\EntityManager;
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

	/**
	 * Runs a sql query and yields each resulting entity to obtain database entries in a memory-efficient way
	 *
	 * @return Generator Generator of fetched entities
	 * @psalm-return Generator<T> Generator of fetched entities
	 * @throws Exception
	 */
	public function yieldEntities(IQueryBuilder $query): Generator {
		$result = $query->executeQuery();
		try {
			while ($row = $result->fetch()) {
				yield $this->mapRowToEntity($row);
			}
		} finally {
			$result->closeCursor();
		}
	}

	/**
	 * Runs a sql query and returns an array of entities
	 *
	 * @psalm-return list<T> all fetched entities
	 * @throws Exception
	 */
	public function findEntities(IQueryBuilder $query): array {
		return iterator_to_array($this->yieldEntities($query));
	}

	private function buildDebugMessage(string $msg, IQueryBuilder $sql): string {
		return $msg . ': query "' . $sql->getSQL() . '"; ';
	}

	/**
	 * @param array<string, mixed> $row
	 * @return T
	 */
	private function mapRowToEntity(mixed $row): object {
		$entityInfo = $this->entityManager->getEntityInfo($this->entityClass);

		$entity = new $this->entityClass();
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
		return $entity;
	}

	/**
	 * Insert the entity in the database.
	 *
	 * This will additionally generate a value for the primary key.
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
	 * Finds entities by a set of criteria.
	 *
	 * Use the property names for the criteria and orderBy key.
	 *
	 * @param array<string, int|float|string|null|\DateTime|list<int|float|string>> $criteria
	 * @param array<string, 'asc'|'desc'>|null $orderBy
	 * @return \Generator<T>
	 */
	public function findBy(array $criteria, array $orderBy = [], ?int $limit = null, ?int $offset = null): \Generator {
		$qb = $this->getSelectQueryBuilder($criteria, $orderBy);

		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}

		if ($offset !== null) {
			$qb->setFirstResult($offset);
		}

		return $this->yieldEntities($qb);
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
	 * Finds a single entity by a set of criteria.
	 *
	 * @param array<string, int|float|string|null|\DateTime|list<int|float|string>> $criteria
	 * @param array<string, 'asc'|'desc'>|null $orderBy
	 * @return T
	 * @throws DoesNotExistException
	 */
	public function findOneBy(array $criteria, array $orderBy = []): object {
		$qb = $this->getSelectQueryBuilder($criteria, $orderBy);

		$qb->setMaxResults(1);

		return $this->findEntity($qb);
	}

	/**
	 * @param array<string, int|float|string|null|\DateTime|list<int|float|string>> $criteria
	 * @param array<string, 'asc'|'desc'>|null $orderBy
	 */
	private function getSelectQueryBuilder(array $criteria, array $orderBy = []): IQueryBuilder {
		$entityInfo = $this->entityManager->getEntityInfo($this->entityClass);

		$qb = $this->connection->getQueryBuilder();
		$qb->select('*')
			->from($entityInfo->tableName);

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
		foreach ($orderBy as $field => $direction) {
			$qb->addOrderBy($qb->createNamedParameter($field), $direction);
		}

		return $qb;
	}

	/**
	 * Returns a db result and throws exceptions when there are more or less
	 * results
	 *
	 * @psalm-return T the entity
	 * @throws Exception
	 * @throws MultipleObjectsReturnedException if more than one item exist
	 * @throws DoesNotExistException if the item does not exist
	 */
	protected function findEntity(IQueryBuilder $query): object {
		$result = $query->executeQuery();

		$row = $result->fetch();
		if ($row === false) {
			$result->closeCursor();
			$msg = $this->buildDebugMessage(
				'Did expect one result but found none when executing', $query
			);
			throw new DoesNotExistException($msg);
		}

		$row2 = $result->fetch();
		$result->closeCursor();
		if ($row2 !== false) {
			$msg = $this->buildDebugMessage(
				'Did not expect more than one result when executing', $query
			);
			throw new MultipleObjectsReturnedException($msg);
		}

		return $this->mapRowToEntity($row);
	}

	/**
	 * @return Generator<T>
	 * @throws Exception
	 */
	public function yieldAll(): \Generator {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName());

		return $this->yieldEntities($qb);
	}

	public function getTableName(): string {
		$entityInfo = $this->entityManager->getEntityInfo($this->entityClass);
		return $entityInfo->tableName;
	}
}
