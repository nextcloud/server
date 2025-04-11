<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UserStatus\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1008Date20230921144701 extends SimpleMigrationStep {

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$statusTable = $schema->getTable('user_status');
		if (!($statusTable->hasColumn('status_message_timestamp'))) {
			$statusTable->addColumn('status_message_timestamp', Types::INTEGER, [
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
				'default' => 0,
			]);
		}
		if (!$statusTable->hasIndex('user_status_mtstmp_ix')) {
			$statusTable->addIndex(['status_message_timestamp'], 'user_status_mtstmp_ix');
		}

		return $schema;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$qb = $this->connection->getQueryBuilder();

		$update = $qb->update('user_status')
			->set('status_message_timestamp', 'status_timestamp');

		$update->executeStatement();
	}
}
