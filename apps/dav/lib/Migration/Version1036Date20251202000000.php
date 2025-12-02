<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\Attributes\ModifyColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

#[ModifyColumn(table: 'calendarobjects', name: 'uid', type: ColumnType::STRING, description: 'Increase uid length to 512 characters')]
#[ModifyColumn(table: 'calendar_reminders', name: 'uid', type: ColumnType::STRING, description: 'Increase uid length to 512 characters')]
#[ModifyColumn(table: 'calendar_invitations', name: 'uid', type: ColumnType::STRING, description: 'Increase uid length to 512 characters')]
class Version1036Date20251202000000 extends SimpleMigrationStep {
	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$modified = false;

		$table = $schema->getTable('calendarobjects');
		$column = $table->getColumn('uid');
		if ($column->getLength() < 512) {
			$column->setLength(512);
			$modified = true;
		}

		$table = $schema->getTable('calendar_reminders');
		$column = $table->getColumn('uid');
		if ($column->getLength() < 512) {
			$column->setLength(512);
			$modified = true;
		}

		$table = $schema->getTable('calendar_invitations');
		$column = $table->getColumn('uid');
		if ($column->getLength() < 512) {
			$column->setLength(512);
			$modified = true;
		}

		return $modified ? $schema : null;
	}
}
