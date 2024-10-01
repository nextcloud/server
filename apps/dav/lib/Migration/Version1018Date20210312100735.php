<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1018Date20210312100735 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$calendarsTable = $schema->getTable('calendars');
		$calendarsTable->addColumn('deleted_at', Types::INTEGER, [
			'notnull' => false,
			'length' => 4,
			'unsigned' => true,
		]);
		$calendarsTable->addIndex([
			'principaluri',
			'deleted_at',
		], 'cals_princ_del_idx');

		$calendarObjectsTable = $schema->getTable('calendarobjects');
		$calendarObjectsTable->addColumn('deleted_at', Types::INTEGER, [
			'notnull' => false,
			'length' => 4,
			'unsigned' => true,
		]);

		return $schema;
	}
}
