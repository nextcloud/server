<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Prepare `share_external` for the OCM token-exchange flow:
 *   - add `access_token` / `access_token_expires` for the short-lived Bearer token
 *   - add `refresh_token` and copy data from the legacy `share_token` column
 *     (the actual drop of `share_token` happens in a follow-up migration so the
 *     copy can complete first).
 */
#[AddColumn(table: 'share_external', name: 'access_token', type: ColumnType::STRING, description: 'Stores the short-lived OCM Bearer access token separately from the password field')]
#[AddColumn(table: 'share_external', name: 'access_token_expires', type: ColumnType::INTEGER, description: 'Unix timestamp when the stored OCM Bearer access token expires')]
#[AddColumn(table: 'share_external', name: 'refresh_token', type: ColumnType::STRING, description: 'Renamed from share_token to reflect its role in the OCM token-exchange flow')]
class Version33000Date20260306120000 extends SimpleMigrationStep {
	public function __construct(
		private readonly IDBConnection $db,
	) {
	}

	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$table = $schema->getTable('share_external');

		$changed = false;

		if (!$table->hasColumn('access_token')) {
			$table->addColumn('access_token', Types::STRING, [
				'notnull' => false,
				'default' => null,
				'length' => 4000,
			]);
			$changed = true;
		}

		if (!$table->hasColumn('access_token_expires')) {
			$table->addColumn('access_token_expires', Types::INTEGER, [
				'notnull' => false,
				'default' => null,
			]);
			$changed = true;
		}

		if (!$table->hasColumn('refresh_token')) {
			$table->addColumn('refresh_token', Types::STRING, [
				'notnull' => false,
				'length' => 64,
				'default' => null,
			]);
			$changed = true;
		}

		return $changed ? $schema : null;
	}

	#[Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$table = $schema->getTable('share_external');
		if (!$table->hasColumn('share_token') || !$table->hasColumn('refresh_token')) {
			return;
		}

		$qb = $this->db->getQueryBuilder();
		$qb->update('share_external')
			->set('refresh_token', 'share_token');
		$qb->executeStatement();
	}
}
