<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version12101Date20221011153334 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->createTable('open_local_editor');
		$table->addColumn('id', Types::BIGINT, [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 20,
			'unsigned' => true,
		]);
		$table->addColumn('user_id', Types::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('path_hash', Types::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('expiration_time', Types::BIGINT, [
			'notnull' => true,
			'unsigned' => true,
		]);
		$table->addColumn('token', Types::STRING, [
			'notnull' => true,
			'length' => 128,
		]);

		$table->setPrimaryKey(['id']);
		$table->addUniqueIndex(['user_id', 'path_hash', 'token'], 'openlocal_user_path_token');

		return $schema;
	}
}
