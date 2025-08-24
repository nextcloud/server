<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[AddColumn(table: 'dav_absence', name: 'replacement_user_id', type: ColumnType::STRING)]
#[AddColumn(table: 'dav_absence', name: 'replacement_user_display_name', type:  ColumnType::STRING)]
class Version1031Date20240610134258 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$tableDavAbsence = $schema->getTable('dav_absence');

		if (!$tableDavAbsence->hasColumn('replacement_user_id')) {
			$tableDavAbsence->addColumn('replacement_user_id', Types::STRING, [
				'notnull' => false,
				'default' => '',
				'length' => 64,
			]);
		}

		if (!$tableDavAbsence->hasColumn('replacement_user_display_name')) {
			$tableDavAbsence->addColumn('replacement_user_display_name', Types::STRING, [
				'notnull' => false,
				'default' => '',
				'length' => 64,
			]);
		}

		return $schema;
	}

}
