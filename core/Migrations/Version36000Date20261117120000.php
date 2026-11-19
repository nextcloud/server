<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[CreateTable(table: 'one_time_password', columns: ['password', 'expiration', 'provider', 'recipient'], description: 'Stores short-lived one-time passwords', notes: [])]
#[AddColumn(table: 'share', name: 'one_time_password', type: ColumnType::BIGINT, description: 'References the one-time password protecting a share')]
class Version36000Date20261117120000 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$updated = false;

		if (!$schema->hasTable('one_time_password')) {
			$table = $schema->createTable('one_time_password');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
				'length' => 20,
			]);
			$table->addColumn('password', Types::STRING, [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('expiration', Types::DATETIME, [
				'notnull' => false,
			]);
			$table->addColumn('provider', Types::STRING, [
				'notnull' => true,
				'length' => 255
			]);
			$table->addColumn('recipient', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->setPrimaryKey(['id']);
			$updated = true;
		}

		if ($schema->hasTable('share')) {
			$table = $schema->getTable('share');
			if (!$table->hasColumn('one_time_password')) {
				$table->addColumn('one_time_password', Types::BIGINT, [
					'notnull' => false,
					'default' => null,
					'unsigned' => true,
					'length' => 20,
				]);
				$updated = true;
			}
		}

		return $updated ? $schema : null;
	}
}
