<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version16000Date20190207141427 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('collres_collections')) {
			$table = $schema->createTable('collres_collections');

			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('name', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);

			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('collres_resources')) {
			$table = $schema->createTable('collres_resources');

			$table->addColumn('collection_id', Types::BIGINT, [
				'notnull' => true,
			]);
			$table->addColumn('resource_type', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('resource_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);

			$table->setPrimaryKey(['collection_id', 'resource_type', 'resource_id'], 'crr_pk');
			//			$table->addUniqueIndex(['collection_id', 'resource_type', 'resource_id'], 'collres_unique_res');
		}

		if (!$schema->hasTable('collres_accesscache')) {
			$table = $schema->createTable('collres_accesscache');

			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('collection_id', Types::BIGINT, [
				'notnull' => false,
				'default' => 0,
			]);
			$table->addColumn('resource_type', Types::STRING, [
				'notnull' => false,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('resource_id', Types::STRING, [
				'notnull' => false,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('access', Types::SMALLINT, [
				'notnull' => false,
				'default' => 0,
			]);

			$table->setPrimaryKey(['user_id', 'collection_id', 'resource_type', 'resource_id'], 'cra_pk');
			//			$table->addUniqueIndex(['user_id', 'collection_id', 'resource_type', 'resource_id'], 'collres_unique_user');
			$table->addIndex(['user_id', 'resource_type', 'resource_id'], 'collres_user_res');
			$table->addIndex(['user_id', 'collection_id'], 'collres_user_coll');
		}

		return $schema;
	}
}
