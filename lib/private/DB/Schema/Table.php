<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\Schema;

use Doctrine\DBAL\Schema\Column as DBALColumn;
use Doctrine\DBAL\Schema\ForeignKeyConstraint as DBALForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index as DBALIndex;
use Doctrine\DBAL\Schema\SchemaException as DBALSchemaException;
use Doctrine\DBAL\Schema\Table as DBALTable;
use Doctrine\DBAL\Types\Type as DBALType;
use OCP\DB\Schema\ColumnType;
use OCP\DB\Schema\IColumn;
use OCP\DB\Schema\IForeignKeyConstraint;
use OCP\DB\Schema\IIndex;
use OCP\DB\Schema\ITable;
use OCP\DB\Schema\SchemaException;

/**
 * Object representation of a table, wrapping a Doctrine DBAL Table.
 */
class Table implements ITable {
	public function __construct(
		private DBALTable $table,
	) {
	}

	/**
	 * Returns the wrapped Doctrine DBAL table.
	 */
	public function getWrappedTable(): DBALTable {
		return $this->table;
	}

	#[\Override]
	public function getName(): string {
		/** @var non-empty-string $name */
		$name = $this->table->getName();
		return $name;
	}

	#[\Override]
	public function setPrimaryKey(array $columnNames, string|false $indexName = false): self {
		try {
			$this->table->setPrimaryKey($columnNames, $indexName);
		} catch (DBALSchemaException $e) {
			throw new SchemaException($e->getMessage(), $e->getCode(), $e);
		}

		return $this;
	}

	#[\Override]
	public function addIndex(array $columnNames, ?string $indexName = null, array $flags = [], array $options = []): self {
		try {
			$this->table->addIndex($columnNames, $indexName, $flags, $options);
		} catch (DBALSchemaException $e) {
			throw new SchemaException($e->getMessage(), $e->getCode(), $e);
		}

		return $this;
	}

	#[\Override]
	public function addUniqueConstraint(
		array $columnNames,
		?string $indexName = null,
		array $flags = [],
		array $options = [],
	): self {
		try {
			$this->table->addUniqueConstraint($columnNames, $indexName, $flags, $options);
		} catch (DBALSchemaException $e) {
			throw new SchemaException($e->getMessage(), $e->getCode(), $e);
		}

		return $this;
	}

	#[\Override]
	public function hasUniqueConstraint(string $name): bool {
		return $this->table->hasUniqueConstraint($name);
	}

	#[\Override]
	public function removeUniqueConstraint(string $name): void {
		try {
			$this->table->removeUniqueConstraint($name);
		} catch (DBALSchemaException $e) {
			throw new SchemaException($e->getMessage(), $e->getCode(), $e);
		}
	}

	#[\Override]
	public function dropPrimaryKey(): self {
		try {
			$this->table->dropPrimaryKey();
		} catch (DBALSchemaException $e) {
			throw new SchemaException($e->getMessage(), $e->getCode(), $e);
		}

		return $this;
	}

	#[\Override]
	public function getPrimaryKey(): ?IIndex {
		$primaryKey = $this->table->getPrimaryKey();

		return $primaryKey !== null ? new Index($primaryKey) : null;
	}

	#[\Override]
	public function hasPrimaryKey(): bool {
		return $this->table->hasPrimaryKey();
	}

	#[\Override]
	public function dropIndex(string $name): self {
		try {
			$this->table->dropIndex($name);
		} catch (DBALSchemaException $e) {
			throw new SchemaException($e->getMessage(), $e->getCode(), $e);
		}

		return $this;
	}

	#[\Override]
	public function hasIndex(string $name): bool {
		return $this->table->hasIndex($name);
	}

	#[\Override]
	public function addUniqueIndex(array $columnNames, ?string $indexName = null, array $options = []): self {
		try {
			$this->table->addUniqueIndex($columnNames, $indexName, $options);
		} catch (DBALSchemaException $e) {
			throw new SchemaException($e->getMessage(), $e->getCode(), $e);
		}

		return $this;
	}

