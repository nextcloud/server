<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[AddColumn(table: 'dav_shares', name: 'token', type: ColumnType::STRING)]
#[CreateTable(table: 'calendars_federated', columns: ['id', 'display_name', 'color', 'uri', 'principaluri', 'remote_Url', 'token', 'sync_token', 'last_sync', 'shared_by', 'shared_by_display_name', 'components', 'permissions'], description: 'Supporting Federated Calender')]
class Version1034Date20250605132605 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$davSharesTable = $schema->getTable('dav_shares');
		if (!$davSharesTable->hasColumn('token')) {
			$davSharesTable->addColumn('token', Types::STRING, [
				'notnull' => false,
				'default' => null,
				'length' => 255,
			]);
		}

		if (!$schema->hasTable('calendars_federated')) {
			$federatedCalendarsTable = $schema->createTable('calendars_federated');
			$federatedCalendarsTable->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$federatedCalendarsTable->addColumn('display_name', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$federatedCalendarsTable->addColumn('color', Types::STRING, [
				'notnull' => false,
				'length' => 7,
				'default' => null,
			]);
			$federatedCalendarsTable->addColumn('uri', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$federatedCalendarsTable->addColumn('principaluri', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$federatedCalendarsTable->addColumn('remote_Url', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$federatedCalendarsTable->addColumn('token', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$federatedCalendarsTable->addColumn('sync_token', Types::INTEGER, [
				'notnull' => true,
				'unsigned' => true,
				'default' => 0,
			]);
			$federatedCalendarsTable->addColumn('last_sync', Types::BIGINT, [
				'notnull' => false,
				'unsigned' => true,
				'default' => null,
			]);
			$federatedCalendarsTable->addColumn('shared_by', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$federatedCalendarsTable->addColumn('shared_by_display_name', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$federatedCalendarsTable->addColumn('components', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$federatedCalendarsTable->addColumn('permissions', Types::INTEGER, [
				'notnull' => true,
			]);
			$federatedCalendarsTable->setPrimaryKey(['id']);
			$federatedCalendarsTable->addIndex(['principaluri', 'uri'], 'fedcals_uris_index');
			$federatedCalendarsTable->addIndex(['last_sync'], 'fedcals_last_sync_index');
		}

		return $schema;
	}
}
