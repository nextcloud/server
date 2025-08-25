<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 *
 */
class Version33000Date20250819110529 extends SimpleMigrationStep {

	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('previews')) {
			$table = $schema->createTable('previews');
			$table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'length' => 20, 'unsigned' => true]);
			$table->addColumn('file_id', Types::BIGINT, ['notnull' => true, 'length' => 20, 'unsigned' => true]);
			$table->addColumn('storage_id', Types::BIGINT, ['notnull' => true, 'length' => 20, 'unsigned' => true]);
			$table->addColumn('width', Types::INTEGER, ['notnull' => true, 'unsigned' => true]);
			$table->addColumn('height', Types::INTEGER, ['notnull' => true, 'unsigned' => true]);
			$table->addColumn('mimetype', Types::INTEGER, ['notnull' => true]);
			$table->addColumn('is_max', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
			$table->addColumn('crop', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
			$table->addColumn('etag', Types::STRING, ['notnull' => true, 'length' => 40]);
			$table->addColumn('mtime', Types::INTEGER, ['notnull' => true, 'unsigned' => true]);
			$table->addColumn('size', Types::INTEGER, ['notnull' => true, 'unsigned' => true]);
			$table->addColumn('version', Types::BIGINT, ['notnull' => true, 'default' => -1]); // can not be null otherwise unique index doesn't work
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['file_id', 'width', 'height', 'mimetype', 'crop', 'version'], 'previews_file_uniq_idx');
		}

		return $schema;
	}
}
