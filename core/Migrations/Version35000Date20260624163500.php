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
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[CreateTable('one_time_password', ['password', 'expiration', 'provider', 'recipient'], 'Stores short-lived one-time passwords', [])]
class Version35000Date20260624163500 extends SimpleMigrationStep {
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
			$table->addColumn('expiration', TYPES::DATETIME, [
				'notnull' => false,
			]);
			$table->addColumn('provider', TYPES::STRING, [
				'notnull' => true,
				'length' => 255
			]);
			$table->addColumn('recipient', TYPES::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['recipient'], 'recipient_index');
			$updated = true;
		}

		if ($schema->hasTable('share')) {
			$table = $schema->getTable('share');
			if (!$table->hasColumn('one_time_password')) {
				$table->addColumn('one_time_password', Types::BIGINT, [
					'unsigned' => true,
					'length' => 20,
				]);
				$updated = true;
			}
		}

		return $updated ? $schema : null;
	}

}
