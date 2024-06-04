<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1029Date20231004091403 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('dav_absence')) {
			$table = $schema->createTable('dav_absence');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('first_day', Types::STRING, [
				'length' => 10,
				'notnull' => true,
			]);
			$table->addColumn('last_day', Types::STRING, [
				'length' => 10,
				'notnull' => true,
			]);
			$table->addColumn('status', Types::STRING, [
				'length' => 100,
				'notnull' => true,
			]);
			$table->addColumn('message', Types::TEXT, [
				'notnull' => true,
			]);
			$table->addUniqueIndex(['user_id'], 'dav_absence_uid_idx');
		} else {
			$table = $schema->getTable('dav_absence');
		}

		if ($table->getPrimaryKey() === null) {
			$table->setPrimaryKey(['id'], 'dav_absence_id_idx');
		}

		return $schema;
	}
}