	#[\Override]
	public function renameIndex(string $oldName, ?string $newName = null): self {
		try {
			$this->table->renameIndex($oldName, $newName);
		} catch (DBALSchemaException $e) {
			throw new SchemaException($e->getMessage(), $e->getCode(), $e);
		}

		return $this;
	}

	#[\Override]
	public function addColumn(string $name, string|ColumnType $typeName, array $options = []): IColumn {
		try {
			if ($typeName instanceof ColumnType) {
				$typeName = $typeName->value;
			}
			return new Column($this->table->addColumn($name, $typeName, $options));
		} catch (DBALSchemaException $e) {
			throw new SchemaException($e->getMessage(), $e->getCode(), $e);
		}
	}

	#[\Override]
	public function modifyColumn(string $name, array $options): self {
		try {
			if (isset($options['type'])) {
				if ($options['type'] instanceof ColumnType) {
					$options['type'] = $options['type']->value;
				}
				if (is_string($options['type'])) {
					$options['type'] = DBALType::getType($options['type']);
				}
			}
			$this->table->modifyColumn($name, $options);
		} catch (DBALSchemaException $e) {
			throw new SchemaException($e->getMessage(), $e->getCode(), $e);
		}

		return $this;
	}

	#[\Override]
	public function dropColumn(string $name): self {
		$this->table->dropColumn($name);

		return $this;
	}

	#[\Override]
	public function addForeignKeyConstraint(
		ITable|string $foreignTable,
		array $localColumnNames,
		array $foreignColumnNames,
		array $options = [],
		?string $name = null,
	): self {
		try {
			$this->table->addForeignKeyConstraint(
				$foreignTable instanceof self ? $foreignTable->getWrappedTable() : $foreignTable,
				$localColumnNames,
				$foreignColumnNames,
				$options,
				$name,
			);
		} catch (DBALSchemaException $e) {
			throw new SchemaException($e->getMessage(), $e->getCode(), $e);
		}

		return $this;
	}

	#[\Override]
	public function hasForeignKey(string $name): bool {
		return $this->table->hasForeignKey($name);
	}

	#[\Override]
	public function removeForeignKey(string $name): void {
		try {
			$this->table->removeForeignKey($name);
		} catch (DBALSchemaException $e) {
			throw new SchemaException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Forwards any method not declared on ITable to the wrapped Doctrine
	 * DBAL table, e.g. read-only accessors that are not part of the public API.
	 */
	public function __call(string $name, array $arguments): mixed {
		try {
			return $this->table->$name(...$arguments);
		} catch (DBALSchemaException $e) {
			throw new SchemaException($e->getMessage(), $e->getCode(), $e);
		}
	}

	#[\Override]
	public function hasColumn(string $name): bool {
		return $this->table->hasColumn($name);
	}

	#[\Override]
	public function getColumn(string $name): IColumn {
		try {
			return new Column($this->table->getColumn($name));
		} catch (DBALSchemaException $e) {
			throw new SchemaException($e->getMessage(), $e->getCode(), $e);
		}
	}

	#[\Override]
	public function getColumns(): array {
		return array_values(array_map(
			static fn (DBALColumn $column): IColumn => new Column($column),
			$this->table->getColumns(),
		));
	}

	#[\Override]
	public function getIndexes(): array {
		return array_values(array_map(
			static fn (DBALIndex $index): IIndex => new Index($index),
			$this->table->getIndexes(),
		));
	}

	#[\Override]
	public function getIndex(string $name): IIndex {
		return new Index($this->table->getIndex($name));
	}

	#[\Override]
	public function getForeignKeys(): array {
		return array_values(array_map(
			static fn (DBALForeignKeyConstraint $keyConstraint): IForeignKeyConstraint => new ForeignKeyConstraint($keyConstraint),
			$this->table->getForeignKeys(),
		));
	}
}
