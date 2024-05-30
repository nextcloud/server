<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1005Date20180530124431 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$types = ['resources', 'rooms'];
		foreach ($types as $type) {
			if (!$schema->hasTable('calendar_' . $type)) {
				$table = $schema->createTable('calendar_' . $type);

				$table->addColumn('id', Types::BIGINT, [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 11,
					'unsigned' => true,
				]);
				$table->addColumn('backend_id', Types::STRING, [
					'notnull' => false,
					'length' => 64,
				]);
				$table->addColumn('resource_id', Types::STRING, [
					'notnull' => false,
					'length' => 64,
				]);
				$table->addColumn('email', Types::STRING, [
					'notnull' => false,
					'length' => 255,
				]);
				$table->addColumn('displayname', Types::STRING, [
					'notnull' => false,
					'length' => 255,
				]);
				$table->addColumn('group_restrictions', Types::STRING, [
					'notnull' => false,
					'length' => 4000,
				]);

				$table->setPrimaryKey(['id']);
				$table->addIndex(['backend_id', 'resource_id'], 'calendar_' . $type . '_bkdrsc');
				$table->addIndex(['email'], 'calendar_' . $type . '_email');
				$table->addIndex(['displayname'], 'calendar_' . $type . '_name');
			}
		}

		return $schema;
	}
}
