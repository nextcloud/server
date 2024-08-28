<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Db;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\PhpIntegerMappingType;
use Doctrine\DBAL\Types\Type;

class SchemaEncoder {
	/**
	 * Encode a DBAL schema to json, performing some normalization based on the database platform
	 *
	 * @param Schema $schema
	 * @param AbstractPlatform $platform
	 * @return array
	 */
	public function encodeSchema(Schema $schema, AbstractPlatform $platform): array {
		$encoded = ['tables' => [], 'sequences' => []];
		foreach ($schema->getTables() as $table) {
			$encoded[$table->getName()] = $this->encodeTable($table, $platform);
		}
		ksort($encoded);
		return $encoded;
	}

	/**
	 * @psalm-type ColumnArrayType =
	 */
	private function encodeTable(Table $table, AbstractPlatform $platform): array {
		$encoded = ['columns' => [], 'indexes' => []];
		foreach ($table->getColumns() as $column) {
			/**
			 * @var array{
			 *     name: string,
			 *     default: mixed,
			 *     notnull: bool,
			 *     length: ?int,
			 *     precision: int,
			 *     scale: int,
			 *     unsigned: bool,
			 *     fixed: bool,
			 *     autoincrement: bool,
			 *     comment: string,
			 *     columnDefinition: ?string,
			 *     collation?: string,
			 *     charset?: string,
			 *     jsonb?: bool,
			 * } $data
			 **/
			$data = $column->toArray();
			$data['type'] = Type::getTypeRegistry()->lookupName($column->getType());
			$data['default'] = $column->getType()->convertToPHPValue($column->getDefault(), $platform);
			if ($platform instanceof PostgreSQLPlatform) {
				$data['unsigned'] = false;
				if ($column->getType() instanceof PhpIntegerMappingType) {
					$data['length'] = null;
				}
				unset($data['jsonb']);
			} elseif ($platform instanceof AbstractMySqlPlatform) {
				if ($column->getType() instanceof PhpIntegerMappingType) {
					$data['length'] = null;
				} elseif (in_array($data['type'], ['text', 'blob', 'datetime', 'float', 'json'])) {
					$data['length'] = 0;
				}
				unset($data['collation']);
				unset($data['charset']);
			}
			if ($data['type'] === 'string' && $data['length'] === null) {
				$data['length'] = 255;
			}
			$encoded['columns'][$column->getName()] = $data;
		}
		ksort($encoded['columns']);
		foreach ($table->getIndexes() as $index) {
			$options = $index->getOptions();
			if (isset($options['lengths']) && count(array_filter($options['lengths'])) === 0) {
				unset($options['lengths']);
			}
			if ($index->isPrimary()) {
				if ($platform instanceof PostgreSqlPlatform) {
					$name = $table->getName() . '_pkey';
				} elseif ($platform instanceof AbstractMySQLPlatform) {
					$name = 'PRIMARY';
				} else {
					$name = $index->getName();
				}
			} else {
				$name = $index->getName();
			}
			if ($platform instanceof PostgreSqlPlatform) {
				$name = strtolower($name);
			}
			$encoded['indexes'][$name] = [
				'name' => $name,
				'columns' => $index->getColumns(),
				'unique' => $index->isUnique(),
				'primary' => $index->isPrimary(),
				'flags' => $index->getFlags(),
				'options' => $options,
			];
		}
		ksort($encoded['indexes']);
		return $encoded;
	}
}
