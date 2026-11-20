<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sharing\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Schema\ITable;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

final class Version2000Date20261120164144 extends SimpleMigrationStep {
	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();
		$changed = false;

		$indices = [
			['table' => 'sharing_share_recipients', 'columns' => ['share_id', 'recipient_class_id', 'recipient_value'], 'unique' => true],
		];
		foreach ($indices as $index) {
			if ($schema->hasTable($index['table'])) {
				$table = $schema->getTable($index['table']);
				$name = $this->generateIndexName($table, $index['columns'], $index['unique']);
				if ($table->hasIndex($name)) {
					$table->dropIndex($name);
					$changed = true;
				}
			}
		}

		return $changed ? $schema : null;
	}

	/**
	 * @param string[] $columns
	 * @return non-empty-string
	 */
	private function generateIndexName(ITable $table, array $columns, bool $unique): string {
		$maxLength = $this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_ORACLE ? 30 : 63;
		return $this->generateIdentifierName(
			array_merge([$table->getName()], $columns),
			$unique ? 'uniq' : 'idx',
			$maxLength,
		);
	}

	/**
	 * @param string[] $columnNames
	 * @param non-empty-string $prefix
	 * @return non-empty-string
	 */
	private function generateIdentifierName(array $columnNames, string $prefix, int $maxSize = 30): string {
		$hash = implode('', array_map(static fn (string $column): string => dechex(crc32($column)), $columnNames));

		/** @var non-empty-string $name */
		$name = strtoupper(substr($prefix . '_' . $hash, 0, $maxSize));
		return $name;
	}
}
