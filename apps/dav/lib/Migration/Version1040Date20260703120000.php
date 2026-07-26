<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCA\DAV\CalDAV\DefaultCalendarAlarms;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\Attributes\DataCleansing;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

#[DataCleansing(table: 'calendars', description: 'Migrate legacy default_alarm_* integers to default_alarms_* JSON')]
#[AddColumn(table: 'calendars', name: 'default_alarms_pday', type: ColumnType::TEXT)]
#[AddColumn(table: 'calendars', name: 'default_alarms_fday', type: ColumnType::TEXT)]
class Version1040Date20260703120000 extends SimpleMigrationStep {
	public function __construct(
		private IDBConnection $db,
	) {
	}

	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$calendarsTable = $schema->getTable('calendars');

		if (!$calendarsTable->hasColumn('default_alarms_pday')) {
			$calendarsTable->addColumn('default_alarms_pday', Types::TEXT, [
				'notnull' => false,
				'default' => null,
			]);
		}

		if (!$calendarsTable->hasColumn('default_alarms_fday')) {
			$calendarsTable->addColumn('default_alarms_fday', Types::TEXT, [
				'notnull' => false,
				'default' => null,
			]);
		}

		return $schema;
	}

	#[Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$select = $this->db->getQueryBuilder();
		$select->select('id', 'default_alarm_pday', 'default_alarm_fday', 'default_alarms_pday', 'default_alarms_fday')
			->from('calendars')
			->where($select->expr()->orX(
				$select->expr()->andX(
					$select->expr()->isNotNull('default_alarm_pday'),
					$select->expr()->isNull('default_alarms_pday'),
				),
				$select->expr()->andX(
					$select->expr()->isNotNull('default_alarm_fday'),
					$select->expr()->isNull('default_alarms_fday'),
				),
			));

		$result = $select->executeQuery();
		while (($row = $result->fetchAssociative()) !== false) {
			$id = (int)$row['id'];

			if ($row['default_alarms_pday'] === null && $row['default_alarm_pday'] !== null) {
				$this->updateAlarmsColumn($id, 'default_alarms_pday', DefaultCalendarAlarms::encodeFromLegacyInt((int)$row['default_alarm_pday']));
			}

			if ($row['default_alarms_fday'] === null && $row['default_alarm_fday'] !== null) {
				$this->updateAlarmsColumn($id, 'default_alarms_fday', DefaultCalendarAlarms::encodeFromLegacyInt((int)$row['default_alarm_fday']));
			}
		}
		$result->closeCursor();
	}

	private function updateAlarmsColumn(int $calendarId, string $column, ?string $value): void {
		$update = $this->db->getQueryBuilder();
		$update->update('calendars')
			->set($column, $update->createNamedParameter($value, IQueryBuilder::PARAM_STR))
			->where($update->expr()->eq('id', $update->createNamedParameter($calendarId, IQueryBuilder::PARAM_INT)));
		$update->executeStatement();
	}
}
