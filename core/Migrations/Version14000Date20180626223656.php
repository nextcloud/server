<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;

class Version14000Date20180626223656 extends SimpleMigrationStep {
	public function changeSchema(\OCP\Migration\IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if (!$schema->hasTable('whats_new')) {
			$table = $schema->createTable('whats_new');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
				'unsigned' => true,
			]);
			$table->addColumn('version', 'string', [
				'notnull' => true,
				'length' => 64,
				'default' => '11',
			]);
			$table->addColumn('etag', 'string', [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('last_check', 'integer', [
				'notnull' => true,
				'length' => 4,
				'unsigned' => true,
				'default' => 0,
			]);
			$table->addColumn('data', 'text', [
				'notnull' => true,
				'default' => '',
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['version'], 'version');
			$table->addIndex(['version', 'etag'], 'version_etag_idx');
		}

		return $schema;
	}
}
