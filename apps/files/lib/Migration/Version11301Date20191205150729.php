<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version11301Date20191205150729 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->createTable('user_transfer_owner');
		$table->addColumn('id', 'bigint', [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 20,
			'unsigned' => true,
		]);
		$table->addColumn('source_user', 'string', [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('target_user', 'string', [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('file_id', 'bigint', [
			'notnull' => true,
			'length' => 20,
		]);
		$table->addColumn('node_name', 'string', [
			'notnull' => true,
			'length' => 255,
		]);
		$table->setPrimaryKey(['id']);

		// Quite radical, we just assume no one updates cross beta with a pending request.
		// Do not try this at home
		if ($schema->hasTable('user_transfer_ownership')) {
			$schema->dropTable('user_transfer_ownership');
		}

		return $schema;
	}
}
