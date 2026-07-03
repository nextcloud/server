<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\Attributes\DataCleansing;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Add JSON default-alarm columns and copy legacy single-int defaults via set-based UPDATEs.
 */
#[DataCleansing(table: 'calendars', description: 'Migrate legacy default_alarm_* integers to default_alarms_* JSON')]
#[AddColumn(table: 'calendars', name: 'default_alarms_pday', type: ColumnType::TEXT)]
#[AddColumn(table: 'calendars', name: 'default_alarms_fday', type: ColumnType::TEXT)]
class Version2000Date20260703100001 extends SimpleMigrationStep {
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
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$calendarsTable = $schema->getTable('calendars');

		if ($calendarsTable->hasColumn('default_alarm_pday') && $calendarsTable->hasColumn('default_alarms_pday')) {
			$this->migrateLegacyIntColumn('default_alarm_pday', 'default_alarms_pday');
		}

		if ($calendarsTable->hasColumn('default_alarm_fday') && $calendarsTable->hasColumn('default_alarms_fday')) {
			$this->migrateLegacyIntColumn('default_alarm_fday', 'default_alarms_fday');
		}
	}

	/**
	 * Encode a single legacy trigger int as [{"trigger":N,"action":"DISPLAY"}]
	 * for all rows still missing the JSON column value.
	 */
	private function migrateLegacyIntColumn(string $legacyColumn, string $jsonColumn): void {
		$qb = $this->db->getQueryBuilder();
		$qb->update('calendars')
			->set($jsonColumn, $qb->func()->concat(
				$qb->expr()->literal('[{"trigger":'),
				$legacyColumn,
				$qb->expr()->literal(',"action":"DISPLAY"}]'),
			))
			->where($qb->expr()->isNotNull($legacyColumn))
			->andWhere($qb->expr()->isNull($jsonColumn));
		$qb->executeStatement();
	}
}
