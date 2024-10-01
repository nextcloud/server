<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Create new fields for type and lazy loading in appconfig for the new IAppConfig API.
 */
class Version28000Date20231126110901 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		return null;

		/**
		 * this migration was needed during Nextcloud 28 to prep the migration to 29 and a
		 * new IAppConfig as its API require 'lazy' and 'type' database field.
		 *
		 * some changes in the migration process and the expected result have made its execution
		 * useless, therefore ignored.
		 *
		 * @see Version29000Date20240124132201
		 * @see Version29000Date20240124132202
		 */
		//		/** @var ISchemaWrapper $schema */
		//		$schema = $schemaClosure();
		//
		//		if (!$schema->hasTable('appconfig')) {
		//			return null;
		//		}
		//
		//		$table = $schema->getTable('appconfig');
		//		if ($table->hasColumn('lazy')) {
		//			return null;
		//		}
		//
		//		// type=2 means value is typed as MIXED
		//		$table->addColumn('type', Types::INTEGER, ['notnull' => true, 'default' => 2]);
		//		$table->addColumn('lazy', Types::BOOLEAN, ['notnull' => false, 'default' => false]);
		//
		//		return $schema;
	}
}
