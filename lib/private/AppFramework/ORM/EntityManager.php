<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\AppFramework\ORM;

use OC\DB\SchemaWrapper;
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
use OCP\Snowflake\ISnowflakeGenerator;

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
		if (!isset($this->entitiesInfo[$entityClass])) {
			$this->entitiesInfo[$entityClass] = new EntityInfo($entityClass);
		}

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
					if ($generatorClass === ISnowflakeGenerator::class) {
						$generator = Server::get($generatorClass);
						$values[$propertyAttributes->column->name] = $generator->nextId();
						$property->setValue($entity, $insert->createNamedParameter($values[$propertyAttributes->column->name]));
					}

					continue;
				}

				if ($isComposite) {
					// A composite primary key can't rely on a single autoincrement column: every
					// part must already be set on the entity (e.g. a foreign key id, or a value
					// assigned by the caller) before insert() is called.
					$value = $property->getValue($entity);
					if ($value === null) {
						throw new \LogicException($entity::class . '::' . $property->getName() . ' is part of a composite primary key and must be set before insert(); it cannot rely on DB autoincrement.');
					}

					if (!is_string($value) && !is_int($value)) {
						throw new \LogicException($entity::class . '::' . $property->getName() . ' is part of a composite primary key and must be set to a int or string before insert();.');
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
				$values[$propertyAttributes->column->name] = $insert->createNamedParameter($property->getValue($entity), $type);
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
				$update->set($propertyAttributes->column->name, $update->createNamedParameter($value, $type));
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
				/** @var int|string $value */
				$value = $property->getValue($entity);

				$delete->andWhere($delete->expr()->eq($propertyAttributes->column->name, $delete->createNamedParameter($value)));
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
			$options['default'] = $columnAttribute->default;
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
