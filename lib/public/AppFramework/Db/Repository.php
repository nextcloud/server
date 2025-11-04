<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Db;

use Generator;
use OCP\AppFramework\Db\Attribute\Column;
use OCP\AppFramework\Db\Attribute\Id;
use OCP\AppFramework\Db\Attribute\Entity;
use OCP\AppFramework\Db\Attribute\Table;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Server;
use OCP\Snowflake\IGenerator;

/**
 * @template T
 */
class Repository {
	private string $tableName;

	/** @var array<string, string> */
	private array $_mappingColumnToTypes = [];

	/** @var array<string, string> */
	private array $_mappingColumnToProperty = [];

	/** @var array<string, string> */
	private array $_mappingPropertyToColumn = [];

	/** @var \ReflectionClass<T>  */
	private \ReflectionClass $reflection;

	private string $idProperty;

	public function __construct(
		protected readonly IDBConnection $connection,
		protected readonly string $entityClass,
	) {
		$this->reflection = new \ReflectionClass($this->entityClass);

		$entities = $this->reflection->getAttributes(Entity::class, \ReflectionAttribute::IS_INSTANCEOF);
		if (empty($entities)) {
			throw new \InvalidArgumentException("The given entity is missing a required #[Entity] attribute");
		}

		$tables = $this->reflection->getAttributes(Table::class, \ReflectionAttribute::IS_INSTANCEOF);
		if (empty($tables)) {
			throw new \InvalidArgumentException("The given entityClass is missing a required #[Table] attribute");
		}
		$this->tableName = $tables[0]->newInstance()->name;

		foreach ($this->reflection->getProperties() as $property) {
			$columnAttributes = $property->getAttributes(Column::class, \ReflectionAttribute::IS_INSTANCEOF);
			if (count($columnAttributes) === 0) {
				continue;
			}

			/** @var Column $columnAttribute */
			$columnAttribute = $columnAttributes[0]->newInstance();
			$this->_mappingColumnToTypes[$columnAttribute->name] = $columnAttribute->type;
			$this->_mappingColumnToProperty[$columnAttribute->name] = $property->getName();
			$this->_mappingPropertyToColumn[$property->getName()] = $columnAttribute->name;

			/** @var list<\ReflectionAttribute<Id>> $ids */
			$ids = $property->getAttributes(Id::class, \ReflectionAttribute::IS_INSTANCEOF);
			if (!empty($ids)) {
				$this->idProperty = $property->getName();
			}
		}
	}

	/**
	 * Runs a sql query and yields each resulting entity to obtain database entries in a memory-efficient way
	 *
	 * @param IQueryBuilder $query
	 * @return Generator Generator of fetched entities
	 * @psalm-return Generator<T> Generator of fetched entities
	 * @throws Exception
	 * @since 30.0.0
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
	 * @param IQueryBuilder $query
	 * @psalm-return list<T> all fetched entities
	 * @throws Exception
	 * @since 33.0.0
	 */
	public function findEntities(IQueryBuilder $query): array {
		$result = $query->executeQuery();
		try {
			$entities = [];
			while ($row = $result->fetch()) {
				$entities[] = $this->mapRowToEntity($row);
			}
			return $entities;
		} finally {
			$result->closeCursor();
		}
	}

	private function buildDebugMessage(string $msg, IQueryBuilder $sql): string {
		return $msg . ': query "' . $sql->getSQL() . '"; ';
	}

