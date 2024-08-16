<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesReminders\Migration;

use Closure;
use OCA\FilesReminders\Db\ReminderMapper;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version10000Date20230725162149 extends SimpleMigrationStep {
	/**
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable(ReminderMapper::TABLE_NAME)) {
			return null;
		}

		$table = $schema->createTable(ReminderMapper::TABLE_NAME);
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
		$table->addColumn('file_id', Types::BIGINT, [
			'notnull' => true,
			'length' => 20,
			'unsigned' => true,
		]);
		$table->addColumn('due_date', Types::DATETIME, [
			'notnull' => true,
		]);
		$table->addColumn('updated_at', Types::DATETIME, [
			'notnull' => true,
		]);
		$table->addColumn('created_at', Types::DATETIME, [
			'notnull' => true,
		]);
		$table->addColumn('notified', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$table->setPrimaryKey(['id']);
		$table->addUniqueIndex(['user_id', 'file_id', 'due_date'], 'reminders_uniq_idx');

		return $schema;
	}
}
