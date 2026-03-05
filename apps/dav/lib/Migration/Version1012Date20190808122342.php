<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version1012Date20190808122342 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 * @since 17.0.0
	 */
	public function changeSchema(IOutput $output,
		\Closure $schemaClosure,
		array $options):?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('calendar_reminders')) {
			$table = $schema->createTable('calendar_reminders');

			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('calendar_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 11,
			]);
			$table->addColumn('object_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 11,
			]);
			$table->addColumn('is_recurring', Types::SMALLINT, [
				'notnull' => false,
				'length' => 1,
			]);
			$table->addColumn('uid', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('recurrence_id', Types::BIGINT, [
				'notnull' => false,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('is_recurrence_exception', Types::SMALLINT, [
				'notnull' => true,
				'length' => 1,
			]);
			$table->addColumn('event_hash', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('alarm_hash', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('type', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('is_relative', Types::SMALLINT, [
				'notnull' => true,
				'length' => 1,
			]);
			$table->addColumn('notification_date', Types::BIGINT, [
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('is_repeat_based', Types::SMALLINT, [
				'notnull' => true,
				'length' => 1,
			]);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['object_id'], 'calendar_reminder_objid');
			$table->addIndex(['uid', 'recurrence_id'], 'calendar_reminder_uidrec');

			return $schema;
		}

		return null;
	}
}