	/**
	 * @param array<string, mixed> $row
	 * @return T
	 */
	private function mapRowToEntity(mixed $row): object {
		$entity = new $this->entityClass();
		foreach ($row as $column => $value) {
			$property = $this->_mappingColumnToProperty[$column];
			$type = $this->_mappingColumnToTypes[$column];
			if ($type === Types::BLOB) {
				// (B)LOB is treated as string when we read from the DB
				if (is_resource($value)) {
					$value = stream_get_contents($value);
				}
				$type = Types::STRING;
			}

			if ($column === $this->idProperty) {
				$entity->$property = (string)$value;
				continue;
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
						$value = json_decode($value, true);
					}
					break;
			}
			$entity->$property = $value;
		}
		return $entity;
	}

	/**
	 * @psalm-param T $entity
	 * @return T
	 */
	public function insert(object $entity): object {
		$insert = $this->connection->getQueryBuilder();

		$values = [];
		foreach ($this->reflection->getProperties() as $property) {
			/** @var list<\ReflectionAttribute<Column>> $columns */
			$columns = $property->getAttributes(Column::class, \ReflectionAttribute::IS_INSTANCEOF);
			if (empty($columns)) {
				continue; // Not in the DB
			}

			$column = $columns[0]->newInstance();

			/** @var list<\ReflectionAttribute<Id>> $ids */
			$ids = $property->getAttributes(Id::class, \ReflectionAttribute::IS_INSTANCEOF);
			if (count($ids) > 0 && $property->getValue($entity) === null) {
				$generatorClass = $ids[0]->newInstance()->generatorClass;
				$generator = Server::get($generatorClass);
				if ($generator instanceof IGenerator) {
					$values[$column->name] = $generator->nextId();
					$property->setValue($entity, $insert->createNamedParameter($values[$column->name]));
				}
			} else {
				$type = $this->getParameterType($column->type);
				$values[$column->name] = $insert->createNamedParameter($property->getValue($entity), $type);
			}
		}

		$insert->insert($this->tableName)
			->values($values)
			->executeStatement();
		return $entity;
	}

	/**
	 * @psalm-param T $entity
	 * @return T
	 */
	public function update(object $entity): object {
		$update = $this->connection->getQueryBuilder();
		$update->update($this->tableName);

		foreach ($this->reflection->getProperties() as $property) {
			/** @var list<\ReflectionAttribute<Column>> $columns */
			$columns = $property->getAttributes(Column::class, \ReflectionAttribute::IS_INSTANCEOF);
			if (empty($columns)) {
				continue; // Not in the DB
			}

			$column = $columns[0]->newInstance();

			if (count($property->getAttributes(Id::class, \ReflectionAttribute::IS_INSTANCEOF)) !== 0) {
				if ($property->getValue($entity) === null) {
					throw new \LogicException("Trying to update an entity with no primary key set.");
				}

				$update->andWhere($update->expr()->eq($this->_mappingPropertyToColumn[$this->idProperty], $update->createNamedParameter($property->getValue($entity))));
				// don't update the id
				continue;
			};

			$type = $this->getParameterType($column->type);
			$update->set($column->name, $update->createNamedParameter($property->getValue($entity), $type));
		}


		$update->executeStatement();
		return $entity;
	}

	public function delete(object $entity): void {
		$delete = $this->connection->getQueryBuilder();
		$delete->delete($this->tableName);

		$foundId = false;
		foreach ($this->reflection->getProperties() as $property) {
			$columns = $property->getAttributes(Column::class, \ReflectionAttribute::IS_INSTANCEOF);
			if (empty($columns)) {
				continue; // Not in the DB
			}

			$column = $columns[0]->newInstance();

			if (count($property->getAttributes(Id::class, \ReflectionAttribute::IS_INSTANCEOF)) !== 0) {
				$delete->andWhere($delete->expr()->eq($column->name, $property->getValue($entity)));
				$foundId = true;
			};
		}

		if (!$foundId) {
			throw new \LogicException("The given entity is missing a required #[Id] attribute on one of its properties.");
		}

		$delete->executeStatement();
	}

	/**
	 * @param Types::* $type
	 * @return IQueryBuilder::PARAM_*
	 */
	private function getParameterType(string $type, bool $isArray): string|int {
		if ($isArray) {
			return match ($type) {
				Types::INTEGER, Types::SMALLINT => IQueryBuilder::PARAM_INT_ARRAY,
				Types::STRING => IQueryBuilder::PARAM_STR_ARRAY,
				Types::JSON => IQueryBuilder::PARAM_JSON,
				default => throw new \LogicException("Parameter type '$type' is not supported as an array."),
			};
		}

		return match ($type) {
			Types::INTEGER, Types::SMALLINT => IQueryBuilder::PARAM_INT,
			Types::STRING => IQueryBuilder::PARAM_STR,
			Types::BOOLEAN => IQueryBuilder::PARAM_BOOL,
			Types::BLOB => IQueryBuilder::PARAM_LOB,
			Types::DATE, Types::DATETIME => IQueryBuilder::PARAM_DATETIME_MUTABLE,
			Types::DATETIME_TZ => IQueryBuilder::PARAM_DATETIME_TZ_MUTABLE,
			Types::DATE_IMMUTABLE => IQueryBuilder::PARAM_DATE_IMMUTABLE,
			Types::DATETIME_IMMUTABLE => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
			Types::DATETIME_TZ_IMMUTABLE => IQueryBuilder::PARAM_DATETIME_TZ_IMMUTABLE,
			Types::TIME => IQueryBuilder::PARAM_TIME_MUTABLE,
			Types::TIME_IMMUTABLE => IQueryBuilder::PARAM_TIME_IMMUTABLE,
			Types::JSON => IQueryBuilder::PARAM_JSON,
			default => IQueryBuilder::PARAM_STR,
		};
	}

	/**
	 * Finds entities by a set of criteria.
	 *
	 * Use the property names for the criteria and orderBy key.
	 *
	 * @param array<string, int|float|string> $criteria
	 * @param array<string, 'asc'|'desc'>|null $orderBy
	 * @return \Generator<T>
	 * @since 33.0.0
	 */
	public function findBy(array $criteria, array $orderBy = [], int|null $limit = null, int|null $offset = null): \Generator {
		$qb = $this->getSelectQueryBuilder($criteria, $orderBy);

		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}

		if ($offset !== null) {
			$qb->setFirstResult($offset);
		}

		return $this->yieldEntities($qb);
	}

	public function deleteBy(array $criteria, int|null $limit = null): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete($this->tableName);

		foreach ($criteria as $property => $value) {
			$column = $this->_mappingPropertyToColumn[$property];
			$type = $this->getParameterType($this->_mappingColumnToTypes[$column]);
			$qb->andWhere($qb->expr()->eq($column, $qb->createNamedParameter($value, $type)));
		}

		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}

		$qb->executeStatement();
	}

	/**
	 * Finds a single entity by a set of criteria.
	 *
	 * @param array<string, int|float|string> $criteria
	 * @param array<string, 'asc'|'desc'>|null $orderBy
	 * @return T|null
	 * @since 33.0.0
	 */
	public function findOneBy(array $criteria, array $orderBy = []): object|null {
		$qb = $this->getSelectQueryBuilder($criteria, $orderBy);

		$qb->setMaxResults(1);

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			return null;
		}
	}

	private function getSelectQueryBuilder(array $criteria, array $orderBy = []): IQueryBuilder {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('*')
			->from($this->tableName);

		foreach ($criteria as $property => $value) {
			$column = $this->_mappingPropertyToColumn[$property];
			$type = $this->getParameterType($this->_mappingColumnToTypes[$column], is_array($value));
			$qb->andWhere($qb->expr()->eq($column, $qb->createNamedParameter($value, $type)));
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
	 * @param IQueryBuilder $query
	 * @psalm-return T the entity
	 * @throws Exception
	 * @throws MultipleObjectsReturnedException if more than one item exist
	 * @throws DoesNotExistException if the item does not exist
	 * @since 33.0.0
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

		$row;
		return $this->mapRowToEntity($row);
	}

	public function getTableName(): string {
		return $this->tableName;
	}
}
