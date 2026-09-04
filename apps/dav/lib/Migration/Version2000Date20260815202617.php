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

#[AddColumn(table: 'calendarsubscriptions', name: 'lasterror', type: ColumnType::STRING)]
class Version2000Date20260815202617 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('calendarsubscriptions');

		if (!$table->hasColumn('lasterror')) {
			$table->addColumn('lasterror', Types::STRING, [
				'notnull' => false,
				'length' => 255,
				'default' => null,
			]);
		}

		return $schema;
	}
}
