<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\DropColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Drop legacy single-int default alarm columns after JSON migration.
 */
#[DropColumn(table: 'calendars', name: 'default_alarm_pday', description: 'Replaced by default_alarms_pday JSON')]
#[DropColumn(table: 'calendars', name: 'default_alarm_fday', description: 'Replaced by default_alarms_fday JSON')]
class Version2000Date20260703100002 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$calendarsTable = $schema->getTable('calendars');

		if ($calendarsTable->hasColumn('default_alarm_pday')) {
			$calendarsTable->dropColumn('default_alarm_pday');
		}

		if ($calendarsTable->hasColumn('default_alarm_fday')) {
			$calendarsTable->dropColumn('default_alarm_fday');
		}

		return $schema;
	}
}
