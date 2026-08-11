<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\DB\Schema;

use OCP\AppFramework\Attribute\Consumable;
use OCP\DB\Types;

/**
 * Object representation of a table.
 *
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface ITable {
	/**
	 * Returns the name of this table.
	 *
	 * @since 35.0.0
	 * @return non-empty-lowercase-string
	 */
	public function getName(): string;

	/**
	 * Sets the Primary Key.
	 *
	 * @param list<non-empty-string> $columnNames
	 * @param string|false $indexName
	 *
	 * @throws SchemaException
	 * @since 35.0.0
	 */
	public function setPrimaryKey(array $columnNames, string|false $indexName = false): self;

	/**
	 * @param list<non-empty-lowercase-string> $columnNames
	 * @param list<non-empty-lowercase-string> $flags
	 * @param array<non-empty-lowercase-string, mixed> $options
	 *
	 * @throws SchemaException
	 * @since 35.0.0
	 */
	public function addIndex(array $columnNames, ?string $indexName = null, array $flags = [], array $options = []): self;

	/**
	 * @param list<string> $columnNames
	 * @param list<string> $flags
	 * @param array<string, mixed> $options
	 * @since 35.0.0
	 */
	public function addUniqueConstraint(
		array $columnNames,
		?string $indexName = null,
		array $flags = [],
		array $options = [],
	): self;

	/**
	 * Drops the primary key from this table.
	 *
	 * @throws SchemaException
	 * @since 35.0.0
	 */
	public function dropPrimaryKey(): self;

	/**
	 * Returns the primary key, or null if this table has no primary key.
	 *
	 * @since 35.0.0
	 */
	public function getPrimaryKey(): ?IIndex;

	/**
	 * Drops an index from this table.
	 *
	 * @param non-empty-lowercase-string $name The index name.
	 *
	 * @throws SchemaException If the index does not exist.
	 * @since 35.0.0
	 */
	public function dropIndex(string $name): self;

	/**
	 * Returns whether this table has an index with the given name.
	 *
	 * @param non-empty-lowercase-string $name The index name.
	 * @since 35.0.0
	 */
	public function hasIndex(string $name): bool;

	/**
	 * @param list<string> $columnNames
	 * @param string|null $indexName
	 * @param array<string, mixed> $options
	 *
	 * @throws SchemaException
	 * @since 35.0.0
	 */
	public function addUniqueIndex(array $columnNames, ?string $indexName = null, array $options = []): self;

	/**
	 * Renames an index.
	 *
	 * @param non-empty-lowercase-string $oldName The name of the index to rename from.
	 * @param non-empty-lowercase-string|null $newName The name of the index to rename to.
	 *                                                 If null is given, the index name will be auto-generated.
	 *
	 * @return self This table instance.
	 *
	 * @throws SchemaException If no index exists for the given current name
	 *                         or if an index with the given new name already exists on this table.
	 * @since 35.0.0
	 */
	public function renameIndex(string $oldName, ?string $newName = null): self;

	/**
	 * @param non-empty-lowercase-string $name
	 * @param Types::*|ColumnType $typeName
	 * @param array{
	 *     notnull?: bool,
	 *     length?: ?int,
	 *     default?: ?scalar,
	 *     unsigned?: bool,
	 *     autoincrement?: bool,
	 *     fixed?: bool,
	 *     precision?: int,
	 *     scale?: int,
	 *     type?: Types::*|ColumnType,
	 * } $options
	 *
	 * @throws SchemaException
	 * @since 35.0.0
	 */
	public function addColumn(string $name, string|ColumnType $typeName, array $options = []): IColumn;

	/**
	 * @param non-empty-lowercase-string $name
	 * @param array{
	 *     notnull?: bool,
	 *     length?: ?int,
	 *     default?: ?scalar,
	 *     unsigned?: bool,
	 *     autoincrement?: bool,
	 *     fixed?: bool,
	 *     precision?: int,
	 *     scale?: int,
	 *     type?: Types::*|ColumnType,
	 * } $options
	 *
	 * @throws SchemaException
	 * @since 35.0.0
	 */
	public function modifyColumn(string $name, array $options): self;

	/**
	 * Drops a Column from the Table.
	 *
	 * @param non-empty-lowercase-string $name
	 * @since 35.0.0
	 */
	public function dropColumn(string $name): self;

	/**
	 * Returns whether this table has a Column with the given name.
	 *
	 * @param non-empty-lowercase-string $name The column name.
	 * @since 35.0.0
	 */
	public function hasColumn(string $name): bool;

	/**
	 * Returns the Column with the given name.
	 *
	 * @param non-empty-lowercase-string $name The column name.
	 *
	 * @throws SchemaException If the column does not exist.
	 * @since 35.0.0
	 */
	public function getColumn(string $name): IColumn;

	/**
	 * Returns all columns of this table.
	 *
	 * @return list<IColumn>
	 * @since 35.0.0
	 */
	public function getColumns(): array;

	/**
	 * Returns all indexes of this table.
	 *
	 * @return list<IIndex>
	 * @since 35.0.0
	 */
	public function getIndexes(): array;

	/**
	 * Adds a foreign key constraint.
	 *
	 * Name is inferred from the local columns.
	 *
	 * @param ITable|non-empty-lowercase-string $foreignTable Table schema instance or table name
	 * @param list<non-empty-lowercase-string> $localColumnNames
	 * @param list<non-empty-lowercase-string> $foreignColumnNames
	 * @param array<string, mixed> $options
	 *
	 * @throws SchemaException
	 * @since 35.0.0
	 */
	public function addForeignKeyConstraint(
		ITable|string $foreignTable,
		array $localColumnNames,
		array $foreignColumnNames,
		array $options = [],
		?string $name = null,
	): self;

	/**
	 * Returns whether this table has a foreign key constraint with the given name.
	 *
	 * @param non-empty-string $name The foreign key name.
	 * @since 35.0.0
	 */
	public function hasForeignKey(string $name): bool;

	/**
	 * Removes the foreign key constraint with the given name.
	 *
	 * @param non-empty-string $name The constraint name.
	 *
	 * @throws SchemaException
	 * @since 35.0.0
	 */
	public function removeForeignKey(string $name): void;

	/**
	 * @since 35.0.0
	 * @return list<IForeignKeyConstraint>
	 */
	public function getForeignKeys(): array;
}
