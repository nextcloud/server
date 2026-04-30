<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\Attributes\DropColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[DropColumn(table: 'calendars', name: 'default_alarm', description: 'Replaced by default_alarm_pday and default_alarm_fday')]
#[AddColumn(table: 'calendars', name: 'default_alarm_pday', type: ColumnType::INTEGER)]
#[AddColumn(table: 'calendars', name: 'default_alarm_fday', type: ColumnType::INTEGER)]
class Version1039Date20260408000000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$calendarsTable = $schema->getTable('calendars');

		if ($calendarsTable->hasColumn('default_alarm')) {
			$calendarsTable->dropColumn('default_alarm');
		}

		if (!$calendarsTable->hasColumn('default_alarm_pday')) {
			$calendarsTable->addColumn('default_alarm_pday', Types::INTEGER, [
				'notnull' => false,
				'default' => null,
			]);
		}

		if (!$calendarsTable->hasColumn('default_alarm_fday')) {
			$calendarsTable->addColumn('default_alarm_fday', Types::INTEGER, [
				'notnull' => false,
				'default' => null,
			]);
		}

		return $schema;
	}
}
