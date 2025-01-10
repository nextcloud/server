<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Db;

use Generator;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;

/**
 * Simple parent class for inheriting your data access layer from. This class
 * may be subject to change in the future
 *
 * @since 14.0.0
 *
 * @template T of Entity
 */
abstract class QBMapper {
	/** @var string */
	protected $tableName;

	/** @var string|class-string<T> */
	protected $entityClass;

	/** @var IDBConnection */
	protected $db;

	/**
	 * @param IDBConnection $db Instance of the Db abstraction layer
	 * @param string $tableName the name of the table. set this to allow entity
	 * @param class-string<T>|null $entityClass the name of the entity that the sql should be
	 *                                          mapped to queries without using sql
	 * @since 14.0.0
	 */
	public function __construct(IDBConnection $db, string $tableName, ?string $entityClass = null) {
		$this->db = $db;
		$this->tableName = $tableName;

		// if not given set the entity name to the class without the mapper part
		// cache it here for later use since reflection is slow
		if ($entityClass === null) {
			$this->entityClass = str_replace('Mapper', '', \get_class($this));
		} else {
			$this->entityClass = $entityClass;
		}
	}


	/**
	 * @return string the table name
	 * @since 14.0.0
	 */
	public function getTableName(): string {
		return $this->tableName;
	}


