<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1004Date20170919104507 extends SimpleMigrationStep {

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

		$table = $schema->getTable('addressbooks');
		$column = $table->getColumn('id');
		$column->setUnsigned(true);

		$table = $schema->getTable('calendarobjects');
		$column = $table->getColumn('id');
		$column->setUnsigned(true);

		$table = $schema->getTable('calendarchanges');
		$column = $table->getColumn('id');
		$column->setUnsigned(true);

		return $schema;
	}
}
