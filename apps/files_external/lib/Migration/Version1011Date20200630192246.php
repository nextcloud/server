<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1011Date20200630192246 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('external_mounts')) {
			$table = $schema->createTable('external_mounts');
			$table->addColumn('mount_id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 6,
			]);
			$table->addColumn('mount_point', Types::STRING, [
				'notnull' => true,
				'length' => 128,
			]);
			$table->addColumn('storage_backend', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('auth_backend', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('priority', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
				'default' => 100,
			]);
			$table->addColumn('type', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
			]);
			$table->setPrimaryKey(['mount_id']);
		}

		if (!$schema->hasTable('external_applicable')) {
			$table = $schema->createTable('external_applicable');
			$table->addColumn('applicable_id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 6,
			]);
			$table->addColumn('mount_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 6,
			]);
			$table->addColumn('type', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('value', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->setPrimaryKey(['applicable_id']);
			$table->addIndex(['mount_id'], 'applicable_mount');
			$table->addUniqueIndex(['type', 'value', 'mount_id'], 'applicable_type_value_mount');
		}

		if (!$schema->hasTable('external_config')) {
			$table = $schema->createTable('external_config');
			$table->addColumn('config_id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 6,
			]);
			$table->addColumn('mount_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 6,
			]);
			$table->addColumn('key', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('value', Types::STRING, [
				'notnull' => false,
				'length' => 4000,
			]);
			$table->setPrimaryKey(['config_id']);
			$table->addUniqueIndex(['mount_id', 'key'], 'config_mount_key');
		} else {
			$table = $schema->getTable('external_config');
			$table->changeColumn('value', [
				'notnull' => false,
				'length' => 4000,
			]);
		}

		if (!$schema->hasTable('external_options')) {
			$table = $schema->createTable('external_options');
			$table->addColumn('option_id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 6,
			]);
			$table->addColumn('mount_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 6,
			]);
			$table->addColumn('key', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('value', Types::STRING, [
				'notnull' => true,
				'length' => 256,
			]);
			$table->setPrimaryKey(['option_id']);
			$table->addUniqueIndex(['mount_id', 'key'], 'option_mount_key');
		}
		return $schema;
	}
}
