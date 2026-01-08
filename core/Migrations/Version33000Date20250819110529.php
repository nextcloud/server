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
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 *
 */
#[CreateTable(table: 'preview', description: 'Holds the preview data')]
#[CreateTable(table: 'preview_locations', description: 'Holds the preview location in an object store')]
class Version33000Date20250819110529 extends SimpleMigrationStep {

	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('preview_locations')) {
			$table = $schema->createTable('preview_locations');
			$table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'length' => 20, 'unsigned' => true]);
			$table->addColumn('bucket_name', Types::STRING, ['notnull' => true, 'length' => 40]);
			$table->addColumn('object_store_name', Types::STRING, ['notnull' => true, 'length' => 40]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('preview_versions')) {
			$table = $schema->createTable('preview_versions');
			$table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'length' => 20, 'unsigned' => true]);
			$table->addColumn('file_id', Types::BIGINT, ['notnull' => true, 'length' => 20, 'unsigned' => true]);
			$table->addColumn('version', Types::STRING, ['notnull' => true, 'default' => '', 'length' => 1024]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('previews')) {
			$table = $schema->createTable('previews');
			$table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true, 'length' => 20, 'unsigned' => true]);
			$table->addColumn('file_id', Types::BIGINT, ['notnull' => true, 'length' => 20, 'unsigned' => true]);
			$table->addColumn('storage_id', Types::BIGINT, ['notnull' => true, 'length' => 20, 'unsigned' => true]);
			$table->addColumn('old_file_id', Types::BIGINT, ['notnull' => false, 'length' => 20, 'unsigned' => true]);
			$table->addColumn('location_id', Types::BIGINT, ['notnull' => false, 'length' => 20, 'unsigned' => true]);
			$table->addColumn('width', Types::INTEGER, ['notnull' => true, 'unsigned' => true]);
			$table->addColumn('height', Types::INTEGER, ['notnull' => true, 'unsigned' => true]);
			$table->addColumn('mimetype_id', Types::INTEGER, ['notnull' => true]);
			$table->addColumn('source_mimetype_id', Types::INTEGER, ['notnull' => true]);
			$table->addColumn('max', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
			$table->addColumn('cropped', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
			$table->addColumn('encrypted', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
			$table->addColumn('etag', Types::STRING, ['notnull' => true, 'length' => 40, 'fixed' => true]);
			$table->addColumn('mtime', Types::INTEGER, ['notnull' => true, 'unsigned' => true]);
			$table->addColumn('size', Types::INTEGER, ['notnull' => true, 'unsigned' => true]);
			$table->addColumn('version_id', Types::BIGINT, ['notnull' => true, 'default' => -1]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['file_id']);
			$table->addUniqueIndex(['file_id', 'width', 'height', 'mimetype_id', 'cropped', 'version_id'], 'previews_file_uniq_idx');
		}

		return $schema;
	}
}
