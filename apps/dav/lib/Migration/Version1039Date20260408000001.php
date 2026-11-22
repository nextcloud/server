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
 * Drops default_alarm in a separate step from adding its replacements, as
 * dropping and adding columns with the same definition in one step lets the
 * schema comparator turn them into a rename, which fails on Oracle.
 */
#[DropColumn(table: 'calendars', name: 'default_alarm', description: 'Replaced by default_alarm_pday and default_alarm_fday')]
class Version1039Date20260408000001 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$calendarsTable = $schema->getTable('calendars');
		if (!$calendarsTable->hasColumn('default_alarm')) {
			return null;
		}

		$calendarsTable->dropColumn('default_alarm');
		return $schema;
	}
}
