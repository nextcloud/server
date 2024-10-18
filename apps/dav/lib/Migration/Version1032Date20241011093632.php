<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1032Date20241011093632 extends SimpleMigrationStep {
	public function name(): string {
		return 'Add dav_page_cache table';
	}

	public function description(): string {
		return 'Add table to cache webdav multistatus responses for pagination purpose';
	}

	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('dav_page_cache')) {
			$table = $schema->createTable('dav_page_cache');

			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true
			]);
			$table->addColumn('url_hash', Types::STRING, [
				'notnull' => true,
				'length' => 32,
			]);
			$table->addColumn('token', Types::STRING, [
				'notnull' => true,
				'length' => 32
			]);
			$table->addColumn('result_index', Types::INTEGER, [
				'notnull' => true
			]);
			$table->addColumn('result_value', Types::TEXT, [
				'notnull' => false,
			]);
			$table->addColumn('insert_time', Types::DATETIME, [
				'notnull' => true,
			]);

			$table->setPrimaryKey(['id'], 'dav_page_cache_id_index');
			$table->addIndex(['token', 'url_hash'], 'dav_page_cache_token_url');
			$table->addUniqueIndex(['token', 'url_hash', 'result_index'], 'dav_page_cache_url_index');
			$table->addIndex(['result_index'], 'dav_page_cache_index');
			$table->addIndex(['insert_time'], 'dav_page_cache_time');
		}

		return $schema;
	}
}