	/**
	 * Deletes an entity from the table
	 *
	 * @param Entity $entity the entity that should be deleted
	 * @psalm-param T $entity the entity that should be deleted
	 * @return Entity the deleted entity
	 * @psalm-return T the deleted entity
	 * @throws Exception
	 * @since 14.0.0
	 */
	public function delete(Entity $entity): Entity {
		$qb = $this->db->getQueryBuilder();

		$idType = $this->getParameterTypeForProperty($entity, 'id');

		$qb->delete($this->tableName)
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($entity->getId(), $idType))
			);
		$qb->executeStatement();
		return $entity;
	}


	/**
	 * Creates a new entry in the db from an entity
	 *
	 * @param Entity $entity the entity that should be created
	 * @psalm-param T $entity the entity that should be created
	 * @return Entity the saved entity with the set id
	 * @psalm-return T the saved entity with the set id
	 * @throws Exception
	 * @since 14.0.0
	 */
	public function insert(Entity $entity): Entity {
		// get updated fields to save, fields have to be set using a setter to
		// be saved
		$properties = $entity->getUpdatedFields();

		$qb = $this->db->getQueryBuilder();
		$qb->insert($this->tableName);

		// build the fields
		foreach ($properties as $property => $updated) {
			$column = $entity->propertyToColumn($property);
			$getter = 'get' . ucfirst($property);
			$value = $entity->$getter();

			$type = $this->getParameterTypeForProperty($entity, $property);
			$qb->setValue($column, $qb->createNamedParameter($value, $type));
		}

		$qb->executeStatement();

		if ($entity->id === null) {
			// When autoincrement is used id is always an int
			$entity->setId($qb->getLastInsertId());
		}

		return $entity;
	}

	/**
	 * Tries to creates a new entry in the db from an entity and
	 * updates an existing entry if duplicate keys are detected
	 * by the database
	 *
	 * @param Entity $entity the entity that should be created/updated
	 * @psalm-param T $entity the entity that should be created/updated
	 * @return Entity the saved entity with the (new) id
	 * @psalm-return T the saved entity with the (new) id
	 * @throws Exception
	 * @throws \InvalidArgumentException if entity has no id
	 * @since 15.0.0
	 */
	public function insertOrUpdate(Entity $entity): Entity {
		try {
			return $this->insert($entity);
		} catch (Exception $ex) {
			if ($ex->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				return $this->update($entity);
			}
			throw $ex;
		}
	}

	/**
	 * Updates an entry in the db from an entity
	 *
	 * @param Entity $entity the entity that should be created
	 * @psalm-param T $entity the entity that should be created
	 * @return Entity the saved entity with the set id
	 * @psalm-return T the saved entity with the set id
	 * @throws Exception
	 * @throws \InvalidArgumentException if entity has no id
	 * @since 14.0.0
	 */
	public function update(Entity $entity): Entity {
		// if entity wasn't changed it makes no sense to run a db query
		$properties = $entity->getUpdatedFields();
		if (\count($properties) === 0) {
			return $entity;
		}

		// entity needs an id
		$id = $entity->getId();
		if ($id === null) {
			throw new \InvalidArgumentException(
				'Entity which should be updated has no id');
		}

		// get updated fields to save, fields have to be set using a setter to
		// be saved
		// do not update the id field
		unset($properties['id']);

		$qb = $this->db->getQueryBuilder();
		$qb->update($this->tableName);

		// build the fields
		foreach ($properties as $property => $updated) {
			$column = $entity->propertyToColumn($property);
			$getter = 'get' . ucfirst($property);
			$value = $entity->$getter();

			$type = $this->getParameterTypeForProperty($entity, $property);
			$qb->set($column, $qb->createNamedParameter($value, $type));
		}

		$idType = $this->getParameterTypeForProperty($entity, 'id');

		$qb->where(
			$qb->expr()->eq('id', $qb->createNamedParameter($id, $idType))
		);
		$qb->executeStatement();

		return $entity;
	}

	/**
	 * Returns the type parameter for the QueryBuilder for a specific property
	 * of the $entity
	 *
	 * @param Entity $entity The entity to get the types from
	 * @psalm-param T $entity
	 * @param string $property The property of $entity to get the type for
	 * @return int|string
	 * @since 16.0.0
	 */
	protected function getParameterTypeForProperty(Entity $entity, string $property) {
		$types = $entity->getFieldTypes();

		if (!isset($types[ $property ])) {
			return IQueryBuilder::PARAM_STR;
		}

		switch ($types[ $property ]) {
			case 'int':
			case Types::INTEGER:
			case Types::SMALLINT:
				return IQueryBuilder::PARAM_INT;
			case Types::STRING:
				return IQueryBuilder::PARAM_STR;
			case 'bool':
			case Types::BOOLEAN:
				return IQueryBuilder::PARAM_BOOL;
			case Types::BLOB:
				return IQueryBuilder::PARAM_LOB;
			case Types::DATE:
				return IQueryBuilder::PARAM_DATETIME_MUTABLE;
			case Types::DATETIME:
				return IQueryBuilder::PARAM_DATETIME_MUTABLE;
			case Types::DATETIME_TZ:
				return IQueryBuilder::PARAM_DATETIME_TZ_MUTABLE;
			case Types::DATE_IMMUTABLE:
				return IQueryBuilder::PARAM_DATE_IMMUTABLE;
			case Types::DATETIME_IMMUTABLE:
				return IQueryBuilder::PARAM_DATETIME_IMMUTABLE;
			case Types::DATETIME_TZ_IMMUTABLE:
				return IQueryBuilder::PARAM_DATETIME_TZ_IMMUTABLE;
			case Types::TIME:
				return IQueryBuilder::PARAM_TIME_MUTABLE;
			case Types::TIME_IMMUTABLE:
				return IQueryBuilder::PARAM_TIME_IMMUTABLE;
			case Types::JSON:
				return IQueryBuilder::PARAM_JSON;
		}

		return IQueryBuilder::PARAM_STR;
	}

	/**
	 * Returns an db result and throws exceptions when there are more or less
	 * results
	 *
	 * @param IQueryBuilder $query
	 * @return array the result as row
	 * @throws Exception
	 * @throws MultipleObjectsReturnedException if more than one item exist
	 * @throws DoesNotExistException if the item does not exist
	 * @see findEntity
	 *
	 * @since 14.0.0
	 */
	protected function findOneQuery(IQueryBuilder $query): array {
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

		return $row;
	}

	/**
	 * @param string $msg
	 * @param IQueryBuilder $sql
	 * @return string
	 * @since 14.0.0
	 */
	private function buildDebugMessage(string $msg, IQueryBuilder $sql): string {
		return $msg .
			': query "' . $sql->getSQL() . '"; ';
	}


	/**
	 * Creates an entity from a row. Automatically determines the entity class
	 * from the current mapper name (MyEntityMapper -> MyEntity)
	 *
	 * @param array $row the row which should be converted to an entity
	 * @return Entity the entity
	 * @psalm-return T the entity
	 * @since 14.0.0
	 */
	protected function mapRowToEntity(array $row): Entity {
		unset($row['DOCTRINE_ROWNUM']); // remove doctrine/dbal helper column
		return \call_user_func($this->entityClass . '::fromRow', $row);
	}


	/**
	 * Runs a sql query and returns an array of entities
	 *
	 * @param IQueryBuilder $query
	 * @return list<Entity> all fetched entities
	 * @psalm-return list<T> all fetched entities
	 * @throws Exception
	 * @since 14.0.0
	 */
	protected function findEntities(IQueryBuilder $query): array {
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

	/**
	 * Runs a sql query and yields each resulting entity to obtain database entries in a memory-efficient way
	 *
	 * @param IQueryBuilder $query
	 * @return Generator Generator of fetched entities
	 * @psalm-return Generator<T> Generator of fetched entities
	 * @throws Exception
	 * @since 30.0.0
	 */
	protected function yieldEntities(IQueryBuilder $query): Generator {
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
	 * Returns an db result and throws exceptions when there are more or less
	 * results
	 *
	 * @param IQueryBuilder $query
	 * @return Entity the entity
	 * @psalm-return T the entity
	 * @throws Exception
	 * @throws MultipleObjectsReturnedException if more than one item exist
	 * @throws DoesNotExistException if the item does not exist
	 * @since 14.0.0
	 */
	protected function findEntity(IQueryBuilder $query): Entity {
		return $this->mapRowToEntity($this->findOneQuery($query));
	}
}
