<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Delete the admin|personal sections and settings tables
 */
class Version14000Date20180129121024 extends SimpleMigrationStep {
	public function name(): string {
		return 'Drop obsolete settings tables';
	}

	public function description(): string {
		return 'Drops the following obsolete tables: "admin_sections", "admin_settings", "personal_sections" and "personal_settings"';
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$schema->dropTable('admin_sections');
		$schema->dropTable('admin_settings');
		$schema->dropTable('personal_sections');
		$schema->dropTable('personal_settings');

		return $schema;
	}
}
