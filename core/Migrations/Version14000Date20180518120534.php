<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version14000Date20180518120534 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('authtoken');
		$table->addColumn('private_key', 'text', [
			'notnull' => false,
		]);
		$table->addColumn('public_key', 'text', [
			'notnull' => false,
		]);
		$table->addColumn('version', 'smallint', [
			'notnull' => true,
			'default' => 1,
			'unsigned' => true,
		]);
		$table->addIndex(['uid'], 'authtoken_uid_index');

		return $schema;
	}
}
