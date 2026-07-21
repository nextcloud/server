<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\AppFramework\ORM;

use Doctrine\DBAL\Schema\Table;
use OC\DB\SchemaWrapper;
use OCP\AppFramework\ORM\Attribute\Column;
use OCP\AppFramework\ORM\Attribute\JoinColumn;
use OCP\AppFramework\ORM\Repository;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Server;
use OCP\Snowflake\ISnowflakeGenerator;

class EntityManager {
	public function __construct(
		readonly private IDBConnection $connection,
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
	 * @template T of object
	 * @param class-string<T> $entityClass
	 * @return Repository<T>
	 */
	public function getRepository(string $entityClass): Repository {
		return new Repository($this->connection, $entityClass);
	}

	/**
	 * @template T
	 * @psalm-param T $entity
	 * @return T
	 */
	public function insert(object $entity): object {
		$entityInfo = $this->getEntityInfo($entity::class);
		$insert = $this->connection->getQueryBuilder();

		$isSnowflake = false;
		$primaryProperty = null;
		$values = [];

		foreach ($entityInfo->propertiesAttributes as $propertyAttributes) {
			$property = $propertyAttributes->property;
			if ($propertyAttributes->id !== null && $propertyAttributes->column !== null) {
				$primaryProperty = $property;
				$generatorClass = $propertyAttributes->id->generatorClass;
				if ($generatorClass) {
					if ($generatorClass === ISnowflakeGenerator::class) {
						$generator = Server::get($generatorClass);
						$isSnowflake = true;
						/** @psalm-suppress UndefinedClass */
						$values[$propertyAttributes->column->name] = $generator->nextId();
						$property->setValue($entity, $insert->createNamedParameter($values[$propertyAttributes->column->name]));
					}
				}
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
				/** @var object $object */
				$targetEntity = $property->getValue($entity);
				$targetEntityInfo = $this->getEntityInfo($targetEntityClass);
				if ($targetEntity === null) {
					$values[$joinColumn->name] = $insert->createNamedParameter(null);
				} else {
					$values[$joinColumn->name] = $insert->createNamedParameter($targetEntityInfo->idProperty->getValue($targetEntity));
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

		if (!$isSnowflake) {
			$primaryProperty->setValue($entity, $insert->getLastInsertId());
		}
		return $entity;
	}

	/**
	 * @template T
	 * @psalm-param T $entity
	 * @return T
	 */
	public function update(object $entity): object {
		$entityClass = get_class($entity);
		$entityInfo = $this->getEntityInfo($entityClass);

		$update = $this->connection->getQueryBuilder();
		$update->update($entityInfo->tableName);

		foreach ($entityInfo->propertiesAttributes as $propertyAttributes) {
			$property = $propertyAttributes->property;
			$value = $property->getValue($entity);

			if ($propertyAttributes->id !== null && $propertyAttributes->column !== null) {
				if ($value === null) {
					throw new \LogicException('Trying to update an entity with no primary key set.');
				}

				$update->andWhere($update->expr()->eq($entityInfo->mappingPropertyToColumn[$entityInfo->idProperty->getName()], $update->createNamedParameter($property->getValue($entity))));
				// don't update the id
				continue;
			}

			if ($propertyAttributes->isRelation() && $propertyAttributes->joinColumn !== null) {
				$targetEntityClass = $propertyAttributes->getOwningRelationTarget();
				if ($targetEntityClass === null) {
					continue;
				}

				/** @var JoinColumn $joinColumn */
				$joinColumn = $propertyAttributes->joinColumn;
				/** @var object $object */
				$targetEntity = $value;
				$targetEntityInfo = $this->getEntityInfo($targetEntityClass);
				if ($targetEntity === null) {
					$update->set($joinColumn->name, $update->createNamedParameter(null));
				} else {
					$update->set($joinColumn->name, $update->createNamedParameter($targetEntityInfo->idProperty->getValue($targetEntity)));
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
	 * @template T
	 * @psalm-param T $entity
	 */
	public function delete(object $entity): void {
		$entityClass = get_class($entity);
		$entityInfo = $this->getEntityInfo($entityClass);

		$delete = $this->connection->getQueryBuilder();
		$delete->delete($entityInfo->tableName);

		$foundId = false;
		foreach ($entityInfo->propertiesAttributes as $propertyAttributes) {
			if ($propertyAttributes->id !== null && $propertyAttributes->column !== null) {
				$property = $propertyAttributes->property;
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
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_FOREIGN_KEY_VIOLATION) {
				throw new \LogicException($entityClass . ' cannot be deleted: another entity still references it. Delete the related entity first, or set onDelete: \'CASCADE\' on the owning JoinColumn.', 0, $e);
			}
			throw $e;
		}
	}

	/**
	 * @param Types::* $type
	 * @return IQueryBuilder::PARAM_*
	 */
	public function getParameterType(string $type, bool $isArray): string|int {
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
	 * @internal Only for unit tests.
	 *
	 * @param class-string $entityClass
	 */
	public function createTable(string $entityClass, SchemaWrapper $schema): void {
		$entityInfo = $this->getEntityInfo($entityClass);

		$table = $schema->createTable($entityInfo->tableName);

		foreach ($entityInfo->propertiesAttributes as $propertyAttributes) {
			$this->createProperty($propertyAttributes, $table);

			$this->createRelationColumn($propertyAttributes, $table, $schema);
		}

		$schema->getWrappedSchema()->toSql($this->connection->getDatabasePlatform());
	}

	public function dropTable(string $entityClass, string $prefix): void {
		$entityInfo = $this->getEntityInfo($entityClass);
		$this->connection->dropTable($prefix . $entityInfo->tableName);
	}

	private function createProperty(PropertyAttributes $attributes, Table $table): void {
		if ($attributes->column === null) {
			return;
		}
		/** @var Column $columnAttribute */
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

		if ($attributes->id !== null && $attributes->id->generatorClass === null) {
			$options['autoincrement'] = true;
		}

		$table->addColumn($columnAttribute->name, $columnAttribute->type, $options);

		if ($attributes->id !== null) {
			$table->setPrimaryKey([$columnAttribute->name]);
		}
	}

	private function createRelationColumn(PropertyAttributes $attributes, Table $table, SchemaWrapper $schema): void {
		$targetEntityClass = $attributes->getOwningRelationTarget();
		if ($attributes->joinColumn === null || $targetEntityClass === null) {
			return;
		}

		$table->addColumn($attributes->joinColumn->name, Types::BIGINT, [
			'notnull' => !$attributes->joinColumn->nullable,
		]);

		if ($attributes->oneToOne !== null) {
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
