<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1008Date20181114084440 extends SimpleMigrationStep {


	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('calendarchanges')) {
			$calendarChangesTable = $schema->getTable('calendarchanges');
			if ($calendarChangesTable->hasIndex('calendarid_calendartype_synctoken')) {
				$calendarChangesTable->dropIndex('calendarid_calendartype_synctoken');
			}

			if (!$calendarChangesTable->hasIndex('calid_type_synctoken')) {
				$calendarChangesTable->addIndex(['calendarid', 'calendartype', 'synctoken'], 'calid_type_synctoken');
			}
		}

		return $schema;
	}
}
