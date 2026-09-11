<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

#[CreateTable(
	table: 'remember_login_tokens',
	columns: ['uid', 'token', 'created'],
	description: 'New table to store remember login tokens, replacing the login_token entries kept in oc_preferences',
)]
#[AddIndex(table: 'remember_login_tokens', type: IndexType::PRIMARY)]
#[AddIndex(table: 'remember_login_tokens', type: IndexType::UNIQUE, description: 'Allows to search on token')]
#[AddIndex(table: 'remember_login_tokens', type: IndexType::INDEX, description: 'Allows to search on user ID')]
class Version36000Date20260908184209 extends SimpleMigrationStep {

	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('remember_login_tokens')) {
			$table = $schema->createTable('remember_login_tokens');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 20,
				'unsigned' => true,
			]);
			$table->addColumn('uid', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('token', Types::STRING, [
				'notnull' => true,
				'length' => 200,
			]);
			$table->addColumn('created', Types::BIGINT, [
				'notnull' => true,
				'length' => 20,
				'unsigned' => true,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['token'], 'remember_login_tokens_token');
			$table->addIndex(['uid'], 'remember_login_tokens_uid');

			return $schema;
		}

		return null;
	}
}
