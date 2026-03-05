<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version27000Date20220613163520 extends SimpleMigrationStep {
	public function name(): string {
		return 'Add mountpoint path to mounts table unique index';
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('mounts');
		if ($table->hasIndex('mounts_user_root_index')) {
			$table->dropIndex('mounts_user_root_index');
			// new index gets added with "add missing indexes"
		}

		return $schema;
	}
}
