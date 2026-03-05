<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1004Date20170924124212 extends SimpleMigrationStep {

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

		$table = $schema->getTable('cards');
		// Dropped in Version1030Date20240205103243 because cards_abid is redundant with cards_abiduri
		// $table->addIndex(['addressbookid'], 'cards_abid');
		$table->addIndex(['addressbookid', 'uri'], 'cards_abiduri');

		$table = $schema->getTable('cards_properties');
		// Removed later on
		// $table->addIndex(['addressbookid'], 'cards_prop_abid');
		// Added later on
		$table->addIndex(['addressbookid', 'name', 'value'], 'cards_prop_abid_name_value', );

		return $schema;
	}
}
