<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Schema\ColumnType;
use OCP\DB\Types;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Unified user table
 */
#[CreateTable(
	table: 'oc_user_backends',
	columns: ['backend_id', 'backend_name', 'backend_class'],
	description: 'List of registered user backends',
)]
#[CreateTable(
	table: 'oc_user_accounts',
	columns: [
		'account_id',
		'backend_id',
		'account_identifier',
		'account_primary_email',
		'account_display_name',
		'account_password',
		'legacy_uid',
		'account_status',
		'account_last_seen',
	],
	description: 'List of users from all backends',
)]
class Version36000Date20261005112424 extends SimpleMigrationStep {
	#[Override]
	public function name(): string {
		return 'Create unified user table';
	}

	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$changed = false;

		// We need a case-insensitive collation
		// Logins "user123" and "UsEr123" are identical
		$platform = $schema->getDatabasePlatform();
		$caseInsensitiveCollation = match (true) {
			$platform instanceof MySQLPlatform => 'utf8mb4_unicode_ci',
			$platform instanceof MariaDBPlatform => 'utf8mb4_unicode_ci',
			$platform instanceof PostgreSQLPlatform => 'und-x-icu',
			$platform instanceof SqlitePlatform => 'NOCASE',
			default => null,
		};

		if (!$schema->hasTable('user_backends')) {
			$userBackendsTable = $schema->createTable('user_backends');
			$userBackendsTable->addColumn('backend_id', Types::SMALLINT, ['autoincrement' => true, 'unsigned' => true, 'notnull' => true]);
			$userBackendsTable->addColumn('backend_name', Types::STRING, [
				'length' => 64,
				'customSchemaOptions' => [
					'collation' => $caseInsensitiveCollation,
				],
			]);
			$userBackendsTable->addColumn('backend_class', Types::STRING, [
				'length' => 64,
				'customSchemaOptions' => [
					'collation' => $caseInsensitiveCollation,
				],
			]);
			$userBackendsTable->setPrimaryKey(['backend_id']);
			$userBackendsTable->addUniqueIndex(['backend_name']);
			$changed = true;
		}

		if (!$schema->hasTable('user_accounts')) {
			$userAccountsTable = $schema->createTable('user_accounts');
			$userAccountsTable->addColumn('account_id', Types::BIGINT, ['unsigned' => true, 'notnull' => true]);
			$userAccountsTable->addColumn('backend_id', Types::SMALLINT, ['unsigned' => true, 'notnull' => true, 'default' => 0]);
			$userAccountsTable->addColumn('account_identifier', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'customSchemaOptions' => [
					'collation' => $caseInsensitiveCollation,
				],
			]);
			$userAccountsTable->addColumn('account_primary_email', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'customSchemaOptions' => [
					'collation' => $caseInsensitiveCollation,
				],
			]);
			$userAccountsTable->addColumn('account_display_name', Types::STRING, [
				'default' => '',
				'notnull' => true,
				'length' => 64,
				'customSchemaOptions' => [
					'collation' => $caseInsensitiveCollation,
				],
			]);
			$userAccountsTable->addColumn('account_password', Types::BINARY, [
				'default' => '',
				'notnull' => true,
				'length' => 255,
			]);
			$userAccountsTable->addColumn('legacy_uid', Types::STRING, [
				'default' => '',
				'notnull' => true,
				'length' => 64,
				'customSchemaOptions' => [
					'collation' => $caseInsensitiveCollation,
				],
			]);
			$userAccountsTable->addColumn('account_status', ColumnType::TinyInt, ['unsigned' => true, 'notnull' => true, 'default' => 0]);
			$userAccountsTable->addColumn('account_last_seen', ColumnType::TimestampImmutable, [
				'notnull' => false,
			]);

			$userAccountsTable->setPrimaryKey(['account_id']);
			$userAccountsTable->addUniqueIndex(['account_identifier']);
			$changed = true;
		}

		return $changed ? $schema : null;
	}
}
