<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version18000Date20191014105105 extends SimpleMigrationStep {
	public function __construct(
		protected IDBConnection $connection,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$table = $schema->createTable('direct_edit');

		$table->addColumn('id', Types::BIGINT, [
			'autoincrement' => true,
			'notnull' => true,
		]);
		$table->addColumn('editor_id', Types::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('token', Types::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('file_id', Types::BIGINT, [
			'notnull' => true,
		]);
		$table->addColumn('user_id', Types::STRING, [
			'notnull' => false,
			'length' => 64,
		]);
		$table->addColumn('share_id', Types::BIGINT, [
			'notnull' => false
		]);
		$table->addColumn('timestamp', Types::BIGINT, [
			'notnull' => true,
			'length' => 20,
			'unsigned' => true,
		]);
		$table->addColumn('accessed', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false
		]);

		$table->setPrimaryKey(['id']);
		$table->addIndex(['token']);
		$table->addIndex(['timestamp'], 'direct_edit_timestamp');

		return $schema;
	}
}
