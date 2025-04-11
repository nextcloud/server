<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1010Date20200630192639 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('files_trash')) {
			$table = $schema->createTable('files_trash');
			$table->addColumn('auto_id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('id', Types::STRING, [
				'notnull' => true,
				'length' => 250,
				'default' => '',
			]);
			$table->addColumn('user', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('timestamp', Types::STRING, [
				'notnull' => true,
				'length' => 12,
				'default' => '',
			]);
			$table->addColumn('location', Types::STRING, [
				'notnull' => true,
				'length' => 512,
				'default' => '',
			]);
			$table->addColumn('type', Types::STRING, [
				'notnull' => false,
				'length' => 4,
			]);
			$table->addColumn('mime', Types::STRING, [
				'notnull' => false,
				'length' => 255,
			]);
			$table->setPrimaryKey(['auto_id']);
			$table->addIndex(['id'], 'id_index');
			$table->addIndex(['timestamp'], 'timestamp_index');
			$table->addIndex(['user'], 'user_index');
		}
		return $schema;
	}
}
