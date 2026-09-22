<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\AppFramework\ORM;

use OC\DB\SchemaWrapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\Id;
use OCP\AppFramework\ORM\Attribute\JoinColumn;
use OCP\AppFramework\ORM\Attribute\OneToOne;
use OCP\AppFramework\ORM\Repository;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Schema\ColumnType;
use OCP\DB\Schema\ITable;
use OCP\IDBConnection;
use OCP\Server;

final class EntityManager {
	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	/** @var array<class-string, EntityInfo<object>> $entitiesInfo */
	private array $entitiesInfo = [];

	/**
	 * @template T
	 * @param class-string<T> $entityClass
	 * @return EntityInfo<T>
	 */
	public function getEntityInfo(string $entityClass): EntityInfo {
		$this->entitiesInfo[$entityClass] ??= new EntityInfo($entityClass);

		/** @var EntityInfo<T> $entityInfo */
		$entityInfo = $this->entitiesInfo[$entityClass];
		return $entityInfo;
	}

	/**
	 * Generic, runtime-typed repository factory for callers that only have the entity class as
	 * a value (e.g. tests). Hand-written repositories (e.g. BackupCodeMapper) should instead
	 * extend Repository and override its `entityClass` constant, which also gets them proper
	 * static analysis of their entity type.
	 *
	 * @template T of object
	 * @param class-string<T> $entityClass
	 * @return Repository<T>
	 */
	public function getRepository(string $entityClass): Repository {
		/** @psalm-suppress InternalMethod both are private */
		return new Repository($this->connection, $this, $entityClass);
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
	public function hydrateRow(string $entityClass, mixed $row): object {
		$entityInfo = $this->getEntityInfo($entityClass);

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
				ColumnType::Bigint, ColumnType::Smallint, ColumnType::Integer => (int)$value,
				ColumnType::Float => (float)$value,
				ColumnType::Boolean => (bool)$value,
				ColumnType::Binary, ColumnType::Decimal, ColumnType::Guid, ColumnType::Text, ColumnType::String => (string)$value,
				ColumnType::Time, ColumnType::Date, ColumnType::Datetime, ColumnType::DatetimeTz => $value instanceof \DateTime
					? $value
					: new \DateTime((string)$value),
				ColumnType::TimeImmutable, ColumnType::DateImmutable, ColumnType::DatetimeImmutable, ColumnType::DatetimeTzImmutable => $value instanceof \DateTimeImmutable
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
	 * @return array{0: IQueryBuilder, 1: array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo}>}
	 */
	public function buildJoinedSelectQuery(EntityInfo $entityInfo): array {
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
				$joinColumn = $propertyAttributes->joinColumn;
				if ($joinColumn === null) {
					throw new \LogicException('Unreachable: owning relation without a JoinColumn');
				}

				// Owning side (OneToOne's invertedBy, or ManyToOne): the join column lives on our own table.
				$targetEntityInfo = $this->getEntityInfo($owningTargetClass);
				$alias = 'r' . $index++;

				$this->joinRelation(
					$qb,
					$alias,
					$targetEntityInfo,
					'e.' . $joinColumn->name,
					$alias . '.' . $joinColumn->referencedColumnName,
				);

				$relations[$alias] = ['attributes' => $propertyAttributes, 'entityInfo' => $targetEntityInfo];
				continue;
			}

			if ($propertyAttributes->oneToOne !== null && $propertyAttributes->oneToOne->mappedBy !== null) {
				// Inverse side: the join column lives on the target's table, pointing back at us.
				$targetEntityInfo = $this->getEntityInfo($propertyAttributes->oneToOne->targetEntity);

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
	 * @template S of object
	 * @param class-string<S> $entityClass
	 * @param array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo}> $relations
	 * @param array<string, mixed> $row
	 * @return S
	 */
	public function mapJoinedRowToEntity(string $entityClass, array $relations, mixed $row): object {
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

		$entity = $this->hydrateRow($entityClass, $mainRow);

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
		$entityInfo = $this->getEntityInfo($entityClass);
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
	 * @template S of object
	 * @param class-string<S> $entityClass
	 * @param array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo}> $relations
	 * @return \Generator<S>
	 */
	public function yieldJoinedEntities(string $entityClass, IQueryBuilder $query, array $relations): \Generator {
		$result = $query->executeQuery();
		try {
			while ($row = $result->fetch()) {
				yield $this->mapJoinedRowToEntity($entityClass, $relations, $row);
			}
		} finally {
			$result->closeCursor();
		}
	}

	/**
	 * @template S of object
	 * @param class-string<S> $entityClass
	 * @param array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo}> $relations
	 * @return S|null
	 * @throws MultipleObjectsReturnedException
	 */
	public function findJoinedEntityOrNull(string $entityClass, IQueryBuilder $query, array $relations): ?object {
		$result = $query->executeQuery();
		try {
			$row = $result->fetch();
			if ($row === false) {
				return null;
			}

			$row2 = $result->fetch();
			if ($row2 !== false) {
				throw new MultipleObjectsReturnedException($this->buildDebugMessage(
					'Did not expect more than one result when executing', $query
				));
			}

			return $this->mapJoinedRowToEntity($entityClass, $relations, $row);
		} finally {
			$result->closeCursor();
		}
	}

	/**
	 * @template S of object
	 * @param class-string<S> $entityClass
	 * @param array<string, array{attributes: PropertyAttributes, entityInfo: EntityInfo}> $relations
	 * @return S
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 */
	public function findJoinedEntity(string $entityClass, IQueryBuilder $query, array $relations): object {
		$entity = $this->findJoinedEntityOrNull($entityClass, $query, $relations);
		if ($entity === null) {
			throw new DoesNotExistException($this->buildDebugMessage(
				'Did expect one result but found none when executing', $query
			));
		}

		return $entity;
	}

	/**
	 * @template T of object
	 * @psalm-param T $entity
	 * @return T
	 * @throws Exception
	 */
	public function insert(object $entity): object {
		$entityInfo = $this->getEntityInfo($entity::class);
		$insert = $this->connection->getQueryBuilder();

		$isComposite = $entityInfo->hasCompositeIdProperty();
		$autoIncrementProperty = null;
		$values = [];

		foreach ($entityInfo->propertiesAttributes as $propertyAttributes) {
			$property = $propertyAttributes->property;
			if ($propertyAttributes->id !== null && $propertyAttributes->column !== null) {
				$generatorClass = $propertyAttributes->id->generatorClass;
				if ($generatorClass) {
					$generator = Server::get($generatorClass);
					$value = $generator->nextId();
					$type = $this->getParameterType($propertyAttributes->column->type, false);
					$values[$propertyAttributes->column->name] = $insert->createNamedParameter($value, $type);
					$property->setValue($entity, $value);
					continue;
				}

				if ($isComposite) {
					// A composite primary key can't rely on a single autoincrement column: every
					// part must already be set on the entity (e.g. a foreign key id, or a value
					// assigned by the caller) before insert() is called.
					/** @psalm-suppress MixedAssignment */
					$value = $property->getValue($entity);
					if ($value === null) {
						throw new \LogicException($entity::class . '::' . $property->getName() . ' is part of a composite primary key and must be set before insert(); it cannot rely on DB autoincrement.');
					}

					if (!is_string($value) && !is_int($value)) {
						throw new \LogicException($entity::class . '::' . $property->getName() . ' is part of a composite primary key and must be set to an int or string before insert().');
					}

					$type = $this->getParameterType($propertyAttributes->column->type, false);
					$values[$propertyAttributes->column->name] = $insert->createNamedParameter($value, $type);
					continue;
				}

				// Single autoincrement primary key: let the DB generate it, then read it back below.
				$autoIncrementProperty = $property;
				continue;
			}

			if ($propertyAttributes->isRelation() && $propertyAttributes->joinColumn !== null) {
				$targetEntityClass = $propertyAttributes->getOwningRelationTarget();
				if ($targetEntityClass === null) {
					if ($property->getValue($entity) !== null) {
						throw new \LogicException($entity::class . '::' . $property->getName() . ' is the mappedBy (inverse) side of a OneToOne relation and cannot be persisted directly; set it from the owning (invertedBy) side instead.');
					}

					continue;
				}

				$joinColumn = $propertyAttributes->joinColumn;
				/** @var object|null $targetEntity */
				$targetEntity = $property->getValue($entity);
				$targetEntityInfo = $this->getEntityInfo($targetEntityClass);
				if ($targetEntity === null) {
					$values[$joinColumn->name] = $insert->createNamedParameter(null);
				} else {
					$values[$joinColumn->name] = $insert->createNamedParameter($targetEntityInfo->getSingleIdProperty()->getValue($targetEntity));
				}

				continue;
			}

			if ($propertyAttributes->column !== null) {
				$type = $this->getParameterType($propertyAttributes->column->type, false);
				$values[$propertyAttributes->column->name] = $insert->createNamedParameter($this->toParameterValue($property->getValue($entity)), $type);
			}
		}

		$insert->insert($entityInfo->tableName)
			->values($values)
			->executeStatement();

		if ($autoIncrementProperty !== null) {
			$autoIncrementProperty->setValue($entity, $insert->getLastInsertId());
		}

		return $entity;
	}

	/**
	 * @template T of object
	 * @psalm-param T $entity
	 * @return T
	 */
	public function update(object $entity): object {
		$entityClass = $entity::class;
		$entityInfo = $this->getEntityInfo($entityClass);

		$update = $this->connection->getQueryBuilder();
		$update->update($entityInfo->tableName);

		foreach ($entityInfo->propertiesAttributes as $propertyAttributes) {
			$property = $propertyAttributes->property;
			/** @psalm-suppress MixedAssignment */
			$value = $property->getValue($entity);

			if ($propertyAttributes->id !== null && $propertyAttributes->column !== null) {
				if ($value === null) {
					throw new \LogicException('Trying to update an entity with no primary key set.');
				}

				$update->andWhere($update->expr()->eq($propertyAttributes->column->name, $update->createNamedParameter($value)));
				// don't update the id
				continue;
			}

			if ($propertyAttributes->isRelation() && $propertyAttributes->joinColumn !== null) {
				$targetEntityClass = $propertyAttributes->getOwningRelationTarget();
				if ($targetEntityClass === null) {
					continue;
				}

				$joinColumn = $propertyAttributes->joinColumn;
				/** @var object|null $targetEntity */
				$targetEntity = $value;
				$targetEntityInfo = $this->getEntityInfo($targetEntityClass);
				if ($targetEntity === null) {
					$update->set($joinColumn->name, $update->createNamedParameter(null));
				} else {
					$update->set($joinColumn->name, $update->createNamedParameter($targetEntityInfo->getSingleIdProperty()->getValue($targetEntity)));
				}

				continue;
			}

			if ($propertyAttributes->column !== null) {
				$type = $this->getParameterType($propertyAttributes->column->type, false);
				$update->set($propertyAttributes->column->name, $update->createNamedParameter($this->toParameterValue($value), $type));
			}
		}

		$update->executeStatement();
		return $entity;
	}

	/**
	 * @template T of object
	 * @psalm-param T $entity
	 */
	public function delete(object $entity): void {
		$entityClass = $entity::class;
		$entityInfo = $this->getEntityInfo($entityClass);

		$delete = $this->connection->getQueryBuilder();
		$delete->delete($entityInfo->tableName);

		$foundId = false;
		foreach ($entityInfo->propertiesAttributes as $propertyAttributes) {
			if ($propertyAttributes->id !== null && $propertyAttributes->column !== null) {
				$property = $propertyAttributes->property;
				/** @var int|string|null $value */
				$value = $property->getValue($entity);
				if ($value === null) {
					throw new \LogicException('Trying to delete an entity with no primary key set.');
				}

				$type = $this->getParameterType($propertyAttributes->column->type, false);
				$delete->andWhere($delete->expr()->eq($propertyAttributes->column->name, $delete->createNamedParameter($value, $type)));
				$foundId = true;
			};
		}

		if (!$foundId) {
			throw new \LogicException('The given entity is missing a required #[Id] attribute on one of its properties.');
		}

		try {
			$delete->executeStatement();
		} catch (Exception $exception) {
			if ($exception->getReason() === Exception::REASON_FOREIGN_KEY_VIOLATION) {
				throw new \LogicException($entityClass . " cannot be deleted: another entity still references it. Delete the related entity first, or set onDelete: 'CASCADE' on the owning JoinColumn.", 0, $exception);
			}

			throw $exception;
		}
	}

	/**
	 * @return IQueryBuilder::PARAM_*
	 */
	public function getParameterType(ColumnType $type, bool $isArray): string|int {
		if ($isArray) {
			/** @psalm-suppress DeprecatedConstant Types::JSON is only discouraged in WHERE clauses; mapping it is still supported. */
			return match ($type) {
				ColumnType::Integer, ColumnType::Smallint, ColumnType::Bigint => IQueryBuilder::PARAM_INT_ARRAY,
				ColumnType::String => IQueryBuilder::PARAM_STR_ARRAY,
				ColumnType::Json => IQueryBuilder::PARAM_JSON,
				default => throw new \LogicException(sprintf("Parameter type '%s' is not supported as an array.", $type->name)),
			};
		}

		/** @psalm-suppress DeprecatedConstant Types::JSON is only discouraged in WHERE clauses; mapping it is still supported. */
		return match ($type) {
			ColumnType::Integer, ColumnType::Smallint, ColumnType::Bigint => IQueryBuilder::PARAM_INT,
			ColumnType::Boolean => IQueryBuilder::PARAM_BOOL,
			ColumnType::Blob => IQueryBuilder::PARAM_LOB,
			ColumnType::Date, ColumnType::Datetime => IQueryBuilder::PARAM_DATETIME_MUTABLE,
			ColumnType::DatetimeTz => IQueryBuilder::PARAM_DATETIME_TZ_MUTABLE,
			ColumnType::DateImmutable => IQueryBuilder::PARAM_DATE_IMMUTABLE,
			ColumnType::DatetimeImmutable => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
			ColumnType::DatetimeTzImmutable => IQueryBuilder::PARAM_DATETIME_TZ_IMMUTABLE,
			ColumnType::Time => IQueryBuilder::PARAM_TIME_MUTABLE,
			ColumnType::TimeImmutable => IQueryBuilder::PARAM_TIME_IMMUTABLE,
			ColumnType::Json => IQueryBuilder::PARAM_JSON,
			default => IQueryBuilder::PARAM_STR,
		};
	}

	public function toParameterValue(mixed $value): mixed {
		if ($value instanceof \BackedEnum) {
			return $value->value;
		}

		if (is_array($value)) {
			return array_map($this->toParameterValue(...), $value);
		}

		return $value;
	}

	/**
	 * @internal Only for unit tests.
	 *
	 * @param class-string $entityClass
	 */
	public function createTable(string $entityClass, SchemaWrapper $schema): void {
		$entityInfo = $this->getEntityInfo($entityClass);

		$table = $schema->createTable($entityInfo->tableName);

		/** @var list<non-empty-lowercase-string> $idColumns */
		$idColumns = [];
		foreach ($entityInfo->propertiesAttributes as $propertyAttributes) {
			$this->createProperty($entityInfo, $propertyAttributes, $table);

			if ($propertyAttributes->id instanceof Id && $propertyAttributes->column instanceof Column) {
				$idColumns[] = $propertyAttributes->column->name;
			}

			$this->createRelationColumn($propertyAttributes, $table, $schema);
		}

		$table->setPrimaryKey($idColumns);
	}

	/**
	 * @param class-string $entityClass
	 */
	public function dropTable(string $entityClass, string $prefix): void {
		$entityInfo = $this->getEntityInfo($entityClass);
		$this->connection->dropTable($prefix . $entityInfo->tableName);
	}

	private function createProperty(EntityInfo $entityInfo, PropertyAttributes $attributes, ITable $table): void {
		if (!$attributes->column instanceof Column) {
			return;
		}

		$columnAttribute = $attributes->column;
		$options = [
			'notnull' => !$columnAttribute->nullable,
		];
		if ($columnAttribute->length !== null) {
			$options['length'] = $columnAttribute->length;
		}

		if ($columnAttribute->default !== null) {
			// Column::$default is documented as scalar|\BackedEnum, so unwrapping a \BackedEnum
			// case here always yields a scalar.
			/** @var scalar $default */
			$default = $this->toParameterValue($columnAttribute->default);
			$options['default'] = $default;
		}

		// A composite primary key can't rely on a single autoincrement column; see insert().
		if ($attributes->id instanceof Id && $attributes->id->generatorClass === null && !$entityInfo->hasCompositeIdProperty()) {
			$options['autoincrement'] = true;
		}

		$table->addColumn($columnAttribute->name, $columnAttribute->type, $options);
	}

	private function createRelationColumn(PropertyAttributes $attributes, ITable $table, SchemaWrapper $schema): void {
		$targetEntityClass = $attributes->getOwningRelationTarget();
		if (!$attributes->joinColumn instanceof JoinColumn || $targetEntityClass === null) {
			return;
		}

		$table->addColumn($attributes->joinColumn->name, ColumnType::Bigint, [
			'notnull' => !$attributes->joinColumn->nullable,
		]);

		if ($attributes->oneToOne instanceof OneToOne) {
			// Enforces the "one" in OneToOne; ManyToOne intentionally allows duplicates.
			$table->addUniqueIndex([$attributes->joinColumn->name]);
		}

		$foreignEntityInfo = $this->getEntityInfo($targetEntityClass);

		$options = [];
		if ($attributes->joinColumn->onDelete === 'CASCADE') {
			$options['onDelete'] = 'CASCADE';
		}

		$foreignTableName = $schema->getTable($foreignEntityInfo->tableName)->getName();
		$table->addForeignKeyConstraint($foreignTableName, [$attributes->joinColumn->name], [$attributes->joinColumn->referencedColumnName], $options);
	}
}
