<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\ModifyColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

#[ModifyColumn(table: 'calendarobjects', name: 'firstoccurence', description: 'Change firstoccurence to signed to allow dates before the unix epoch')]
#[ModifyColumn(table: 'calendarobjects', name: 'lastoccurence', description: 'Change lastoccurence to signed to allow dates before the unix epoch')]
class Version1037Date20251211000000 extends SimpleMigrationStep {
	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$modified = false;

		$table = $schema->getTable('calendarobjects');

		$column = $table->getColumn('firstoccurence');
		if ($column->getUnsigned()) {
			$column->setUnsigned(false);
			$modified = true;
		}

		$column = $table->getColumn('lastoccurence');
		if ($column->getUnsigned()) {
			$column->setUnsigned(false);
			$modified = true;
		}

		return $modified ? $schema : null;
	}
}
