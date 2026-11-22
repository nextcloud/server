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
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

#[AddColumn(table: 'calendars', name: 'disable_alarm_notifications', type: ColumnType::BOOLEAN)]
class Version3000Date20260828000000 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$modified = false;

		$calendarsTable = $schema->getTable('calendars');
		if (!$calendarsTable->hasColumn('disable_alarm_notifications')) {
			$calendarsTable->addColumn('disable_alarm_notifications', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false,
			]);
			$modified = true;
		}

		return $modified ? $schema : null;
	}
}
