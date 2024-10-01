<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

// Create new tables for the Metadata API (files_metadata and files_metadata_index).
class Version28000Date20231004103301 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$updated = false;

		if (!$schema->hasTable('files_metadata')) {
			$table = $schema->createTable('files_metadata');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('file_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('json', Types::TEXT);
			$table->addColumn('sync_token', Types::STRING, [
				'length' => 15,
			]);
			$table->addColumn('last_update', Types::DATETIME);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['file_id'], 'files_meta_fileid');
			$updated = true;
		}

		if (!$schema->hasTable('files_metadata_index')) {
			$table = $schema->createTable('files_metadata_index');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('file_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('meta_key', Types::STRING, [
				'notnull' => false,
				'length' => 31,
			]);
			$table->addColumn('meta_value_string', Types::STRING, [
				'notnull' => false,
				'length' => 63,
			]);
			$table->addColumn('meta_value_int', Types::BIGINT, [
				'notnull' => false,
				'length' => 11,
			]);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['file_id', 'meta_key', 'meta_value_string'], 'f_meta_index');
			$table->addIndex(['file_id', 'meta_key', 'meta_value_int'], 'f_meta_index_i');
			$updated = true;
		}

		if (!$updated) {
			return null;
		}

		return $schema;
	}
}
