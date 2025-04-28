<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\Attributes\DropIndex;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Create new column and index for lazy loading in preferences for the new IUserPreferences API.
 */
#[CreateTable(table: 'async_processes', columns: ['id', 'token', 'type', 'code', 'params', 'orig', 'result', 'status'], description: 'async task and status')]
class Version32000Date20250307222601 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('async_process')) {
			return null;
		}

		$table = $schema->createTable('async_process');
		$table->addColumn('id', Types::BIGINT, [	'notnull' => true, 'length' => 64, 'autoincrement' => true, 'unsigned' => true]);
		$table->addColumn('token', Types::STRING, ['notnull' => true, 'default' => '', 'length' => 15]);
		$table->addColumn('session_token', Types::STRING, ['notnull' => true, 'default' => '', 'length' => 15]);
		$table->addColumn('type', Types::SMALLINT, ['notnull' => true, 'default' => 0, 'unsigned' => true]);
		$table->addColumn('code', Types::TEXT, ['notnull' => true, 'default' => '']);
		$table->addColumn('params', Types::TEXT, ['notnull' => true, 'default' => '[]']);
		$table->addColumn('dataset', Types::TEXT, ['notnull' => true, 'default' => '[]']);
		$table->addColumn('metadata', Types::TEXT, ['notnull' => true, 'default' => '[]']);
		$table->addColumn('links', Types::TEXT, ['notnull' => true, 'default' => '[]']);
		$table->addColumn('orig', Types::TEXT, ['notnull' => true, 'default' => '[]']);
		$table->addColumn('result', Types::TEXT, ['notnull' => true, 'default' => '']);
		$table->addColumn('status', Types::SMALLINT, ['notnull' => true, 'default' => 0, 'unsigned' => true]);
		$table->addColumn('execution_time', Types::SMALLINT, ['notnull' => true, 'default' => 0, 'unsigned' => true]);
		$table->addColumn('lock_token', Types::STRING, ['notnull' => true, 'default' => '', 'length' => 7]);
		$table->addColumn('creation', Types::INTEGER, ['notnull' => true, 'default' => 0, 'unsigned' => true]);
		$table->addColumn('last_run', Types::INTEGER, ['notnull' => true, 'default' => 0, 'unsigned' => true]);
		$table->addColumn('next_run', Types::INTEGER, ['notnull' => true, 'default' => 0, 'unsigned' => true]);

		$table->setPrimaryKey(['id'], 'asy_prc_id');

		$table->addIndex(['token'], 'asy_prc_tkn');
		$table->addIndex(['status'], 'asy_prc_sts');
		$table->addIndex(['next_run'], 'asy_prc_nxt');
		$table->addIndex(['status', 'id', 'next_run'], 'asy_prc_sin');

		return $schema;
	}
}
