<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1006Date20180628111625 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('calendarchanges')) {
			$calendarChangesTable = $schema->getTable('calendarchanges');
			$calendarChangesTable->addColumn('calendartype', Types::INTEGER, [
				'notnull' => true,
				'default' => 0,
			]);

			if ($calendarChangesTable->hasIndex('calendarid_synctoken')) {
				$calendarChangesTable->dropIndex('calendarid_synctoken');
			}
			$calendarChangesTable->addIndex(['calendarid', 'calendartype', 'synctoken'], 'calid_type_synctoken');
		}

		if ($schema->hasTable('calendarobjects')) {
			$calendarObjectsTable = $schema->getTable('calendarobjects');
			$calendarObjectsTable->addColumn('calendartype', Types::INTEGER, [
				'notnull' => true,
				'default' => 0,
			]);

			if ($calendarObjectsTable->hasIndex('calobjects_index')) {
				$calendarObjectsTable->dropIndex('calobjects_index');
			}
			$calendarObjectsTable->addUniqueIndex(['calendarid', 'calendartype', 'uri'], 'calobjects_index');
			$calendarObjectsTable->addUniqueIndex(['calendarid', 'calendartype', 'uid'], 'calobjects_by_uid_index');
		}

		if ($schema->hasTable('calendarobjects_props')) {
			$calendarObjectsPropsTable = $schema->getTable('calendarobjects_props');
			$calendarObjectsPropsTable->addColumn('calendartype', Types::INTEGER, [
				'notnull' => true,
				'default' => 0,
			]);


			if ($calendarObjectsPropsTable->hasIndex('calendarobject_index')) {
				$calendarObjectsPropsTable->dropIndex('calendarobject_index');
			}
			if ($calendarObjectsPropsTable->hasIndex('calendarobject_name_index')) {
				$calendarObjectsPropsTable->dropIndex('calendarobject_name_index');
			}
			if ($calendarObjectsPropsTable->hasIndex('calendarobject_value_index')) {
				$calendarObjectsPropsTable->dropIndex('calendarobject_value_index');
			}

			$calendarObjectsPropsTable->addIndex(['objectid', 'calendartype'], 'calendarobject_index');
			$calendarObjectsPropsTable->addIndex(['name', 'calendartype'], 'calendarobject_name_index');
			$calendarObjectsPropsTable->addIndex(['value', 'calendartype'], 'calendarobject_value_index');
			$calendarObjectsPropsTable->addIndex(['calendarid', 'calendartype'], 'calendarobject_calid_index');
		}

		if ($schema->hasTable('calendarsubscriptions')) {
			$calendarSubscriptionsTable = $schema->getTable('calendarsubscriptions');
			$calendarSubscriptionsTable->addColumn('synctoken', 'integer', [
				'notnull' => true,
				'default' => 1,
				'length' => 10,
				'unsigned' => true,
			]);
		}

		return $schema;
	}
}
