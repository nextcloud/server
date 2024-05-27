<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Create new column for type and remove previous lazy column in appconfig (will be recreated by Version29000Date20240124132202) for the new IAppConfig API.
 */
class Version29000Date20240124132201 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('appconfig');

		// we will drop 'lazy', we start to clean related indexes first
		if ($table->hasIndex('ac_lazy_i')) {
			$table->dropIndex('ac_lazy_i');
		}

		if ($table->hasIndex('ac_app_lazy_i')) {
			$table->dropIndex('ac_app_lazy_i');
		}

		if ($table->hasIndex('ac_app_lazy_key_i')) {
			$table->dropIndex('ac_app_lazy_key_i');
		}

		if ($table->hasColumn('lazy')) {
			$table->dropColumn('lazy');
		}

		// create field 'type' if it does not exist yet, or fix the fact that it is missing 'unsigned'
		if (!$table->hasColumn('type')) {
			$table->addColumn('type', Types::INTEGER, ['notnull' => true, 'default' => 2, 'unsigned' => true]);
		} else {
			$table->modifyColumn('type', ['notnull' => true, 'default' => 2, 'unsigned' => true]);
		}

		// not needed anymore
		if ($table->hasIndex('appconfig_config_key_index')) {
			$table->dropIndex('appconfig_config_key_index');
		}

		return $schema;
	}
}
